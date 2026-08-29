<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InternalLinking
{
    public static function tagSlug(string $tag): string
    {
        $slug = Str::slug($tag, '-', 'tr');

        return $slug !== '' ? $slug : 'etiket';
    }

    public static function tagUrl(string $tag): string
    {
        return route('blog.tag', ['tag' => self::tagSlug($tag)]);
    }

    /**
     * @param  Collection<int, BlogPost>  $posts
     */
    public static function tagLabel(Collection $posts, string $slug): string
    {
        foreach ($posts as $post) {
            foreach ($post->tags ?? [] as $tag) {
                if (is_string($tag) && self::tagSlug($tag) === $slug) {
                    return $tag;
                }
            }
        }

        return str_replace('-', ' ', $slug);
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public static function postsForTagSlug(string $slug): Collection
    {
        return BlogPost::published()
            ->get()
            ->filter(function (BlogPost $post) use ($slug) {
                return collect($post->tags ?? [])->contains(
                    fn ($tag) => is_string($tag) && self::tagSlug($tag) === $slug
                );
            })
            ->values();
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public static function relatedPosts(BlogPost $post, int $limit = 3): Collection
    {
        $tagSlugs = collect($post->tags ?? [])
            ->filter(fn ($tag) => is_string($tag) && $tag !== '')
            ->map(fn (string $tag) => self::tagSlug($tag))
            ->unique()
            ->values();

        $titleTokens = self::significantTokens($post->title);

        return BlogPost::published()
            ->where('id', '!=', $post->id)
            ->limit(40)
            ->get()
            ->map(function (BlogPost $candidate) use ($tagSlugs, $titleTokens) {
                $candidateTags = collect($candidate->tags ?? [])
                    ->filter(fn ($tag) => is_string($tag) && $tag !== '')
                    ->map(fn (string $tag) => self::tagSlug($tag));

                $score = $candidateTags->intersect($tagSlugs)->count() * 10;
                $score += count(array_intersect($titleTokens, self::significantTokens($candidate->title))) * 3;

                return ['post' => $candidate, 'score' => $score];
            })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('post')
            ->values();
    }

    /**
     * @return Collection<int, array{slug: string, label: string, count: int, lastmod: string}>
     */
    public static function indexableTagHubs(int $minPosts = 2): Collection
    {
        $hubs = [];

        BlogPost::published()->get(['tags', 'updated_at'])->each(function (BlogPost $post) use (&$hubs): void {
            foreach ($post->tags ?? [] as $tag) {
                if (! is_string($tag) || $tag === '') {
                    continue;
                }

                $slug = self::tagSlug($tag);
                if (! isset($hubs[$slug])) {
                    $hubs[$slug] = [
                        'slug' => $slug,
                        'label' => $tag,
                        'count' => 0,
                        'lastmod' => $post->updated_at?->toAtomString() ?? now()->toAtomString(),
                    ];
                }

                $hubs[$slug]['count']++;
                $atom = $post->updated_at?->toAtomString();
                if ($atom && $atom > $hubs[$slug]['lastmod']) {
                    $hubs[$slug]['lastmod'] = $atom;
                }
            }
        });

        return collect($hubs)
            ->filter(fn (array $hub) => $hub['count'] >= $minPosts)
            ->sortByDesc('count')
            ->values();
    }

    /**
     * @return Collection<int, Category>
     */
    public static function crossSellCategories(Category|Product $source): Collection
    {
        $slugs = [];
        $excludeIds = [];

        if ($source instanceof Product) {
            $categories = $source->relationLoaded('categories')
                ? $source->categories
                : $source->categories()->with('parent')->get();

            foreach ($categories as $category) {
                foreach ($category->ancestorsAndSelf() as $node) {
                    $slugs[] = $node->slug;
                    $excludeIds[] = $node->id;
                }
            }
        } else {
            foreach ($source->ancestorsAndSelf() as $node) {
                $slugs[] = $node->slug;
                $excludeIds[] = $node->id;
            }

            if ($source->relationLoaded('activeChildren')) {
                $excludeIds = array_merge($excludeIds, $source->activeChildren->pluck('id')->all());
            }
        }

        $targets = [];
        $map = config('internal_links.cross_sell_by_slug', []);
        foreach (array_unique($slugs) as $slug) {
            foreach ($map[$slug] ?? [] as $targetSlug) {
                if (is_string($targetSlug) && $targetSlug !== '') {
                    $targets[] = $targetSlug;
                }
            }
        }

        $targets = array_values(array_unique($targets));
        if ($targets === []) {
            return collect();
        }

        return Category::query()
            ->where('active', true)
            ->whereIn('slug', $targets)
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', array_unique($excludeIds)))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public static function blogGuidesForBrand(string $brandSlug, int $limit = 4): Collection
    {
        return self::blogGuidesFromSlugs(
            config('internal_links.blog_guides_by_brand_slug.'.$brandSlug, []),
            $limit
        );
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public static function blogGuidesForCategory(Category $category, int $limit = 4): Collection
    {
        $slugs = [];
        foreach ($category->ancestorsAndSelf() as $node) {
            $mapped = config('internal_links.blog_guides_by_category_slug.'.$node->slug, []);
            if (is_array($mapped)) {
                $slugs = array_merge($slugs, $mapped);
            }
        }

        return self::blogGuidesFromSlugs($slugs, $limit);
    }

    /**
     * @param  list<string>  $postSlugs
     * @return Collection<int, BlogPost>
     */
    public static function blogGuidesFromSlugs(array $postSlugs, int $limit = 4): Collection
    {
        $postSlugs = array_values(array_unique(array_filter($postSlugs, fn ($slug) => is_string($slug) && $slug !== '')));
        if ($postSlugs === []) {
            return collect();
        }

        $posts = BlogPost::published()
            ->whereIn('slug', $postSlugs)
            ->get()
            ->keyBy('slug');

        return collect($postSlugs)
            ->map(fn (string $slug) => $posts->get($slug))
            ->filter()
            ->take($limit)
            ->values();
    }

    /**
     * @return array{guide: ?array{url: string, label: string, has_guide: bool}, siblings: Collection<int, Category>, cross: Collection<int, Category>}
     */
    public static function productHub(Product $product): array
    {
        $primary = $product->primaryCategory();
        $guide = null;
        $siblings = collect();

        if ($primary) {
            $landing = CategoryLandingPresenter::for($primary);
            $guide = [
                'url' => $primary->storefrontUrl(),
                'label' => $primary->name,
                'has_guide' => filled($landing['buying_guide']),
            ];

            if ($primary->parent_id) {
                $siblings = $primary->activeSiblings()->limit(8)->get();
            }
        }

        $cross = self::crossSellCategories($product)
            ->reject(fn (Category $category) => $siblings->contains('id', $category->id))
            ->values();

        return [
            'guide' => $guide,
            'siblings' => $siblings,
            'cross' => $cross,
        ];
    }

    /** @return list<string> */
    public static function significantTokens(string $text): array
    {
        $stop = ['ve', 'ile', 'icin', 'için', 'tip', 'tipi', 'nasil', 'nasıl', 'nedir', 'icin', 'bir', 'olan'];

        return collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text, 'UTF-8')) ?: [])
            ->filter(fn ($token) => is_string($token) && mb_strlen($token) >= 4 && ! in_array($token, $stop, true))
            ->unique()
            ->values()
            ->all();
    }
}
