<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LlmsTxt
{
    public static function cacheKey(): string
    {
        return 'seo.llms.txt';
    }

    public static function render(): string
    {
        $cacheSeconds = (int) config('seo.robots_cache_seconds', 86400);

        return Cache::remember(self::cacheKey(), $cacheSeconds, fn (): string => self::body());
    }

    private static function body(): string
    {
        $name = SiteName::get();
        $url = Seo::siteUrl();
        $lines = [
            '# '.$name,
            '',
            '> Endüstriyel su pompası, hidrofor, dalgıç pompa ve sanayi vantilatörü tedarikçisi.',
            '> Fiyat ve stok ürün sayfalarındadır. Seçim için teknik parametre (debi, basma yüksekliği, güç) kullanılır.',
            '',
            '## Site',
            '- [Ana sayfa]('.$url.'/)',
            '- [Ürünler]('.route('products.index').')',
            '- [Kategoriler]('.route('categories.index').')',
            '- [Blog]('.route('blog.index').')',
            '- [Markalar]('.route('brands.index').')',
            '- [Site haritası]('.route('sitemap.html').')',
            '- [İletişim]('.route('contact.show').')',
            '- [XML sitemap]('.route('sitemap').')',
        ];

        if (PumpSelectorUiConfig::isEnabled()) {
            $lines[] = '- [Pompa seçici]('.route('pump-selector.show').')';
        }

        $lines[] = '';
        $lines[] = '## Kategoriler (ana gruplar)';

        Category::query()
            ->where('active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'parent_id'])
            ->each(function (Category $category) use (&$lines): void {
                $lines[] = '- ['.$category->name.']('.$category->storefrontUrl().')';
            });

        $featuredCategories = self::featuredCategories();
        if ($featuredCategories->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Önemli alt kategoriler';
            foreach ($featuredCategories as $category) {
                $lines[] = '- ['.$category->name.']('.$category->storefrontUrl().')';
            }
        }

        $featuredBlog = self::featuredBlogPosts();
        if ($featuredBlog->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Rehberler (blog)';
            foreach ($featuredBlog as $post) {
                $lines[] = '- ['.$post->title.']('.route('blog.show', $post).')';
            }
        }

        $lines[] = '';
        $lines[] = '## Markalar';

        Brand::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(40)
            ->get(['slug', 'name'])
            ->each(function (Brand $brand) use (&$lines): void {
                $lines[] = '- ['.$brand->name.']('.route('brands.show', $brand).')';
            });

        $pages = Page::query()->where('published', true)->orderBy('sort_order')->get(['slug', 'title']);
        if ($pages->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Sayfalar';
            foreach ($pages as $page) {
                $lines[] = '- ['.$page->title.']('.route('pages.show', $page).')';
            }
        }

        $lines[] = '';
        $lines[] = '## Not';
        $lines[] = 'Sepet, ödeme, hesap ve arama URL’leri taranmamalı. Ürün teknik özellikleri HTML tabloda yer alır; PDF ikincildir.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /** @return Collection<int, Category> */
    private static function featuredCategories(): Collection
    {
        $paths = config('seo.llms.featured_category_paths', []);
        if (! is_array($paths) || $paths === []) {
            return collect();
        }

        $categories = Category::query()
            ->where('active', true)
            ->with('parent.parent.parent')
            ->get();

        return collect($paths)
            ->map(function (string $path) use ($categories): ?Category {
                return $categories->first(
                    fn (Category $category) => $category->nestedSlugPath() === $path
                );
            })
            ->filter()
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, BlogPost> */
    private static function featuredBlogPosts(): Collection
    {
        $slugs = config('seo.llms.featured_blog_slugs', []);
        $limit = max(1, (int) config('seo.llms.recent_blog_limit', 10));

        $featured = collect();
        if (is_array($slugs) && $slugs !== []) {
            $featured = BlogPost::published()
                ->whereIn('slug', $slugs)
                ->get(['id', 'slug', 'title', 'published_at'])
                ->sortBy(fn (BlogPost $post) => array_search($post->slug, $slugs, true))
                ->values();
        }

        $recent = BlogPost::published()
            ->latest('published_at')
            ->limit($limit)
            ->get(['id', 'slug', 'title', 'published_at']);

        return $featured
            ->concat($recent)
            ->unique('id')
            ->take(20)
            ->values();
    }
}
