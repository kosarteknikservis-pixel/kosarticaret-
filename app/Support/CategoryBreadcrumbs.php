<?php

namespace App\Support;

use App\Models\Category;

class CategoryBreadcrumbs
{
    /**
     * @return list<array{name: string, url?: string}>
     */
    public static function for(Category $category): array
    {
        $crumbs = [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.categories'), 'url' => route('categories.index')],
        ];

        $chain = $category->ancestorsAndSelf();
        $lastIndex = count($chain) - 1;

        foreach ($chain as $index => $node) {
            $crumbs[] = [
                'name' => $node->name,
                'url' => $index < $lastIndex ? $node->storefrontUrl() : null,
            ];
        }

        return $crumbs;
    }
}
