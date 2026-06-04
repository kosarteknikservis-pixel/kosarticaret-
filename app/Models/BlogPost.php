<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug', 'title', 'excerpt', 'content', 'translations', 'tags',
        'image', 'image_alt',
        'published_at', 'published', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published' => 'boolean',
            'published_at' => 'datetime',
            'translations' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($query)
    {
        return $query->where('published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at');
    }

    public function imageUrl(?string $variant = null): ?string
    {
        return PublicAssetUrl::resolve($this->image, $variant);
    }

    public function imageSrcset(array $variants = ['blog-card' => 960]): ?string
    {
        return PublicAssetUrl::srcset($this->image, $variants);
    }
}
