<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;

class PageSpeedTargets
{
    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public static function resolve(): array
    {
        if (! PageSpeedAuditUrl::isConfigured()) {
            return [];
        }

        $targets = [];

        if ($home = PageSpeedAuditUrl::route('home')) {
            $targets[] = [
                'key' => 'home',
                'label' => 'Ana sayfa',
                'url' => $home,
            ];
        }

        if ($productsIndex = PageSpeedAuditUrl::route('products.index')) {
            $targets[] = [
                'key' => 'products_index',
                'label' => 'Tüm ürünler',
                'url' => $productsIndex,
            ];
        }

        $category = Category::query()
            ->where('active', true)
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->first();

        if ($category && ($categoryUrl = PageSpeedAuditUrl::route('categories.show', $category))) {
            $targets[] = [
                'key' => 'category',
                'label' => 'Kategori: '.$category->name,
                'url' => $categoryUrl,
            ];
        }

        $product = Product::query()
            ->active()
            ->whereNotNull('image')
            ->orderByDesc('featured')
            ->latest('id')
            ->first();

        if ($product && ($productUrl = PageSpeedAuditUrl::route('products.show', $product))) {
            $targets[] = [
                'key' => 'product',
                'label' => 'Ürün detay',
                'url' => $productUrl,
            ];
        }

        $blogPost = BlogPost::query()
            ->where('published', true)
            ->latest('published_at')
            ->first();

        if ($blogPost && ($blogUrl = PageSpeedAuditUrl::route('blog.show', $blogPost))) {
            $targets[] = [
                'key' => 'blog',
                'label' => 'Blog yazısı',
                'url' => $blogUrl,
            ];
        }

        return $targets;
    }
}
