<?php

use App\Models\Brand;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * N11 reddi: başlık/açıklamada yasaklı "troy" ve "yakar".
 * İlgili ürünler Sumak markaya alınır; metinden yasaklı kelimeler temizlenir.
 * URL slug değişmez.
 */
return new class extends Migration
{
    /**
     * @var list<array{slug: string, name: string}>
     */
    private const ITEMS = [
        [
            'slug' => 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli',
            'name' => 'Sumak HMJ H 15 Hidrofor 4 Kat 4 Daire 24 Litre Küre Tanklı',
        ],
        [
            'slug' => 'troy-hmj-15-dokum-govdeli-jet-pompa-1-hp-49-mss-4-m3-h',
            'name' => 'Sumak HMJ 15 Döküm Gövdeli Jet Pompa 1 HP 49 mss 4 m³/h',
        ],
        [
            'slug' => 'troy-ts-12-12-h-12v-karavan-hidroforu',
            'name' => 'Sumak TS 12-12 H 12V Karavan Hidroforu',
        ],
        [
            'slug' => 'troy-ts-12-8-h-12v-karavan-hidroforu',
            'name' => 'Sumak TS 12-8 H 12V Karavan Hidroforu',
        ],
        [
            'slug' => 'kaysu-pompa-hidrofor-050-hp-1-kat-1-daire',
            'name' => 'Sumak Hidrofor 0.50 HP 1 Kat 1 Daire',
        ],
        [
            'slug' => 'kaysu-pompa-hkjmh-10h-jet-hidrofor-4-kat-4-daire-24l-tankli',
            'name' => 'Sumak HKJMH 10H Jet Hidrofor 4 Kat 4 Daire 24L Tanklı',
        ],
    ];

    public function up(): void
    {
        $sumak = Brand::query()->where('slug', 'sumak')->first();
        if ($sumak === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Sumak markası yok.');
        }

        MarketplaceBrandMapping::query()->updateOrCreate(
            [
                'channel_key' => 'n11',
                'brand_id' => $sumak->id,
            ],
            [
                'external_brand_id' => 'Sumak',
                'external_brand_name' => 'Sumak',
            ],
        );

        foreach (self::ITEMS as $item) {
            $product = Product::query()->where('slug', $item['slug'])->first();
            if ($product === null) {
                continue;
            }

            $short = $this->scrubBanned((string) ($product->short_description ?: ''));
            $desc = $this->scrubBanned((string) ($product->description ?: ''));

            Product::withoutEvents(function () use ($product, $sumak, $item, $short, $desc): void {
                $product->forceFill([
                    'brand_id' => $sumak->id,
                    'name' => $item['name'],
                    'short_description' => $short !== '' ? $short : $product->short_description,
                    'description' => $desc !== '' ? $desc : $product->description,
                ])->save();
            });

            MarketplaceListing::query()
                ->where('product_id', $product->id)
                ->where('channel_key', 'n11')
                ->update([
                    'status' => 'draft',
                    'external_product_id' => null,
                    'last_error' => 'N11 yasaklı kelime düzeltmesi: Sumak marka + metin temizliği; yeniden gönderilecek.',
                ]);
        }
    }

    public function down(): void
    {
        // Marka/metin geri alınmaz.
    }

    private function scrubBanned(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // troy / Troy / TROY → Sumak (kelime sınırı)
        $html = preg_replace('/\b[Tt][Rr][Oo][Yy]\b/u', 'Sumak', $html) ?? $html;

        // yasaklı "yakar" (motoru yakar vb.) → güvenli ifade
        $html = preg_replace('/\byakar\b/iu', 'zarar verir', $html) ?? $html;
        $html = preg_replace('/\byakmaz\b/iu', 'zarar vermez', $html) ?? $html;

        // Marka linkleri /marka/troy → /marka/sumak
        $html = str_ireplace('/marka/troy', '/marka/sumak', $html);
        $html = str_ireplace('>Troy<', '>Sumak<', $html);
        $html = str_ireplace('Kaysu Pompa', 'Sumak', $html);
        $html = preg_replace('/\bKaysu\b/u', 'Sumak', $html) ?? $html;

        return $html;
    }
};
