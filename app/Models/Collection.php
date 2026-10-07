<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_NOINDEX = 'noindex';

    public const STATUS_INDEX = 'index';

    public const MIN_PRODUCTS = 6;

    protected $fillable = [
        'name',
        'slug',
        'target_keyword',
        'status',
        'description',
        'rules',
        'faq',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'faq' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot(['source', 'evidence']);
    }

    public function visibleProducts(): BelongsToMany
    {
        return $this->products()
            ->wherePivotIn('source', ['rule', 'include'])
            ->where('products.is_active', true);
    }

    public function isPublic(): bool
    {
        return in_array($this->status, [self::STATUS_INDEX, self::STATUS_NOINDEX], true);
    }
}
