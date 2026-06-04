<?php

namespace App\Models;

use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'alt', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(?string $variant = null): string
    {
        return PublicAssetUrl::resolve($this->path, $variant) ?? asset('storage/'.$this->path);
    }

    public function srcset(array $variants = ['product-thumb' => 160, 'product-pdp' => 1200]): ?string
    {
        return PublicAssetUrl::srcset($this->path, $variants);
    }
}
