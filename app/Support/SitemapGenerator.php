<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection;

class SitemapGenerator
{
    public const CHUNK_SIZE = 45000;

    /** @return list<array{loc: string, lastmod?: string, priority?: string}> */
    public static function staticUrls(): array
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('products.index'), 'priority' => '0.9'],
            ['loc' => route('categories.index'), 'priority' => '0.8'],
            ['loc' => route('brands.index'), 'priority' => '0.8'],
            ['loc' => route('blog.index'), 'priority' => '0.7'],
            ['loc' => route('contact.show'), 'priority' => '0.6'],
        ];

        if (PumpSelectorUiConfig::isEnabled()) {
            $urls[] = ['loc' => route('pump-selector.show'), 'priority' => '0.8'];
        }

        return $urls;
    }

    public static function productCount(): int
    {
        return Product::query()->active()->count();
    }

    public static function usesIndex(): bool
    {
        return self::productCount() > self::CHUNK_SIZE;
    }

    /** @return list<array{loc: string}> */
    public static function indexEntries(): array
    {
        $entries = [
            ['loc' => route('sitemap.chunk', ['chunk' => 'static'])],
            ['loc' => route('sitemap.chunk', ['chunk' => 'categories'])],
            ['loc' => route('sitemap.chunk', ['chunk' => 'brands'])],
            ['loc' => route('sitemap.chunk', ['chunk' => 'blog'])],
            ['loc' => route('sitemap.chunk', ['chunk' => 'pages'])],
        ];

        $productPages = (int) ceil(max(1, self::productCount()) / self::CHUNK_SIZE);
        for ($page = 1; $page <= $productPages; $page++) {
            $entries[] = ['loc' => route('sitemap.chunk', ['chunk' => 'products-'.$page])];
        }

        return $entries;
    }

    /** @return list<array{loc: string, lastmod?: string, priority?: string}> */
    public static function allUrls(): array
    {
        return collect(self::staticUrls())
            ->merge(self::productUrls())
            ->merge(self::categoryUrls())
            ->merge(self::brandUrls())
            ->merge(self::blogUrls())
            ->merge(self::pageUrls())
            ->values()
            ->all();
    }

    /** @return Collection<int, array{loc: string, lastmod?: string, priority?: string}> */
    public static function chunkUrls(string $chunk): Collection
    {
        if ($chunk === 'static') {
            return collect(self::staticUrls());
        }

        if ($chunk === 'categories') {
            return self::categoryUrls();
        }

        if ($chunk === 'brands') {
            return self::brandUrls();
        }

        if ($chunk === 'blog') {
            return self::blogUrls();
        }

        if ($chunk === 'pages') {
            return self::pageUrls();
        }

        if (preg_match('/^products-(\d+)$/', $chunk, $matches) === 1) {
            return self::productUrls((int) $matches[1]);
        }

        return collect();
    }

    /** @return Collection<int, array{loc: string, lastmod?: string, priority?: string}> */
    private static function productUrls(int $page = 1): Collection
    {
        $urls = collect();
        $offset = ($page - 1) * self::CHUNK_SIZE;

        Product::query()
            ->active()
            ->select('slug', 'updated_at')
            ->orderBy('id')
            ->skip($offset)
            ->take(self::CHUNK_SIZE)
            ->each(function (Product $product) use ($urls): void {
                $urls->push([
                    'loc' => route('products.show', $product),
                    'lastmod' => $product->updated_at->toAtomString(),
                    'priority' => '0.8',
                ]);
            });

        return $urls;
    }

    /** @return Collection<int, array{loc: string, lastmod?: string, priority?: string}> */
    private static function categoryUrls(): Collection
    {
        $urls = collect();

        Category::query()
            ->where('active', true)
            ->select('id', 'slug', 'parent_id', 'updated_at')
            ->each(function (Category $category) use ($urls): void {
                $urls->push([
                    'loc' => $category->storefrontUrl(),
                    'lastmod' => $category->updated_at->toAtomString(),
                    'priority' => '0.7',
                ]);
            });

        return $urls;
    }

    /** @return Collection<int, array{loc: string, lastmod?: string, priority?: string}> */
    private static function brandUrls(): Collection
    {
        $urls = collect();

        Brand::query()
            ->where('active', true)
            ->select('slug', 'updated_at')
            ->each(function (Brand $brand) use ($urls): void {
                $urls->push([
                    'loc' => route('brands.show', $brand),
                    'lastmod' => $brand->updated_at->toAtomString(),
                    'priority' => '0.7',
                ]);
            });

        return $urls;
    }

    /** @return Collection<int, array{loc: string, lastmod?: string, priority?: string}> */
    private static function blogUrls(): Collection
    {
        $urls = collect();

        BlogPost::published()
            ->select('slug', 'updated_at')
            ->each(function (BlogPost $post) use ($urls): void {
                $urls->push([
                    'loc' => route('blog.show', $post),
                    'lastmod' => $post->updated_at->toAtomString(),
                    'priority' => '0.6',
                ]);
            });

        return $urls;
    }

    /** @return Collection<int, array{loc: string, lastmod?: string, priority?: string}> */
    private static function pageUrls(): Collection
    {
        $urls = collect();

        Page::query()
            ->where('published', true)
            ->select('slug', 'updated_at')
            ->each(function (Page $page) use ($urls): void {
                $urls->push([
                    'loc' => route('pages.show', $page),
                    'lastmod' => $page->updated_at->toAtomString(),
                    'priority' => '0.5',
                ]);
            });

        return $urls;
    }
}
