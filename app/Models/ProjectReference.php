<?php

namespace App\Models;

use App\Concerns\ClearsPublicPageCache;
use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Model;

class ProjectReference extends Model
{
    use ClearsPublicPageCache;
    protected $fillable = [
        'title', 'slug', 'client', 'sector', 'location', 'summary', 'body',
        'image', 'featured', 'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function imageUrl(?string $variant = null): ?string
    {
        return PublicAssetUrl::resolve($this->image, $variant);
    }
}
