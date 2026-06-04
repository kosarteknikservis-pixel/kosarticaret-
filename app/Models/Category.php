<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasTranslations;

    protected array $translatable = ['name', 'description', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug', 'name', 'description', 'image', 'translations', 'parent_id',
        'featured', 'show_in_menu', 'active', 'sort_order',
        'meta_title', 'meta_description', 'faq',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'show_in_menu' => 'boolean',
            'active' => 'boolean',
            'translations' => 'array',
            'faq' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('active', true);
    }

    /**
     * Mağaza URL yolu: ust/alt (orn. su-pompalari/jet-pompa).
     */
    public function nestedSlugPath(): string
    {
        $segments = [];
        $node = $this;

        while ($node !== null) {
            array_unshift($segments, $node->slug);
            if ($node->parent_id === null) {
                break;
            }
            $node = $node->relationLoaded('parent') && $node->parent
                ? $node->parent
                : static::query()->find($node->parent_id);
        }

        return implode('/', $segments);
    }

    public function storefrontUrl(): string
    {
        return route('categories.show', ['category' => $this->nestedSlugPath()]);
    }

    /**
     * @return list<Category>
     */
    public function ancestorsAndSelf(): array
    {
        $chain = [];
        $node = $this;

        while ($node !== null) {
            array_unshift($chain, $node);
            if ($node->parent_id === null) {
                break;
            }
            $node = $node->relationLoaded('parent') && $node->parent
                ? $node->parent
                : static::query()->find($node->parent_id);
        }

        return $chain;
    }

    public static function resolveFromStorefrontPath(string $path): ?self
    {
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        $parentId = null;
        $category = null;

        foreach (explode('/', $path) as $slug) {
            $query = static::query()
                ->where('slug', $slug)
                ->where('active', true);

            if ($parentId === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }

            $category = $query->first();

            if ($category === null) {
                return null;
            }

            $parentId = $category->id;
        }

        return $category;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeMenu($query)
    {
        return $query->whereNull('parent_id')
            ->where('active', true)
            ->where('show_in_menu', true)
            ->orderBy('sort_order');
    }

    public static function forStorefrontMenu()
    {
        return static::menu()->with([
            'activeChildren' => fn ($q) => $q->orderBy('sort_order'),
        ]);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)->where('active', true);
    }

    public function imageUrl(?string $variant = null): ?string
    {
        return PublicAssetUrl::resolve($this->image, $variant);
    }

    public function imageSrcset(array $variants = ['category-card' => 720]): ?string
    {
        return PublicAssetUrl::srcset($this->image, $variants);
    }
}
