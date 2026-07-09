<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Support\ImageVariant;
use App\Support\ProductImageAlt;
use App\Support\RichContent;
use App\Support\SlugHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class WooCommerceCatalogImporter
{
    /** @var list<string> */
    private const BRAND_NAMES = [
        'Pedrollo', 'Sumak', 'Grundfos', 'Wilo', 'DAB', 'KSB', 'Lowara', 'Calpeda',
        'Caprari', 'Ebara', 'Franklin', 'Impo', 'Standart', 'Ocean', 'Demtaş', 'Demtas',
        'Vansan', 'Alarko', 'Mas', 'Standart Pompa', 'Kleen', 'Pentax', 'Saer', 'Ondilo',
    ];

    /** @var array<string, int> */
    private array $categoryIdsByPath = [];

    /** @var array<string, int> */
    private array $brandIdsBySlug = [];

    /** @var array<string, bool> */
    private array $usedSlugs = [];

    /** @var array<string, mixed> */
    private array $stats = [
        'categories' => 0,
        'brands' => 0,
        'products' => 0,
        'images_ok' => 0,
        'images_failed' => 0,
        'gallery_images' => 0,
        'skipped' => 0,
    ];

    /**
     * @return array{
     *   products: int,
     *   products_skipped: int,
     *   brands: int,
     *   categories_total: int,
     *   categories_root: int,
     *   categories_child: int,
     *   brand_names: list<string>,
     *   category_roots: list<string>
     * }
     */
    public function preview(string $catalogPath): array
    {
        $rows = $this->loadCatalogRows($catalogPath, null, 0);

        return $this->analyzeCatalogRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function import(
        string $catalogPath,
        bool $fresh = true,
        ?int $limit = null,
        bool $downloadImages = true,
        int $offset = 0,
        bool $seedOnly = false,
        bool $productsOnly = false,
    ): array {
        if ($seedOnly) {
            $rows = $this->loadCatalogRows($catalogPath, null, 0);
            if ($rows === []) {
                throw new RuntimeException('Dosyada ürün satırı bulunamadı (SKU ve Name gerekli).');
            }

            $this->resetImportState();

            DB::transaction(function () use ($rows, $fresh) {
                if ($fresh) {
                    $this->wipeCatalog();
                }
                $this->seedCategoriesAndBrands($rows);
            });

            return $this->stats;
        }

        $rows = $this->loadCatalogRows($catalogPath, $limit, $offset);

        if ($rows === []) {
            throw new RuntimeException('Dosyada ürün satırı bulunamadı (SKU ve Name gerekli).');
        }

        $this->resetImportState();

        DB::transaction(function () use ($rows, $fresh, $downloadImages, $productsOnly, $catalogPath, $offset) {
            if ($fresh) {
                $this->wipeCatalog();
            }

            if ($productsOnly) {
                $this->loadExistingCatalogMaps();
            } elseif (! $productsOnly) {
                $seedRows = $offset > 0 || $limit !== null
                    ? $this->loadCatalogRows($catalogPath, null, 0)
                    : $rows;
                $this->seedCategoriesAndBrands($seedRows);
            }

            foreach ($rows as $row) {
                $this->importProductRow($this->normalizeImportRow($row), $downloadImages);
            }
        });

        return $this->stats;
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{
     *   products: int,
     *   products_skipped: int,
     *   brands: int,
     *   categories_total: int,
     *   categories_root: int,
     *   categories_child: int,
     *   brand_names: list<string>,
     *   category_roots: list<string>
     * }
     */
    private function analyzeCatalogRows(array $rows): array
    {
        $categoryPaths = [];
        $brandLabels = [];
        $products = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $sku = trim((string) ($row['SKU'] ?? ''));
            $name = trim((string) ($row['Name'] ?? ''));

            if ($sku === '' || $name === '') {
                $skipped++;

                continue;
            }

            $products++;

            foreach ($this->parseAllCategoryPaths((string) ($row['Categories'] ?? '')) as $path) {
                $categoryPaths[implode("\0", $path)] = $path;
            }

            $brand = $this->resolveBrandLabel($row);
            if ($brand !== null) {
                $brandLabels[Str::slug($brand)] = $brand;
            }
        }

        $categoryNodes = [];

        foreach ($categoryPaths as $path) {
            $pathKey = '';
            foreach ($path as $name) {
                $pathKey = $pathKey === '' ? $name : $pathKey."\0".$name;
                $categoryNodes[$pathKey] = substr_count($pathKey, "\0") + 1;
            }
        }

        $categoriesRoot = 0;
        $rootNames = [];

        foreach ($categoryNodes as $pathKey => $depth) {
            if ($depth === 1) {
                $categoriesRoot++;
                $rootNames[] = explode("\0", $pathKey)[0];
            }
        }

        sort($rootNames);
        ksort($brandLabels);

        return [
            'products' => $products,
            'products_skipped' => $skipped,
            'brands' => count($brandLabels),
            'categories_total' => count($categoryNodes),
            'categories_root' => $categoriesRoot,
            'categories_child' => count($categoryNodes) - $categoriesRoot,
            'brand_names' => array_values($brandLabels),
            'category_roots' => $rootNames,
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function loadCatalogRows(string $path, ?int $limit, int $offset = 0): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Dosya bulunamadı: {$path}");
        }

        if (str_ends_with(strtolower($path), '.csv')) {
            return $this->loadCsvRows($path, $limit, $offset);
        }

        $jsonPath = $this->exportExcelToJson($path, $limit);
        $payload = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        return $payload['rows'] ?? [];
    }

    /**
     * @return list<array<string, string>>
     */
    private function loadCsvRows(string $csvPath, ?int $limit, int $offset = 0): array
    {
        $content = $this->prepareCsvContent($csvPath);
        $handle = fopen('php://memory', 'r+');
        if ($handle === false) {
            throw new RuntimeException('CSV belleği açılamadı.');
        }
        fwrite($handle, $content);
        rewind($handle);

        $delimiter = $this->detectCsvDelimiter($content);
        $header = null;
        $rows = [];
        $skippedOffset = 0;

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($header === null) {
                $header = $this->normalizeCatalogHeaders($line);
                if (! in_array('SKU', $header, true) || ! in_array('Name', $header, true)) {
                    fclose($handle);
                    throw new RuntimeException('CSV başlık satırı geçersiz (SKU / Name bekleniyor).');
                }
                continue;
            }

            if ($this->csvLineEmpty($line)) {
                continue;
            }

            $cells = array_pad($line, count($header), '');
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = (string) ($cells[$i] ?? '');
            }

            if (trim($row['SKU'] ?? '') === '' && trim($row['Name'] ?? '') === '') {
                continue;
            }

            if ($offset > 0 && $skippedOffset < $offset) {
                $skippedOffset++;

                continue;
            }

            $rows[] = $row;

            if ($limit !== null && $limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function detectCsvDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\n") ?: '';
        if ($firstLine === '') {
            return ',';
        }

        $semicolons = substr_count($firstLine, ';');
        $commas = substr_count($firstLine, ',');

        return $semicolons > $commas ? ';' : ',';
    }

    private function prepareCsvContent(string $csvPath): string
    {
        $content = file_get_contents($csvPath);
        if ($content === false) {
            throw new RuntimeException('CSV dosyası okunamadı.');
        }

        $content = $this->stripPhpWarningsFromCsv($content);
        $content = $this->convertCatalogCsvToUtf8($content);

        return $content;
    }

    private function convertCatalogCsvToUtf8(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        $headerSample = substr($content, 0, 4000);

        $needsCp1254 = ! mb_check_encoding($headerSample, 'UTF-8')
            || str_contains($headerSample, "\u{FFFD}")
            || (str_contains($headerSample, 'Kimlik') && ! str_contains($headerSample, 'İsim'));

        if ($needsCp1254) {
            $converted = @mb_convert_encoding($content, 'UTF-8', 'Windows-1254');
            if ($converted !== false) {
                return $converted;
            }
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            $converted = @mb_convert_encoding($content, 'UTF-8', 'ISO-8859-9');

            return $converted !== false ? $converted : $content;
        }

        return $content;
    }

    private function stripPhpWarningsFromCsv(string $content): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $clean = [];
        $headerFound = false;

        foreach ($lines as $line) {
            $trim = trim($line);
            if (! $headerFound) {
                if ($trim === '' || str_starts_with($trim, '<')) {
                    continue;
                }
                if (stripos($line, 'SKU') !== false && (stripos($line, 'İsim') !== false || stripos($line, 'Name') !== false)) {
                    $headerFound = true;
                    $clean[] = $line;
                    continue;
                }
                continue;
            }
            $clean[] = $line;
        }

        if (! $headerFound) {
            throw new RuntimeException('CSV başlık satırı bulunamadı. Dosya başında PHP uyarıları veya bozuk satırlar olabilir.');
        }

        return implode("\n", $clean);
    }

    private function csvLooksCorrupted(string $content): bool
    {
        $sample = substr($content, 0, 250000);

        return substr_count($sample, '??') > 40
            || preg_match_all('/\w\?\w/u', $sample, $m) && count($m[0]) > 80;
    }

    /** @var array<string, string> */
    private const TURKISH_HEADERS = [
        'Kimlik' => 'ID',
        'Tür' => 'Type',
        'SKU' => 'SKU',
        'İsim' => 'Name',
        'Yayınlandı' => 'Published',
        'Öne çıkıyor mu?' => 'Is featured?',
        'Kısa açıklama' => 'Short description',
        'Açıklama' => 'Description',
        'İndirim fiyatı' => 'Sale price',
        'Normal fiyat' => 'Regular price',
        'Kategoriler' => 'Categories',
        'Etiketler' => 'Tags',
        'Görüntüler' => 'Images',
        'Görseller' => 'Images',
        'Stok' => 'Stock',
        'Markalar' => 'Brands',
    ];

    /** @param  list<string|null>  $header */
    private function normalizeCatalogHeaders(array $header): array
    {
        $aliases = [
            'sku' => 'SKU',
            'name' => 'Name',
            'isim' => 'Name',
            'published' => 'Published',
            'yayin_durumu' => 'Published',
            'yayinlandi' => 'Published',
            'is_featured' => 'Is featured?',
            'one_cikan' => 'Is featured?',
            'one_cikiyor_mu' => 'Is featured?',
            'short_description' => 'Short description',
            'kisa_aciklama' => 'Short description',
            'description' => 'Description',
            'aciklama' => 'Description',
            'regular_price' => 'Regular price',
            'normal_fiyat' => 'Regular price',
            'indirimsiz_fiyat' => 'Regular price',
            'sale_price' => 'Sale price',
            'indirim_fiyati' => 'Sale price',
            'indirim_fiyat' => 'Sale price',
            'categories' => 'Categories',
            'kategoriler' => 'Categories',
            'images' => 'Images',
            'goruntuler' => 'Images',
            'gorseller' => 'Images',
            'stock' => 'Stock',
            'stok' => 'Stock',
            'brands' => 'Brands',
            'markalar' => 'Brands',
            'url_slug' => 'URL Slug',
            'attributes_json' => 'Attributes_JSON',
        ];

        return array_map(function (?string $cell) use ($aliases): string {
            $cell = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $cell)) ?? trim((string) $cell);

            if (isset(self::TURKISH_HEADERS[$cell])) {
                return self::TURKISH_HEADERS[$cell];
            }

            $key = mb_strtolower($cell, 'UTF-8');
            $key = preg_replace('/[^a-z0-9ğüşıöç]+/u', '_', $key) ?? $key;
            $key = trim($key, '_');

            return $aliases[$key] ?? $cell;
        }, $header);
    }

    /** @param  list<string|null>  $line */
    private function csvLineEmpty(array $line): bool
    {
        foreach ($line as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private function normalizeImportRow(array $row): array
    {
        foreach ([
            'Meta: _yoast_wpseo_title' => ['Meta: rank_math_title', 'Meta: Rank Math Title'],
            'Meta: _yoast_wpseo_metadesc' => ['Meta: rank_math_description', 'Meta: Rank Math Description'],
            'Meta: _yoast_wpseo_focuskw' => ['Meta: rank_math_focus_keyword', 'Meta: Rank Math Focus Keyword'],
        ] as $canonical => $alternates) {
            if (trim((string) ($row[$canonical] ?? '')) !== '') {
                continue;
            }
            foreach ($alternates as $alt) {
                if (trim((string) ($row[$alt] ?? '')) !== '') {
                    $row[$canonical] = $row[$alt];
                    break;
                }
            }
        }

        return $row;
    }

    private function exportExcelToJson(string $xlsxPath, ?int $limit): string
    {
        if (! is_file($xlsxPath)) {
            throw new RuntimeException("Excel bulunamadı: {$xlsxPath}");
        }

        $outDir = storage_path('app/import');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $jsonPath = $outDir.'/woocommerce_catalog.json';
        $script = base_path('scripts/woocommerce_excel_to_json.py');
        $command = array_merge(
            $this->pythonBinary(),
            [$script, $xlsxPath, '-o', $jsonPath],
        );

        if ($limit !== null && $limit > 0) {
            $command[] = '--limit';
            $command[] = (string) $limit;
        }

        $result = Process::timeout(600)->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(
                'Excel okunamadı. Python gerekli (py -3). Hata: '.trim($result->errorOutput() ?: $result->output())
            );
        }

        return $jsonPath;
    }

    /**
     * @return list<string>
     */
    private function pythonBinary(): array
    {
        foreach ([['py', '-3'], ['python3'], ['python']] as $cmd) {
            $check = Process::run([...$cmd, '--version']);
            if ($check->successful()) {
                return $cmd;
            }
        }

        throw new RuntimeException('Python bulunamadı (py -3 veya python3 kurulu olmalı).');
    }

    private function resetImportState(): void
    {
        $this->stats = [
            'categories' => 0,
            'brands' => 0,
            'products' => 0,
            'images_ok' => 0,
            'images_failed' => 0,
            'gallery_images' => 0,
            'skipped' => 0,
        ];
        $this->categoryIdsByPath = [];
        $this->brandIdsBySlug = [];
        $this->usedSlugs = [];
    }

    private function loadExistingCatalogMaps(): void
    {
        $categories = Category::query()->get(['id', 'name', 'parent_id']);
        $byId = $categories->keyBy('id');

        foreach ($categories as $category) {
            $segments = [];
            $current = $category;

            while ($current !== null) {
                array_unshift($segments, $current->name);
                $current = $current->parent_id !== null ? $byId->get($current->parent_id) : null;
            }

            $pathKey = implode("\0", $segments);
            $this->categoryIdsByPath[$pathKey] = $category->id;
        }

        foreach (Brand::query()->pluck('id', 'name') as $name => $id) {
            $this->brandIdsBySlug[(string) $name] = (int) $id;
        }

        foreach (Product::query()->pluck('slug') as $slug) {
            $this->usedSlugs[(string) $slug] = true;
        }
    }

    private function wipeCatalog(): void
    {
        DB::table('home_banners')->whereNotNull('product_id')->update(['product_id' => null]);
        DB::table('home_banners')->whereNotNull('category_id')->update(['category_id' => null]);

        ProductReview::query()->delete();
        ProductImage::query()->delete();
        DB::table('category_product')->delete();
        Product::query()->delete();

        while (Category::query()->whereNotNull('parent_id')->exists()) {
            Category::query()->whereDoesntHave('children')->delete();
        }
        Category::query()->delete();
        Brand::query()->delete();

        $this->categoryIdsByPath = [];
        $this->brandIdsBySlug = [];
        $this->usedSlugs = [];
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function seedCategoriesAndBrands(array $rows): void
    {
        $categoryPaths = [];
        $brandLabels = [];

        foreach ($rows as $row) {
            foreach ($this->parseAllCategoryPaths((string) ($row['Categories'] ?? '')) as $path) {
                $categoryPaths[implode("\0", $path)] = $path;
            }

            $brand = $this->resolveBrandLabel($row);
            if ($brand !== null) {
                $brandLabels[Str::slug($brand)] = $brand;
            }
        }

        $sort = 0;
        foreach ($brandLabels as $slug => $name) {
            $brand = Brand::query()->create([
                'slug' => SlugHelper::assign('brands', $slug, $name),
                'name' => $name,
                'active' => true,
                'featured' => false,
                'sort_order' => $sort++,
            ]);
            $this->brandIdsBySlug[$brand->name] = $brand->id;
            $this->stats['brands']++;
        }

        $paths = array_values($categoryPaths);
        usort($paths, fn (array $a, array $b) => count($a) <=> count($b));

        foreach ($paths as $segments) {
            $this->ensureCategoryPath($segments);
        }
    }

    /**
     * @return list<list<string>>
     */
    private function parseAllCategoryPaths(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $assignments = preg_split('/\s*,\s*/', $raw) ?: [$raw];
        $paths = [];

        foreach ($assignments as $assignment) {
            $path = $this->parseSingleCategoryPath(trim($assignment));
            if ($path !== []) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function parseSingleCategoryPath(string $raw): array
    {
        if (str_contains($raw, '>')) {
            $parts = preg_split('/\s*>\s*/', $raw) ?: [];
        } else {
            $parts = preg_split('/\s*\|\s*/', $raw) ?: [];
        }

        return array_values(array_filter(array_map('trim', $parts), fn (string $p) => $p !== ''));
    }

    /**
     * @param  list<string>  $segments
     */
    private function ensureCategoryPath(array $segments): void
    {
        $parentId = null;
        $pathKey = '';

        foreach ($segments as $index => $name) {
            $pathKey = $pathKey === '' ? $name : $pathKey."\0".$name;

            if (isset($this->categoryIdsByPath[$pathKey])) {
                $parentId = $this->categoryIdsByPath[$pathKey];

                continue;
            }

            $slug = SlugHelper::assign('categories', Str::slug($name), $name);
            $category = Category::query()->create([
                'slug' => $slug,
                'name' => $name,
                'parent_id' => $parentId,
                'active' => true,
                'show_in_menu' => $index === 0,
                'featured' => false,
                'sort_order' => Category::query()->where('parent_id', $parentId)->count(),
            ]);

            $this->categoryIdsByPath[$pathKey] = $category->id;
            $parentId = $category->id;
            $this->stats['categories']++;
        }
    }

    private function detectBrand(string $categories, string $productName): ?string
    {
        $first = trim(explode('|', $categories)[0] ?? '');
        $haystack = $first.' '.$productName;

        foreach (self::BRAND_NAMES as $brand) {
            if (stripos($haystack, $brand) !== false) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importProductRow(array $row, bool $downloadImages): void
    {
        $sku = trim((string) ($row['SKU'] ?? ''));
        $name = trim((string) ($row['Name'] ?? ''));

        if ($sku === '' || $name === '') {
            $this->stats['skipped']++;

            return;
        }

        $slug = $this->uniqueProductSlug(trim((string) ($row['URL Slug'] ?? '')), $sku, $name);
        $regular = $this->toFloat($row['Regular price'] ?? '0');
        $sale = $this->toFloat($row['Sale price'] ?? '0');
        $price = $sale > 0 ? $sale : $regular;
        $compare = ($sale > 0 && $regular > $sale) ? $regular : null;

        $brandId = $this->resolveBrandId($row, $name);
        $specs = $this->parseSpecs((string) ($row['Attributes_JSON'] ?? ''));
        $tags = $this->parseTags((string) ($row['Meta: _yoast_wpseo_focuskw'] ?? ''));

        $product = Product::query()->create([
            'slug' => $slug,
            'sku' => $sku,
            'name' => $name,
            'short_description' => $this->cleanText((string) ($row['Short description'] ?? '')),
            'description' => RichContent::normalize((string) ($row['Description'] ?? '')),
            'brand_id' => $brandId,
            'price' => $price,
            'compare_at_price' => $compare,
            'stock' => max(0, (int) round($this->toFloat($row['Stock'] ?? '0'))),
            'featured' => $this->toBool($row['Is featured?'] ?? '0'),
            'is_active' => $this->toBool($row['Published'] ?? '1'),
            'image_alt' => $this->resolveProductImageAlt($row, $name, $brandId),
            'meta_title' => $this->trimOrNull($row['Meta: _yoast_wpseo_title'] ?? null),
            'meta_description' => $this->trimOrNull($row['Meta: _yoast_wpseo_metadesc'] ?? null),
            'tags' => $tags,
            'specs' => $specs,
            'image' => null,
        ]);

        $categoryIds = $this->categoryIdsForRaw((string) ($row['Categories'] ?? ''));
        if ($categoryIds !== []) {
            $product->categories()->sync($categoryIds);
        }

        $imageUrls = $this->parseImageUrls((string) ($row['Images'] ?? ''));

        if ($downloadImages && $imageUrls !== []) {
            $coverPath = $this->downloadImage($imageUrls[0], $sku);
            if ($coverPath !== null) {
                $product->update(['image' => $coverPath]);
            }

            $sort = 0;
            foreach (array_slice($imageUrls, 1) as $extraUrl) {
                $galleryPath = $this->downloadImage($extraUrl, $sku.'-g'.(++$sort));
                if ($galleryPath !== null) {
                    ProductImage::query()->create([
                        'product_id' => $product->id,
                        'path' => $galleryPath,
                        'sort_order' => $sort,
                    ]);
                    $this->stats['gallery_images']++;
                }
            }
        }

        $this->stats['products']++;
    }

    /**
     * @return list<string>
     */
    private function parseImageUrls(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        if (preg_match_all('#https?://[^\s,"\'<>]+#i', $raw, $matches)) {
            return array_values(array_unique($matches[0]));
        }

        return [];
    }

    /** @return list<int> */
    private function categoryIdsForRaw(string $raw): array
    {
        $ids = [];
        foreach ($this->parseAllCategoryPaths($raw) as $segments) {
            $ids = array_merge($ids, $this->categoryIdsForPath($segments));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $segments
     * @return list<int>
     */
    private function categoryIdsForPath(array $segments): array
    {
        if ($segments === []) {
            return [];
        }

        $ids = [];
        $pathKey = '';

        foreach ($segments as $name) {
            $pathKey = $pathKey === '' ? $name : $pathKey."\0".$name;
            if (isset($this->categoryIdsByPath[$pathKey])) {
                $ids[] = $this->categoryIdsByPath[$pathKey];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveBrandLabel(array $row): ?string
    {
        $fromColumn = trim((string) ($row['Brands'] ?? ''));
        if ($fromColumn !== '') {
            return $fromColumn;
        }

        return $this->detectBrand((string) ($row['Categories'] ?? ''), (string) ($row['Name'] ?? ''));
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveBrandId(array $row, string $productName): ?int
    {
        $label = $this->resolveBrandLabel($row);
        if ($label === null) {
            return null;
        }

        return $this->brandIdsBySlug[$label] ?? null;
    }

    private function uniqueProductSlug(string $preferred, string $sku, string $name): string
    {
        $base = $preferred !== '' ? Str::slug($preferred) : Str::slug($name);
        if ($base === '') {
            $base = Str::slug($sku);
        }

        $slug = $base;
        if (isset($this->usedSlugs[$slug])) {
            $slug = $base.'-'.Str::slug($sku);
        }

        $suffix = 2;
        while (isset($this->usedSlugs[$slug])) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        $this->usedSlugs[$slug] = true;

        return $slug;
    }

    /**
     * @return array<string, string>|null
     */
    private function parseSpecs(string $json): ?array
    {
        $json = trim($json);
        if ($json === '') {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $specs = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $specs[$key] = (string) $value;
            }
        }

        return $specs === [] ? null : $specs;
    }

    /**
     * @return list<string>
     */
    private function parseTags(string $focusKw): array
    {
        $focusKw = trim($focusKw);
        if ($focusKw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $focusKw) ?: [])));
    }

    private function downloadImage(string $url, string $sku): ?string
    {
        $url = trim($url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->stats['images_failed']++;

            return null;
        }

        try {
            $response = Http::timeout(45)->retry(2, 500)->get($url);
            if (! $response->successful()) {
                $this->stats['images_failed']++;

                return null;
            }

            $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
            $ext = strtolower(preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $ext = 'jpg';
            }

            $path = 'products/import/'.Str::slug($sku).'.'.$ext;
            Storage::disk('public')->put($path, $response->body());
            ImageVariant::generate($path, ImageVariant::presetsFor(str_contains($sku, '-g') ? 'product-gallery' : 'product'));
            $this->stats['images_ok']++;

            return $path;
        } catch (\Throwable) {
            $this->stats['images_failed']++;

            return null;
        }
    }

    private function toFloat(string|float|int $value): float
    {
        return (float) str_replace(',', '.', (string) $value);
    }

    private function toBool(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'yes', 'true', 'evet'], true);
    }

    private function trimOrNull(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    private function cleanText(string $text): ?string
    {
        $text = trim(strip_tags($text));

        return $text === '' ? null : $text;
    }

  private function resolveProductImageAlt(array $row, string $name, ?int $brandId): string
    {
        $fromCsv = $this->trimOrNull($row['Image Alt Text'] ?? null);
        if ($fromCsv !== null) {
            return $fromCsv;
        }

        $brandName = $brandId
            ? Brand::query()->whereKey($brandId)->value('name')
            : null;

        return ProductImageAlt::generate($name, $brandName);
    }
}
