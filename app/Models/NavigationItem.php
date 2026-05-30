<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    protected $fillable = [
        'label', 'url', 'location', 'sort_order', 'active', 'open_in_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'open_in_new_tab' => 'boolean',
        ];
    }

    public function scopeActive($query, string $location)
    {
        return $query->where('location', $location)
            ->where('active', true)
            ->orderBy('sort_order');
    }
}
