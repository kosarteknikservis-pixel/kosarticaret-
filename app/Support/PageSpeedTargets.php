<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\URL;

class PageSpeedTargets
{
    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public static function resolve(): array
    {
        $targets = [
            [
                'key' => 'home',
                'label' => 'Ana sayfa',
                'url' => URL::to(route('home', [], false)),
            ],
            [
                'key' => 'products_index',
                'label' => 'Tüm ürünler',
                'url' => URL::to(route('products.index', [], false)),
            ],
        ];

        $category = Category::query()
            ->where('active', true)
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->first();

        if ($category) {
            $targets[] = [
                'key' => 'category',
                'label' => 'Kategori: '.$category->name,
                'url' => URL::to(route('categories.show', $category, false)),
            ];
        }

        $product = Product::query()
            ->active()
            ->whereNotNull('image')
            ->orderByDesc('featured')
            ->latest('id')
            ->first();

        if ($product) {
            $targets[] = [
                'key' => 'product',
                'label' => 'Ürün detay',
                'url' => URL::to(route('products.show', $product, false)),
            ];
        }

        $blogPost = BlogPost::query()
            ->where('published', true)
            ->latest('published_at')
            ->first();

        if ($blogPost) {
            $targets[] = [
                'key' => 'blog',
                'label' => 'Blog yazısı',
                'url' => URL::to(route('blog.show', $blogPost, false)),
            ];
        }

        return $targets;
    }
}
