<?php

namespace App\Models;

use App\Concerns\ClearsPublicPageCache;
use App\Concerns\HasTranslations;
use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use ClearsPublicPageCache, HasTranslations;

    protected array $translatable = [
        'name', 'short_description', 'description', 'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'slug', 'sku', 'name', 'short_description', 'description', 'translations',
        'brand_id', 'price', 'compare_at_price', 'stock',
        'rating', 'review_count', 'badges', 'specs', 'tags',
        'featured', 'is_active', 'image', 'image_alt', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'rating' => 'decimal:1',
            'badges' => 'array',
            'specs' => 'array',
            'tags' => 'array',
            'featured' => 'boolean',
            'is_active' => 'boolean',
            'translations' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function imageAltText(): string
    {
        return filled($this->image_alt) ? (string) $this->image_alt : $this->name;
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('approved', true)->latest();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price !== null
            && (float) $this->compare_at_price > (float) $this->price;
    }

    public function discountPercent(): ?int
    {
        if (! $this->hasDiscount()) {
            return null;
        }

        return (int) round((((float) $this->compare_at_price - (float) $this->price) / (float) $this->compare_at_price) * 100);
    }

    public function imageUrl(?string $variant = null): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return PublicAssetUrl::resolve($this->image, $variant);
    }

    public function imageSrcset(array $variants = ['product-card' => 480, 'product-pdp' => 1200]): ?string
    {
        return PublicAssetUrl::srcset($this->image, $variants);
    }

    public function cardImageSrcset(): ?string
    {
        return $this->imageSrcset(['product-card-sm' => 320, 'product-card' => 480]);
    }
}
