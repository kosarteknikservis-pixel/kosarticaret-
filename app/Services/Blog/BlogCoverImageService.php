<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ImageVariant;
use App\Support\ProductImageAlt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogCoverImageService
{
    /** @var array<string, string>|null */
    private static ?array $manifestSeriesBySlug = null;

    /** @var array<string, list<string>> */
    private const SERIES_CATEGORY_SLUGS = [
        'hidrofor' => ['hidroforlar', 'hidrofor-sistemleri', 'ev-tipi-hidroforlar', 'hidrofor-grubu'],
        'dalgic-pompa' => ['dalgic-pompalar', 'derin-kuyu-dalgic-pompa', 'temiz-su-dalgic-pompalar'],
        'su-pompasi' => ['su-pompalari', 'santrifuj-pompalar', 'jet-pompalar', 'kademeli-pompalar'],
        'vantilator' => ['vantilatorler', 'sanayi-tipi-vantilatorler', 'duvar-tipi-vantilatorler'],
        'sirkulasyon' => ['sirkulasyon-pompalari', 'inline-sirkulasyon-pompalari', 'flansli-sirkulasyon-pompalari'],
        'yangin' => ['yangin-pompalari', 'ozel-amacli-pompalar'],
        'ozel-amacli' => ['ozel-amacli-pompalar', 'on-filtreli-havuz-pompasi', 'foseptik-tahliye-cihazi'],
        'marka' => ['su-pompalari', 'hidroforlar', 'dalgic-pompalar'],
    ];

    /** @var array<string, list<string>> */
    private const SLUG_SEARCH_TERMS = [
        'havuz' => ['havuz'],
        'foseptik' => ['foseptik', 'flatör'],
        'drenaj' => ['drenaj', 'kirli su'],
        'kuyu' => ['kuyu', 'dalgıç', 'dalgic'],
        'keson' => ['keson', 'kuyu'],
        'solar' => ['solar'],
        'yangin' => ['yangın', 'yangin'],
        'jockey' => ['jockey'],
        'vantilator' => ['vantilatör', 'vantilator'],
        'sirkulasyon' => ['sirkülasyon', 'sirkulasyon'],
        'jet' => ['jet pompa', 'jet'],
        'tarimsal' => ['tarım', 'tarim', 'sulama'],
        'preferikal' => ['perferit', 'preferikal'],
        'marka' => ['hidrofor', 'marka', 'markalar'],
        'hidrofor' => ['hidrofor'],
        'dalgic' => ['dalgic', 'dalgıç'],
        'sumak' => ['sumak'],
        'winpo' => ['winpo'],
        'kaysu' => ['kaysu'],
        'inverter' => ['inverter'],
        'dizel' => ['dizel', 'motopomp'],
        'hidromat' => ['hidromat'],
    ];

    public function assign(BlogPost $post, bool $force = false): bool
    {
        if (filled($post->image) && ! $force) {
            return false;
        }

        $product = $this->resolveProduct($post);
        if (! $product || blank($product->image)) {
            return false;
        }

        $path = $this->copyToBlogStorage($post, $product);
        if ($path === null) {
            return false;
        }

        $post->update([
            'image' => $path,
            'image_alt' => filled($post->image_alt)
                ? $post->image_alt
                : $this->defaultAlt($post, $product),
        ]);

        return true;
    }

    public function previewProduct(BlogPost $post): ?Product
    {
        return $this->resolveProduct($post);
    }

    private function resolveProduct(BlogPost $post): ?Product
    {
        return $this->productFromSlugHints($post)
            ?? $this->productFromSeries($post)
            ?? $this->productFromContent((string) $post->content);
    }

    private function productFromContent(string $html): ?Product
    {
        if (! preg_match('/\/urun\/([a-z0-9\-]+)/i', $html, $matches)) {
            return null;
        }

        return $this->baseProductQuery()
            ->where('slug', Str::slug($matches[1]))
            ->first();
    }

    private function productFromSlugHints(BlogPost $post): ?Product
    {
        $slug = $post->slug;
        $terms = [];

        foreach (self::SLUG_SEARCH_TERMS as $needle => $searchTerms) {
            if (str_contains($slug, $needle)) {
                $terms = array_merge($terms, $searchTerms);
            }
        }

        if ($terms === []) {
            return null;
        }

        $query = $this->baseProductQuery();

        $query->where(function ($builder) use ($terms): void {
            foreach (array_unique($terms) as $term) {
                $builder->orWhere('name', 'like', '%'.$term.'%');
            }
        });

        return $this->pickFromPool($query, $slug);
    }

    private function productFromSeries(BlogPost $post): ?Product
    {
        $series = $this->seriesForSlug($post->slug);
        if ($series === null) {
            return null;
        }

        if ($series === 'marka') {
            $brandProduct = $this->productForBrandSlug($post->slug);
            if ($brandProduct) {
                return $brandProduct;
            }

            return $this->productFromCategorySlugs(
                ['hidroforlar', 'hidrofor-sistemleri', 'su-pompalari'],
                $post->slug,
            );
        }

        return $this->productFromCategorySlugs(
            self::SERIES_CATEGORY_SLUGS[$series] ?? [],
            $post->slug,
        );
    }

    /** @param  list<string>  $categorySlugs */
    private function productFromCategorySlugs(array $categorySlugs, string $seed): ?Product
    {
        $categoryIds = $this->categoryIdsForSlugs($categorySlugs);
        if ($categoryIds === []) {
            return null;
        }

        return $this->pickFromPool(
            $this->baseProductQuery()
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds)),
            $seed,
        );
    }

    private function productForBrandSlug(string $blogSlug): ?Product
    {
        foreach (['pedrollo', 'sumak', 'winpo', 'kaysu'] as $brandSlug) {
            if (! str_contains($blogSlug, $brandSlug)) {
                continue;
            }

            $brand = Brand::query()->where('slug', $brandSlug)->where('active', true)->first();
            if (! $brand) {
                continue;
            }

            return $this->pickFromPool(
                $this->baseProductQuery()->where('brand_id', $brand->id),
                $blogSlug,
            );
        }

        return null;
    }

  private function pickFromPool($query, string $seed): ?Product
    {
        $pool = $query
            ->orderByDesc('featured')
            ->orderByDesc('stock')
            ->orderBy('id')
            ->limit(24)
            ->get();

        if ($pool->isEmpty()) {
            return null;
        }

        $index = abs(crc32($seed)) % $pool->count();

        return $pool->get($index);
    }

    /** @param  list<string>  $slugs */
    private function categoryIdsForSlugs(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return Category::query()
            ->where('active', true)
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();
    }

    private function seriesForSlug(string $slug): ?string
    {
        $map = self::manifestSeriesBySlug();

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        if (str_contains($slug, 'hidrofor') || str_contains($slug, 'hidromat')) {
            return 'hidrofor';
        }

        if (str_contains($slug, 'dalgic') || str_contains($slug, 'dalgıç')) {
            return 'dalgic-pompa';
        }

        if (str_contains($slug, 'vantilator') || str_contains($slug, 'vantilatör')) {
            return 'vantilator';
        }

        if (str_contains($slug, 'sirkulasyon')) {
            return 'sirkulasyon';
        }

        if (str_contains($slug, 'yangin') || str_contains($slug, 'yangın')) {
            return 'yangin';
        }

        if (str_contains($slug, 'marka') || str_contains($slug, 'pedrollo') || str_contains($slug, 'sumak')) {
            return 'marka';
        }

        return null;
    }

    /** @return array<string, string> */
    private static function manifestSeriesBySlug(): array
    {
        if (self::$manifestSeriesBySlug !== null) {
            return self::$manifestSeriesBySlug;
        }

        self::$manifestSeriesBySlug = [];

        $manifestPath = base_path('database/blog-queue/manifest.json');
        if (! File::exists($manifestPath)) {
            return self::$manifestSeriesBySlug;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        foreach (($manifest['posts'] ?? []) as $entry) {
            $file = (string) ($entry['file'] ?? '');
            $series = (string) ($entry['series'] ?? '');
            if ($file === '' || $series === '') {
                continue;
            }

            $jsonPath = base_path('database/blog-queue/'.$file);
            if (! File::exists($jsonPath)) {
                continue;
            }

            $payload = json_decode(File::get($jsonPath), true);
            $postSlug = Str::slug((string) ($payload['posts'][0]['slug'] ?? ''));
            if ($postSlug !== '') {
                self::$manifestSeriesBySlug[$postSlug] = $series;
            }
        }

        return self::$manifestSeriesBySlug;
    }

    private function baseProductQuery()
    {
        return Product::query()
            ->active()
            ->whereNotNull('image')
            ->where('image', '!=', '');
    }

    private function copyToBlogStorage(BlogPost $post, Product $product): ?string
    {
        $source = (string) $product->image;
        if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
            return null;
        }

        if (! Storage::disk('public')->exists($source)) {
            return null;
        }

        Storage::disk('public')->makeDirectory('blog/covers');

        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION) ?: 'jpg');
        $dest = 'blog/covers/'.$post->slug.'.'.$ext;

        if (Storage::disk('public')->exists($dest)) {
            Storage::disk('public')->delete($dest);
        }

        Storage::disk('public')->copy($source, $dest);
        ImageVariant::optimizeOriginal($dest, 'blog');
        ImageVariant::generate($dest, ImageVariant::presetsFor('blog'));

        return $dest;
    }

    private function defaultAlt(BlogPost $post, Product $product): string
    {
        $product->loadMissing('brand');

        return Str::limit(
            ProductImageAlt::generate($product->name, $product->brand?->name).' — '.$post->title,
            255,
            '',
        );
    }

    /** @return Collection<int, BlogPost> */
    public function postsNeedingCover(): Collection
    {
        return BlogPost::query()
            ->published()
            ->where(function ($q): void {
                $q->whereNull('image')->orWhere('image', '');
            })
            ->orderBy('published_at')
            ->get();
    }
}
