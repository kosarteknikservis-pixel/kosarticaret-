<?php

namespace App\Concerns;

use App\Models\Category;
use App\Support\SlugRedirectRecorder;
use Illuminate\Database\Eloquent\Model;

trait RecordsSlugRedirects
{
    public static function bootRecordsSlugRedirects(): void
    {
        static::updated(function (Model $model): void {
            if ($model instanceof Category) {
                self::recordCategoryRedirects($model);

                return;
            }

            if (! $model->wasChanged('slug')) {
                return;
            }

            /** @var Model&object{storefrontPathForSlug: callable} $model */
            $from = $model->storefrontPathForSlug((string) $model->getOriginal('slug'));
            $to = $model->storefrontPathForSlug((string) $model->slug);

            SlugRedirectRecorder::record($from, $to, self::slugRedirectSource($model));
        });
    }

    private static function recordCategoryRedirects(Category $category): void
    {
        if (! $category->wasChanged('slug') && ! $category->wasChanged('parent_id')) {
            return;
        }

        $oldSlug = (string) $category->getOriginal('slug');
        $oldParentId = $category->getOriginal('parent_id');
        $oldPath = self::categoryPathFromParentAndSlug(
            $oldParentId !== null ? (int) $oldParentId : null,
            $oldSlug,
        );
        $newPath = '/kategoriler/'.$category->nestedSlugPath();

        SlugRedirectRecorder::record($oldPath, $newPath, 'category');
        self::recordCategoryDescendantRedirects($category, $oldPath, $newPath);
    }

    private static function categoryPathFromParentAndSlug(?int $parentId, string $slug): string
    {
        if ($parentId === null) {
            return '/kategoriler/'.$slug;
        }

        $parent = Category::query()->find($parentId);
        if ($parent === null) {
            return '/kategoriler/'.$slug;
        }

        return '/kategoriler/'.$parent->nestedSlugPath().'/'.$slug;
    }

    private static function recordCategoryDescendantRedirects(Category $category, string $oldBase, string $newBase): void
    {
        $walk = function (Category $node, string $oldPrefix, string $newPrefix) use (&$walk): void {
            $node->loadMissing(['children' => fn ($q) => $q->orderBy('sort_order')]);

            foreach ($node->children as $child) {
                $old = $oldPrefix.'/'.$child->slug;
                $new = $newPrefix.'/'.$child->slug;
                SlugRedirectRecorder::record($old, $new, 'category');
                $walk($child, $old, $new);
            }
        };

        $walk($category, $oldBase, $newBase);
    }

    private static function slugRedirectSource(Model $model): string
    {
        return match ($model::class) {
            \App\Models\Product::class => 'product',
            \App\Models\Brand::class => 'brand',
            \App\Models\BlogPost::class => 'blog_post',
            \App\Models\Page::class => 'page',
            \App\Models\Category::class => 'category',
            default => class_basename($model),
        };
    }
}
