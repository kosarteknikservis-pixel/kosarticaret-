<?php

namespace App\Services;

use App\Models\Collection;
use App\Support\CollectionSpecReader;

class CollectionIntro
{
    public function __construct(private CollectionSpecReader $reader) {}

    public function sentence(Collection $collection): string
    {
        $products = $collection->visibleProducts()->with('brand')->get();
        $count = $products->count();
        if ($count === 0) {
            return 'Bu listede şu an ürün yok.';
        }

        $prices = $products->pluck('price')->map(fn ($price) => (float) $price)->filter(fn ($price) => $price > 0);
        $brands = $products->pluck('brand.name')->filter()->unique()->sort()->values();
        $powers = [];
        foreach ($products as $product) {
            $motor = $this->reader->motorHp(is_array($product->specs) ? $product->specs : null);
            if ($motor['status'] === 'clear' && $motor['hp'] !== null) {
                $powers[] = $this->formatHp($motor['hp']);
            }
        }
        $powers = array_values(array_unique($powers));
        sort($powers, SORT_NATURAL);

        $parts = [$count.' ürün'];
        if ($prices->isNotEmpty()) {
            $min = $this->money((float) $prices->min());
            $max = $this->money((float) $prices->max());
            $parts[] = $min === $max ? 'fiyat '.$min.' TL' : 'fiyat '.$min.'–'.$max.' TL';
        }
        if ($powers !== []) {
            $parts[] = 'motor gücü '.implode(', ', $powers).' HP';
        }
        if ($brands->isNotEmpty()) {
            $shown = $brands->take(6)->implode(', ');
            $extra = $brands->count() - min(6, $brands->count());
            $parts[] = $extra > 0 ? 'markalar: '.$shown.' ve '.$extra.' marka daha' : 'markalar: '.$shown;
        }

        return implode('. ', $parts).'.';
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    private function formatHp(float $hp): string
    {
        $text = number_format($hp, 2, ',', '');

        return rtrim(rtrim($text, '0'), ',');
    }
}
