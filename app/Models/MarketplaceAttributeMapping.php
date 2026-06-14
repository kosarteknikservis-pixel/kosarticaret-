<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAttributeMapping extends Model
{
    protected $fillable = [
        'channel_key',
        'category_id',
        'local_spec_key',
        'external_attribute_id',
        'external_attribute_name',
        'value_map',
    ];

    protected function casts(): array
    {
        return [
            'value_map' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
