<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SitemapGenerator
{
    public const CHUNK_SIZE = 45000;

    public static function stylesheetHref(): string
    {
        return Seo::absolute('/sitemap.xsl');
    }

    /** @return list<array{loc: string, lastmod?: string, priority?: string}> */
    public static function staticUrls(): array
    {
        $homeLastmod = self::latestAtom([
            Product::query()->active()->max('updated_at'),
            BlogPost::published()->max('updated_at'),
            Category::query()->where('active', true)->max('updated_at'),
        ]);

        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'lastmod' => $homeLastmod],
            ['loc' => route('products.index'), 'priority' => '0.9', 'lastmod' => self::atom(Product::query()->active()->max('updated_at'))],
            ['loc' => route('categories.index'), 'priority' => '0.8', 'lastmod' => self::atom(Category::query()->where('active', true)->max('updated_at'))],
            ['loc' => route('brands.index'), 'priority' => '0.8', 'lastmod' => self::atom(Brand::query()->where('active', true)->max('updated_at'))],
            ['loc' => route('blog.index'), 'priority' => '0.7', 'lastmod' => self::atom(BlogPost::published()->max('updated_at'))],
            ['loc' => route('contact.show'), 'priority' => '0.6'],
            ['loc' => route('sitemap.html'), 'priority' => '0.5', 'lastmod' => $homeLastmod],
        ];

        if (PumpSelectorUiConfig::isEnabled()) {
            $urls[] = ['loc' => route('pump-selector.show'), 'priority' => '0.8'];
        }

        return array_map(function (array $url): array {
            return array_filter($url, fn ($value) => $value !== null && $value !== '');
        }, $urls);
    }

    public static function productCount(): int
    {
        return Product::query()->active()->count();
    }

    public static function usesIndex(): bool
    {
        return true;
    }

    /** @return list<array{loc: string, lastmod?: string}> */
    public static function indexEntries(): array
    {
        $productLastmod = self::atom(Product::query()->active()->max('updated_at'));

        $entries = [
            [
                'loc' => route('sitemap.chunk', ['chunk' => 'static']),
                'lastmod' => self::latestAtom([
                    Product::query()->active()->max('updated_at'),
                    BlogPost::published()->max('updated_at'),
                    Page::query()->where('published', true)->max('updated_at'),
                ]),
            ],
            [
                'loc' => route('sitemap.images'),
                'lastmod' => $productLastmod,
            ],
            [
                'loc' => route('sitemap.chunk', ['chunk' => 'categories']),
                'lastmod' => self::atom(Category::query()->where('active', true)->max('updated_at')),
            ],
            [
                'loc' => route('sitemap.chunk', ['chunk' => 'brands']),
                'lastmod' => self::atom(Brand::query()->where('active', true)->max('updated_at')),
            ],
            [
                'loc' => route('sitemap.chunk', ['chunk' => 'blog']),
                'lastmod' => self::atom(BlogPost::published()->max('updated_at')),
            ],
            [
                'loc' => route('sitemap.chunk', ['chunk' => 'pages']),
                'lastmod' => self::atom(Page::query()->where('published', true)->max('updated_at')),
            ],
        ];

        $productPages = (int) ceil(max(1, self::productCount()) / self::CHUNK_SIZE);
        for ($page = 1; $page <= $productPages; $page++) {
            $entries[] = [
                'loc' => route('sitemap.chunk', ['chunk' => 'products-'.$page]),
                'lastmod' => $productLastmod,
            ];
        }

        return array_map(function (array $entry): array {
            return array_filter($entry, fn ($value) => $value !== null && $value !== '');
        }, $entries);
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
            ->where(function ($query) {
                $query->whereHas('activeChildren')
                    ->orWhereHas('products', fn ($products) => $products->where('products.is_active', true));
            })
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

        InternalLinking::indexableTagHubs()->each(function (array $hub) use ($urls): void {
            $urls->push([
                'loc' => route('blog.tag', $hub['slug']),
                'lastmod' => $hub['lastmod'],
                'priority' => '0.45',
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

    private static function atom(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toAtomString();
    }

    /** @param  list<mixed>  $values */
    private static function latestAtom(array $values): ?string
    {
        $filtered = array_values(array_filter($values, fn ($value) => $value !== null && $value !== ''));

        if ($filtered === []) {
            return null;
        }

        return self::atom(max($filtered));
    }
}
