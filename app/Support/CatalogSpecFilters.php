<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

/**
 * Teknik özellik filtreleri — yalnızca yeterince güvenilir alanlar.
 *
 * Kurallar:
 * - Voltajdan faz çıkarılmaz.
 * - 220 ile 230, 380 ile 400 birleştirilmez (açık dayanak yok).
 * - Çift/seçenekli voltaj her iki filtreden de düşer.
 * - Eksik özellikli ürünler aktif filtrede dahil edilmez (uydurma yok).
 * - Query anahtarları CatalogPaginationSeo::FILTER_QUERY_KEYS ile noindex kalır.
 */
class CatalogSpecFilters
{
    /** @var list<string> */
    public const QUERY_KEYS = ['voltaj'];

    /** @var list<string> */
    public const VOLTAJ_VALUES = ['220', '380'];

    /**
     * Ham Voltaj alanından filtre etiketi.
     * Kaynak: products.specs['Voltaj'] — tahmin doldurma yok.
     *
     * @return '220'|'380'|null
     */
    public static function normalizeVoltaj(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $lower = mb_strtolower(trim((string) $raw));
        if ($lower === '') {
            return null;
        }

        // Çift / seçenekli / belirsiz
        if (str_contains($lower, 'seçenek') || str_contains($lower, 'secenek')) {
            return null;
        }

        $has220 = (bool) preg_match('/(?<!\d)220(?!\d)/', $lower);
        $has230 = (bool) preg_match('/(?<!\d)230(?!\d)/', $lower);
        $has380 = (bool) preg_match('/(?<!\d)380(?!\d)/', $lower);
        $has400 = (bool) preg_match('/(?<!\d)400(?!\d)/', $lower);

        // 230 ve 400 ayrı bırakılır; 220/380 grubuna alınmaz.
        if ($has230 || $has400) {
            return null;
        }

        if ($has220 && $has380) {
            return null;
        }

        if ($has220 && ! $has380) {
            return '220';
        }

        if ($has380 && ! $has220) {
            return '380';
        }

        return null;
    }

    /**
     * @param  Builder<\App\Models\Product>|Relation  $query
     * @return Builder<\App\Models\Product>|Relation
     */
    public static function apply(Request $request, Builder|Relation $query): Builder|Relation
    {
        $voltaj = trim((string) $request->input('voltaj', ''));
        if ($voltaj === '' || ! in_array($voltaj, self::VOLTAJ_VALUES, true)) {
            return $query;
        }

        $other = $voltaj === '220' ? '380' : '220';

        // Yalnız net tek değer; 230/400/çift voltaj ve seçenekli kayıtlar dışarıda.
        return $query->where(function ($b) use ($voltaj, $other) {
            $b->where('specs->Voltaj', 'like', '%'.$voltaj.'%')
                ->where('specs->Voltaj', 'not like', '%'.$other.'%')
                ->where('specs->Voltaj', 'not like', '%230%')
                ->where('specs->Voltaj', 'not like', '%400%')
                ->where('specs->Voltaj', 'not like', '%seçenek%')
                ->where('specs->Voltaj', 'not like', '%secenek%');
        });
    }
}
