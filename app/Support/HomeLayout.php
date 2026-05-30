<?php

namespace App\Support;

use App\Models\HomeBanner;
use App\Models\HomeRow;
use Illuminate\Support\Collection;

final class HomeLayout
{
    /** @return Collection<int, HomeRow> */
    public static function rowsForHomepage(): Collection
    {
        return HomeRow::forHomepage();
    }

    /** @return Collection<int, HomeBanner> */
    public static function legacySliders(): Collection
    {
        return static::rowsForHomepage()
            ->flatMap(fn (HomeRow $row) => $row->banners->filter(fn (HomeBanner $b) => $b->isSlider()))
            ->values();
    }
}
