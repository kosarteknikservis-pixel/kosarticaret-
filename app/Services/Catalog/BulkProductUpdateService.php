<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BulkProductUpdateService
{
    /** @var array<string, int> */
    private array $stats = [
        'matched' => 0,
        'updated' => 0,
        'skipped' => 0,
        'csv_rows' => 0,
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countMatching(array $filters): int
    {
        return $this->filterQuery($filters)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $actions
     * @return array<string, int>
     */
    public function apply(array $filters, array $actions, bool $dryRun = false): array
    {
        $this->stats = ['matched' => 0, 'updated' => 0, 'skipped' => 0, 'csv_rows' => 0];

        if (! $this->hasAnyAction($actions)) {
            throw new InvalidArgumentException('En az bir güncelleme alanı seçmelisiniz.');
        }

        $query = $this->filterQuery($filters);
        $this->stats['matched'] = (clone $query)->count();

        if ($dryRun || $this->stats['matched'] === 0) {
            return $this->stats;
        }

        $query->with('categories')->orderBy('id')->chunkById(100, function (Collection $products) use ($actions): void {
            foreach ($products as $product) {
                if ($this->applyActionsToProduct($product, $actions)) {
                    $this->stats['updated']++;
                } else {
                    $this->stats['skipped']++;
                }
            }
        });

        return $this->stats;
    }

    /**
     * @return array<string, int>
     */
    public function applyCsv(string $csvPath, bool $dryRun = false): array
    {
        $this->stats = ['matched' => 0, 'updated' => 0, 'skipped' => 0, 'csv_rows' => 0];

        if (! is_readable($csvPath)) {
            throw new InvalidArgumentException('CSV dosyası okunamadı.');
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException('CSV dosyası açılamadı.');
        }

        $header = null;
        $rows = [];

        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = $this->normalizeCsvHeader($line);
                if (! in_array('sku', $header, true)) {
                    fclose($handle);
                    throw new InvalidArgumentException('CSV dosyasında SKU sütunu zorunludur.');
                }
                continue;
            }
            if ($this->csvRowEmpty($line)) {
                continue;
            }
            $rows[] = array_combine($header, array_pad($line, count($header), ''));
        }
        fclose($handle);

        $this->stats['csv_rows'] = count($rows);

        foreach ($rows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                $this->stats['skipped']++;
                continue;
            }

            $product = Product::query()->where('sku', $sku)->first();
            if ($product === null) {
                $this->stats['skipped']++;
                continue;
            }

            $this->stats['matched']++;
            $actions = $this->actionsFromCsvRow($row);

            if ($actions === []) {
                $this->stats['skipped']++;
                continue;
            }

            if ($dryRun) {
                continue;
            }

            if ($this->applyActionsToProduct($product, $actions)) {
                $this->stats['updated']++;
            } else {
                $this->stats['skipped']++;
            }
        }

        return $this->stats;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filterQuery(array $filters): Builder
    {
        $query = Product::query();

        $categoryIds = $this->expandedCategoryIds($filters['category_ids'] ?? []);
        if ($categoryIds !== []) {
            $query->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds));
        }

        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        $stock = (string) ($filters['stock'] ?? 'any');
        if ($stock === 'in_stock') {
            $query->where('stock', '>', 0);
        } elseif ($stock === 'out_of_stock') {
            $query->where('stock', '<=', 0);
        } elseif ($stock === 'low') {
            $threshold = max(0, (int) ($filters['stock_low_max'] ?? 5));
            $query->whereBetween('stock', [1, $threshold]);
        }

        $featured = (string) ($filters['featured'] ?? 'any');
        if ($featured === 'yes') {
            $query->where('featured', true);
        } elseif ($featured === 'no') {
            $query->where('featured', false);
        }

        $active = (string) ($filters['is_active'] ?? 'any');
        if ($active === 'yes') {
            $query->where('is_active', true);
        } elseif ($active === 'no') {
            $query->where('is_active', false);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        $skus = $this->parseSkuList((string) ($filters['sku_list'] ?? ''));
        if ($skus !== []) {
            $query->whereIn('sku', $skus);
        }

        $ids = array_filter(array_map('intval', (array) ($filters['product_ids'] ?? [])));
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $actions
     */
    private function applyActionsToProduct(Product $product, array $actions): bool
    {
        $dirty = false;

        if (($actions['price']['mode'] ?? 'none') !== 'none') {
            $new = $this->adjustMoney((float) $product->price, $actions['price']);
            if ($new !== null && (float) $product->price !== $new) {
                $product->price = $new;
                $dirty = true;
            }
        }

        if (($actions['compare']['mode'] ?? 'none') !== 'none') {
            $new = $this->adjustCompare((float) $product->price, $product->compare_at_price, $actions['compare']);
            if ($new !== false) {
                $current = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;
                if ($new !== $current) {
                    $product->compare_at_price = $new;
                    $dirty = true;
                }
            }
        }

        if (($actions['stock']['mode'] ?? 'none') !== 'none') {
            $new = $this->adjustStock((int) $product->stock, $actions['stock']);
            if ($new !== null && (int) $product->stock !== $new) {
                $product->stock = $new;
                $dirty = true;
            }
        }

        $brandMode = $actions['brand']['mode'] ?? 'none';
        if ($brandMode === 'clear') {
            if ($product->brand_id !== null) {
                $product->brand_id = null;
                $dirty = true;
            }
        } elseif ($brandMode === 'set' && ! empty($actions['brand']['brand_id'])) {
            $newBrand = (int) $actions['brand']['brand_id'];
            if ($product->brand_id !== $newBrand) {
                $product->brand_id = $newBrand;
                $dirty = true;
            }
        }

        if (($actions['categories']['mode'] ?? 'none') !== 'none') {
            $categoryIds = array_map('intval', $actions['categories']['ids'] ?? []);
            $mode = $actions['categories']['mode'];
            if ($mode === 'sync') {
                $product->categories()->sync($categoryIds);
                $dirty = true;
            } elseif ($mode === 'add' && $categoryIds !== []) {
                $product->categories()->syncWithoutDetaching($categoryIds);
                $dirty = true;
            } elseif ($mode === 'remove' && $categoryIds !== []) {
                $product->categories()->detach($categoryIds);
                $dirty = true;
            }
        }

        foreach (['is_active', 'featured'] as $flag) {
            $mode = $actions['status'][$flag] ?? 'no_change';
            if ($mode === 'enable' && ! $product->{$flag}) {
                $product->{$flag} = true;
                $dirty = true;
            } elseif ($mode === 'disable' && $product->{$flag}) {
                $product->{$flag} = false;
                $dirty = true;
            }
        }

        if (($actions['tags']['mode'] ?? 'none') !== 'none') {
            $newTags = $this->adjustTags($product->tags ?? [], $actions['tags']);
            if ($newTags !== $product->tags) {
                $product->tags = $newTags;
                $dirty = true;
            }
        }

        foreach (['meta_title', 'meta_description', 'image_alt', 'short_description'] as $textField) {
            if (($actions['text'][$textField]['mode'] ?? 'none') === 'none') {
                continue;
            }
            $new = $this->adjustText((string) ($product->{$textField} ?? ''), $actions['text'][$textField]);
            if ($new !== null && (string) $product->{$textField} !== $new) {
                $product->{$textField} = $new;
                $dirty = true;
            }
        }

        if ($dirty) {
            $product->save();
        }

        return $dirty;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function adjustMoney(float $current, array $config): ?float
    {
        $mode = $config['mode'] ?? 'none';
        $value = (float) ($config['value'] ?? 0);

        return match ($mode) {
            'set' => max(0, round($value, 2)),
            'add_percent' => max(0, round($current * (1 + $value / 100), 2)),
            'subtract_percent' => max(0, round($current * (1 - $value / 100), 2)),
            'add_fixed' => max(0, round($current + $value, 2)),
            'subtract_fixed' => max(0, round($current - $value, 2)),
            'round_99' => max(0, floor($current) + 0.99),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return float|null|false false = no change
     */
    private function adjustCompare(float $price, mixed $compare, array $config): float|null|false
    {
        $mode = $config['mode'] ?? 'none';
        $base = $compare !== null ? (float) $compare : $price;
        $value = (float) ($config['value'] ?? 0);

        return match ($mode) {
            'clear' => null,
            'set' => max(0, round($value, 2)),
            'add_percent' => max(0, round($base * (1 + $value / 100), 2)),
            'subtract_percent' => max(0, round($base * (1 - $value / 100), 2)),
            'add_fixed' => max(0, round($base + $value, 2)),
            'subtract_fixed' => max(0, round($base - $value, 2)),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function adjustStock(int $current, array $config): ?int
    {
        $mode = $config['mode'] ?? 'none';
        $value = (int) ($config['value'] ?? 0);

        return match ($mode) {
            'set' => max(0, $value),
            'add' => max(0, $current + $value),
            'subtract' => max(0, $current - $value),
            default => null,
        };
    }

    /**
     * @param  list<string>  $current
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private function adjustTags(array $current, array $config): array
    {
        $mode = $config['mode'] ?? 'none';
        $raw = trim((string) ($config['value'] ?? ''));
        $incoming = $raw === ''
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $raw))));

        return match ($mode) {
            'set' => $incoming,
            'append' => array_values(array_unique([...$current, ...$incoming])),
            'clear' => [],
            default => $current,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function adjustText(string $current, array $config): ?string
    {
        $mode = $config['mode'] ?? 'none';
        $value = (string) ($config['value'] ?? '');

        return match ($mode) {
            'set' => $value,
            'append' => $current.$value,
            'prepend' => $value.$current,
            'clear' => '',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $actions
     */
    private function hasAnyAction(array $actions): bool
    {
        if (($actions['price']['mode'] ?? 'none') !== 'none') {
            return true;
        }
        if (($actions['compare']['mode'] ?? 'none') !== 'none') {
            return true;
        }
        if (($actions['stock']['mode'] ?? 'none') !== 'none') {
            return true;
        }
        if (($actions['brand']['mode'] ?? 'none') !== 'none') {
            return true;
        }
        if (($actions['categories']['mode'] ?? 'none') !== 'none') {
            return true;
        }
        if (in_array($actions['status']['is_active'] ?? 'no_change', ['enable', 'disable'], true)) {
            return true;
        }
        if (in_array($actions['status']['featured'] ?? 'no_change', ['enable', 'disable'], true)) {
            return true;
        }
        if (($actions['tags']['mode'] ?? 'none') !== 'none') {
            return true;
        }
        foreach (['meta_title', 'meta_description', 'image_alt', 'short_description'] as $field) {
            if (($actions['text'][$field]['mode'] ?? 'none') !== 'none') {
                return true;
            }
        }

        return false;
    }

    /** @param  list<int|string>  $ids */
    private function expandedCategoryIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $all = collect($ids);
        $pending = $ids;

        while ($pending !== []) {
            $children = Category::query()->whereIn('parent_id', $pending)->pluck('id')->all();
            if ($children === []) {
                break;
            }
            $all = $all->merge($children);
            $pending = $children;
        }

        return $all->unique()->values()->all();
    }

    /** @return list<string> */
    private function parseSkuList(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    /** @param  list<string>  $header */
    private function normalizeCsvHeader(array $header): array
    {
        $aliases = [
            'sku' => 'sku',
            'stok' => 'stock',
            'stock' => 'stock',
            'fiyat' => 'price',
            'price' => 'price',
            'indirimli' => 'compare_at_price',
            'compare_at_price' => 'compare_at_price',
            'compare_price' => 'compare_at_price',
            'marka' => 'brand',
            'brand' => 'brand',
            'kategoriler' => 'categories',
            'categories' => 'categories',
            'yayinda' => 'is_active',
            'is_active' => 'is_active',
            'published' => 'is_active',
            'one_cikan' => 'featured',
            'featured' => 'featured',
            'etiketler' => 'tags',
            'tags' => 'tags',
            'meta_baslik' => 'meta_title',
            'meta_title' => 'meta_title',
            'meta_aciklama' => 'meta_description',
            'meta_description' => 'meta_description',
        ];

        return array_map(function (string $cell) use ($aliases): string {
            $key = Str::slug(mb_strtolower(trim($cell), 'UTF-8'), '_');

            return $aliases[$key] ?? $key;
        }, $header);
    }

    /** @param  list<string|null>  $row */
    private function csvRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function actionsFromCsvRow(array $row): array
    {
        $actions = $this->emptyActions();

        if (isset($row['price']) && trim($row['price']) !== '') {
            $actions['price'] = ['mode' => 'set', 'value' => (float) str_replace(',', '.', $row['price'])];
        }
        if (isset($row['compare_at_price']) && trim($row['compare_at_price']) !== '') {
            $val = trim($row['compare_at_price']);
            $actions['compare'] = $val === '-' || strtolower($val) === 'clear'
                ? ['mode' => 'clear']
                : ['mode' => 'set', 'value' => (float) str_replace(',', '.', $val)];
        }
        if (isset($row['stock']) && trim($row['stock']) !== '') {
            $actions['stock'] = ['mode' => 'set', 'value' => (int) $row['stock']];
        }
        if (isset($row['brand']) && trim($row['brand']) !== '') {
            $brand = Brand::query()->where('name', trim($row['brand']))->orWhere('slug', trim($row['brand']))->first();
            if ($brand) {
                $actions['brand'] = ['mode' => 'set', 'brand_id' => $brand->id];
            }
        }
        if (isset($row['categories']) && trim($row['categories']) !== '') {
            $names = array_map('trim', preg_split('/\s*\|\s*/', $row['categories']) ?: []);
            $ids = Category::query()->whereIn('name', $names)->pluck('id')->all();
            if ($ids !== []) {
                $actions['categories'] = ['mode' => 'sync', 'ids' => $ids];
            }
        }
        if (isset($row['is_active']) && trim($row['is_active']) !== '') {
            $actions['status']['is_active'] = $this->csvBool($row['is_active']) ? 'enable' : 'disable';
        }
        if (isset($row['featured']) && trim($row['featured']) !== '') {
            $actions['status']['featured'] = $this->csvBool($row['featured']) ? 'enable' : 'disable';
        }
        if (isset($row['tags'])) {
            $actions['tags'] = trim($row['tags']) === ''
                ? ['mode' => 'clear']
                : ['mode' => 'set', 'value' => $row['tags']];
        }
        if (isset($row['meta_title']) && trim($row['meta_title']) !== '') {
            $actions['text']['meta_title'] = ['mode' => 'set', 'value' => $row['meta_title']];
        }
        if (isset($row['meta_description']) && trim($row['meta_description']) !== '') {
            $actions['text']['meta_description'] = ['mode' => 'set', 'value' => $row['meta_description']];
        }

        return $this->hasAnyAction($actions) ? $actions : [];
    }

    private function csvBool(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'yes', 'true', 'evet', 'y', 'on'], true);
    }

    /** @return array<string, mixed> */
    public function emptyActions(): array
    {
        return [
            'price' => ['mode' => 'none', 'value' => 0],
            'compare' => ['mode' => 'none', 'value' => 0],
            'stock' => ['mode' => 'none', 'value' => 0],
            'brand' => ['mode' => 'none', 'brand_id' => null],
            'categories' => ['mode' => 'none', 'ids' => []],
            'status' => ['is_active' => 'no_change', 'featured' => 'no_change'],
            'tags' => ['mode' => 'none', 'value' => ''],
            'text' => [
                'meta_title' => ['mode' => 'none', 'value' => ''],
                'meta_description' => ['mode' => 'none', 'value' => ''],
                'image_alt' => ['mode' => 'none', 'value' => ''],
                'short_description' => ['mode' => 'none', 'value' => ''],
            ],
        ];
    }
}
