<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasTranslations;

    protected array $translatable = ['name', 'description', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug', 'name', 'description', 'translations', 'logo_url',
        'featured', 'active', 'sort_order',
        'meta_title', 'meta_description', 'faq',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'active' => 'boolean',
            'translations' => 'array',
            'faq' => 'array',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function logoUrl(?string $variant = null): ?string
    {
        return PublicAssetUrl::resolve($this->logo_url, $variant);
    }

    public function logoSrcset(array $variants = ['brand-logo' => 360]): ?string
    {
        return PublicAssetUrl::srcset($this->logo_url, $variants);
    }
}
