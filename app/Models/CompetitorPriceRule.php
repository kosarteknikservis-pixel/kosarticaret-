<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitorPriceRule extends Model
{
    protected $fillable = [
        'name',
        'undercut_percent',
        'min_price',
        'min_margin_percent',
        'keep_compare_at',
        'auto_apply',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'undercut_percent' => 'decimal:2',
            'min_price' => 'decimal:2',
            'min_margin_percent' => 'decimal:2',
            'keep_compare_at' => 'boolean',
            'auto_apply' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public static function activeRule(): self
    {
        $rule = static::query()->where('active', true)->orderBy('id')->first();

        if ($rule) {
            return $rule;
        }

        return static::query()->create([
            'name' => 'Varsayılan',
            'undercut_percent' => 2,
            'keep_compare_at' => true,
            'auto_apply' => false,
            'active' => true,
        ]);
    }
}
