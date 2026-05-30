<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class HomeRow extends Model
{
    public const LAYOUTS = [
        '12' => [12],
        '6-6' => [6, 6],
        '4-4-4' => [4, 4, 4],
        '8-4' => [8, 4],
        '4-8' => [4, 8],
        '3-3-3-3' => [3, 3, 3, 3],
    ];

    protected $fillable = ['name', 'columns', 'sort_order', 'active'];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function banners(): HasMany
    {
        return $this->hasMany(HomeBanner::class)->orderBy('col_index')->orderBy('sort_order')->orderBy('id');
    }

    public function columnSpan(int $index): int
    {
        $cols = $this->columns ?? [12];

        return (int) ($cols[$index] ?? 12);
    }

    public function columnCount(): int
    {
        return count($this->columns ?? [12]);
    }

    /** @return Collection<int, Collection<int, HomeBanner>> */
    public function bannersByColumn(): Collection
    {
        $grouped = $this->banners->groupBy('col_index');

        return collect(range(0, $this->columnCount() - 1))
            ->map(fn (int $i) => $grouped->get($i, collect())->values());
    }

    public static function layoutFromPreset(string $preset): array
    {
        return self::LAYOUTS[$preset] ?? [12];
    }

    /** @return Collection<int, self> */
    public static function forHomepage(): Collection
    {
        return static::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['banners' => fn ($q) => $q->where('active', true)->with(['product.brand', 'category', 'brand'])])
            ->get()
            ->filter(fn (self $row) => $row->banners->contains(
                fn (HomeBanner $b) => $b->canDisplay() && ($b->isProductList() || $b->imageUrl())
            ));
    }
}
