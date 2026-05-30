<?php

namespace App\Models;

use App\Support\HomeProductList;
use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class HomeBanner extends Model
{
    public const TYPE_SLIDER = 'slider';

    public const TYPE_BANNER = 'banner';

    public const TYPE_CATEGORY = 'category';

    public const TYPE_PRODUCT = 'product';

    /** WordPress tarzı çoklu ürün vitrini */
    public const TYPE_PRODUCT_LIST = 'product_list';

    public const TYPES = [
        self::TYPE_SLIDER,
        self::TYPE_BANNER,
        self::TYPE_CATEGORY,
        self::TYPE_PRODUCT,
        self::TYPE_PRODUCT_LIST,
    ];

    protected $fillable = [
        'type', 'home_row_id', 'col_index', 'image', 'product_id', 'category_id',
        'product_source', 'brand_id', 'product_ids', 'product_limit',
        'title', 'subtitle', 'cta_text', 'link_url', 'image_alt',
        'sort_order', 'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
            'col_index' => 'integer',
            'product_ids' => 'array',
            'product_limit' => 'integer',
        ];
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(HomeRow::class, 'home_row_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function isSlider(): bool
    {
        return $this->type === self::TYPE_SLIDER;
    }

    public function isBanner(): bool
    {
        return $this->type === self::TYPE_BANNER;
    }

    public function isProduct(): bool
    {
        return $this->type === self::TYPE_PRODUCT;
    }

    public function isCategory(): bool
    {
        return $this->type === self::TYPE_CATEGORY;
    }

    public function isProductList(): bool
    {
        return $this->type === self::TYPE_PRODUCT_LIST;
    }

    /** @return Collection<int, Product> */
    public function listedProducts(): Collection
    {
        return HomeProductList::resolve($this);
    }

    public function isTile(): bool
    {
        return in_array($this->type, [self::TYPE_BANNER, self::TYPE_CATEGORY, self::TYPE_PRODUCT], true);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_SLIDER => __('shop.banner_type_slider'),
            self::TYPE_BANNER => __('shop.banner_type_banner'),
            self::TYPE_CATEGORY => __('shop.banner_type_category'),
            self::TYPE_PRODUCT => __('shop.banner_type_product'),
            self::TYPE_PRODUCT_LIST => __('shop.banner_type_product_list'),
            default => $this->type,
        };
    }

    public function listSourceSummary(): string
    {
        if (! $this->isProductList()) {
            return '';
        }

        $count = $this->listedProducts()->count();
        $base = HomeProductList::sourceLabel($this->product_source).' · '.__('shop.product_list_count', ['count' => $count]);

        return match ($this->product_source) {
            'category' => $base.' · '.($this->category?->name ?? '—'),
            'brand' => $base.' · '.($this->brand?->name ?? '—'),
            default => $base,
        };
    }

    public function imageUrl(): ?string
    {
        if ($this->image) {
            return PublicAssetUrl::resolve($this->image);
        }

        if ($this->isProduct() && $this->relationLoaded('product') && $this->product) {
            return $this->product->imageUrl();
        }

        if ($this->isCategory() && $this->relationLoaded('category') && $this->category) {
            return $this->category->imageUrl();
        }

        return null;
    }

    public function displayTitle(): ?string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        if ($this->isProduct() && $this->product) {
            return $this->product->name;
        }

        if ($this->isCategory() && $this->category) {
            return $this->category->name;
        }

        return null;
    }

    public function displayAlt(): string
    {
        return trim((string) ($this->image_alt ?: $this->displayTitle() ?: __('shop.home_banner_default_alt')));
    }

    public function hasOverlay(): bool
    {
        if ($this->isTile() && ! $this->isBanner()) {
            return filled($this->displayTitle()) || filled($this->subtitle);
        }

        return filled($this->title) || filled($this->subtitle) || filled($this->cta_text);
    }

    public function targetUrl(): ?string
    {
        if ($this->isProduct() && $this->product) {
            return route('products.show', $this->product);
        }

        if ($this->isCategory() && $this->category) {
            return $this->category->storefrontUrl();
        }

        if (! filled($this->link_url)) {
            return null;
        }

        $href = $this->link_url;
        if (! str_starts_with($href, 'http://') && ! str_starts_with($href, 'https://')) {
            return url($href);
        }

        return $href;
    }

    public function canDisplay(): bool
    {
        if ($this->isProductList()) {
            return $this->listedProducts()->isNotEmpty();
        }

        if ($this->isProduct()) {
            return $this->product_id && $this->product;
        }

        if ($this->isCategory()) {
            return $this->category_id && $this->category;
        }

        return filled($this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function columnSpan(): int
    {
        if ($this->relationLoaded('row') && $this->row) {
            return $this->row->columnSpan((int) $this->col_index);
        }

        return 12;
    }
}

