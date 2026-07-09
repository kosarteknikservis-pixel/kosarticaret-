<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class ImageSitemapGenerator
{
    /**
     * @return list<array{loc: string, images: list<array{loc: string, title?: string, caption?: string}>}>
     */
    public static function allEntries(): array
    {
        return self::productEntries()
            ->merge(self::categoryEntries())
            ->merge(self::blogEntries())
            ->merge(self::brandEntries())
            ->values()
            ->all();
    }

    /** @return Collection<int, array{loc: string, images: list<array{loc: string, title?: string, caption?: string}>}> */
    private static function productEntries(): Collection
    {
        $entries = collect();

        Product::query()
            ->active()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->with(['images:id,product_id,path,alt'])
            ->select(['id', 'slug', 'name', 'image', 'image_alt', 'updated_at'])
            ->orderBy('id')
            ->each(function (Product $product) use ($entries): void {
                $images = self::productImageNodes($product);
                if ($images === []) {
                    return;
                }

                $entries->push([
                    'loc' => route('products.show', $product),
                    'images' => $images,
                ]);
            });

        return $entries;
    }

    /** @return list<array{loc: string, title?: string, caption?: string}> */
    private static function productImageNodes(Product $product): array
    {
        $nodes = [];
        $coverAlt = $product->imageAltText();

        if ($loc = self::imageLoc($product->image, 'product-pdp')) {
            $nodes[] = self::imageNode($loc, $coverAlt);
        }

        foreach ($product->images as $image) {
            if ($loc = self::imageLoc($image->path, 'product-pdp')) {
                $alt = filled($image->alt) ? (string) $image->alt : $coverAlt;
                $nodes[] = self::imageNode($loc, $alt);
            }
        }

        return self::uniqueImageNodes($nodes);
    }

    /** @return Collection<int, array{loc: string, images: list<array{loc: string, title?: string, caption?: string}>}> */
    private static function categoryEntries(): Collection
    {
        $entries = collect();

        Category::query()
            ->where('active', true)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->select(['id', 'slug', 'name', 'image', 'parent_id'])
            ->each(function (Category $category) use ($entries): void {
                if ($loc = self::imageLoc($category->image, 'category-card')) {
                    $entries->push([
                        'loc' => $category->storefrontUrl(),
                        'images' => [self::imageNode($loc, $category->name)],
                    ]);
                }
            });

        return $entries;
    }

    /** @return Collection<int, array{loc: string, images: list<array{loc: string, title?: string, caption?: string}>}> */
    private static function blogEntries(): Collection
    {
        $entries = collect();

        BlogPost::published()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->select(['id', 'slug', 'title', 'image', 'image_alt'])
            ->each(function (BlogPost $post) use ($entries): void {
                if ($loc = self::imageLoc($post->image, 'blog-card')) {
                    $alt = filled($post->image_alt) ? (string) $post->image_alt : (string) $post->title;
                    $entries->push([
                        'loc' => route('blog.show', $post),
                        'images' => [self::imageNode($loc, $alt)],
                    ]);
                }
            });

        return $entries;
    }

    /** @return Collection<int, array{loc: string, images: list<array{loc: string, title?: string, caption?: string}>}> */
    private static function brandEntries(): Collection
    {
        $entries = collect();

        Brand::query()
            ->where('active', true)
            ->whereNotNull('logo')
            ->where('logo', '!=', '')
            ->select(['id', 'slug', 'name', 'logo'])
            ->each(function (Brand $brand) use ($entries): void {
                if ($loc = self::imageLoc($brand->logo, 'brand-logo')) {
                    $entries->push([
                        'loc' => route('brands.show', $brand),
                        'images' => [self::imageNode($loc, $brand->name.' logo')],
                    ]);
                }
            });

        return $entries;
    }

    private static function imageLoc(?string $path, string $variant): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $url = ImageVariant::url($path, $variant) ?? ImageVariant::url($path);

        return Seo::absoluteAssetUrl($url);
    }

    /** @return array{loc: string, title: string, caption: string} */
    private static function imageNode(string $loc, string $alt): array
    {
        $alt = trim($alt);

        return [
            'loc' => $loc,
            'title' => $alt,
            'caption' => $alt,
        ];
    }

    /**
     * @param  list<array{loc: string, title?: string, caption?: string}>  $nodes
     * @return list<array{loc: string, title?: string, caption?: string}>
     */
    private static function uniqueImageNodes(array $nodes): array
    {
        $seen = [];

        return array_values(array_filter($nodes, function (array $node) use (&$seen): bool {
            if (isset($seen[$node['loc']])) {
                return false;
            }

            $seen[$node['loc']] = true;

            return true;
        }));
    }
}
