<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'content', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug', 'title', 'content', 'translations',
        'meta_title', 'meta_description',
        'published', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'translations' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
