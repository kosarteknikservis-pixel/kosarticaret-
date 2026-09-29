<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * [kategori slug'ı, eski meta_title md5, yeni meta_title, eski meta_description md5, yeni meta_description].
     * Panelden değiştirilmiş alanın üzerine yazılmaz; null yeni değer alanı olduğu gibi bırakır.
     */
    private function metaFixes(): array
    {
        return [
            [
                'hidrofor-grubu',
                '2c913f31db8a94e0986fec969a38096a', 'Hidrofor Grubu Fiyatları | Sumak, Winpo | Koşar Ticaret',
                '2e31010c147ca62419a9311f0388eccd', 'Apartman, site ve otel için tek, çift ve üç pompalı hidrofor grupları: Sumak SHT, SHTP ve SMKT, Winpo WNP1 ve WNP2 VM. Frekans kontrollü modeller mevcut.',
            ],
            [
                'sumak-hidrofor',
                '447f3ed9ec4093c8a7d4035617663f43', 'Sumak Hidrofor Fiyatları | SMJ, SM ve SYMH Modelleri | Koşar Ticaret',
                '9ae01ae89da9f8c8e0ffcfc7fb64a964', 'Sumak hidrofor modelleri: SM ve SMJ ev tipi paket hidroforlar, SYMH yatay kademeli bina hidroforları ve SMH 120 BOX sessiz hidrofor. Yetkili Sumak bayisi.',
            ],
            [
                'pedrollo-hidrofor',
                '5ad5f5c709c494fc5b6d2250c03d939f', 'Pedrollo Hidrofor Fiyatları | JSWm, 4CPm ve PKm Modelleri | Koşar Ticaret',
                '1a4b74b4cea5d530bf107ec47352d8a4', 'Pedrollo hidrofor modelleri: PKm 60 mini, JSWm ve JCRm jet, 4CPm sessiz ve 2CPm dijital paket hidroforlar; hidromatlı ve tanklı. Yetkili Pedrollo bayisi.',
            ],
            [
                'ev-tipi-hidroforlar',
                'c78199d033357112bc8627e3cacff088', 'Ev Tipi Hidrofor Fiyatları | Pedrollo, Winpo, Kaysu | Koşar Ticaret',
                '704d80a06d416ee5c853fe668e636aed', 'Müstakil ev ve küçük binalar için ev tipi hidroforlar: Pedrollo JSWm ve PKm 60, Winpo WNP 100 ve 150, Kaysu HKJM. Hidromatlı, 24 ve 50 litre tanklı modeller.',
            ],
            [
                'su-pompalari',
                '8c3bda3e8d8ca9dad6203f3a5398a35e', null,
                '093259e4aa78fb993c133dc5097b35bb', 'Santrifüj, dalgıç, kademeli, sirkülasyon ve jet pompa dahil 1.000\'i aşkın su pompası modeli. Pedrollo, Sumak, Winpo ve Kaysu. Ücretsiz teknik danışmanlık.',
            ],
            [
                'drenaj-dalgic-pompa',
                '00e04c895e4f6596dada921ecf462005', null,
                '43b1757ecd3d50b6328c9bd754f87752', 'Bodrum, garaj ve depo tahliyesi için flatörlü drenaj dalgıç pompaları: Pedrollo TOP ve RXm, Winpo QDP ve WNP, Kaysu SP. Plastik ve paslanmaz gövdeli modeller.',
            ],
            [
                'derin-kuyu-dalgic-pompa',
                '6a6cb7445303e27005286cd70bcc69ec', null,
                '033ced0b4dd461293e1712e1a24a7423', '4 inç derin kuyu dalgıç pompaları: Pedrollo 4SR, Sumak 4SD ve Winpo WNP. Monofaze ve trifaze modeller, ayrı dalgıç motorlar ve kuyuya göre seçim desteği.',
            ],
            [
                'temiz-su-dalgic-pompasi',
                'd3e10629c9feb89e4c98e8012e25efcc', null,
                '0a923a801603730bbf796de272d44507', 'Depo, sarnıç ve kuyudan temiz su almak için dalgıç pompalar: Pedrollo TOP ve TOP MULTI, Sumak SDF, Winpo QDP ve Kaysu QDX. Flatörlü ve çok kademeli modeller.',
            ],
            [
                'foseptik-dalgic-pompa',
                '441c1f5f3936afdc9e4d62508ed51b44', null,
                '9acb0dd39acc79208159fa7200c9015c', 'Foseptik ve atık su tahliyesi için dalgıç pompalar: Pedrollo VXm ve BCm, Sumak SDTV ve SDTK, Winpo kırıcılı WNP ve Kaysu WQD. Vortex çarklı ve bıçaklı modeller.',
            ],
            [
                'sirkulasyon-pompalari',
                'fca2de03d85fa466cba32e209440835d', null,
                '7de3b22e66df837c51a38a6ce471d8c0', 'Kalorifer, yerden ısıtma ve sıcak su hatları için Sumak sirkülasyon pompaları: SSP rekorlu, frekans konvertörlü SSP INV, SML inline ve -S serisi modeller.',
            ],
            [
                'inline-sirkulasyon-pompalari',
                'fc3cc10103bb255adb4a311f8e5758a2', null,
                'a4ba5e107a682de7c8310ff520bca1d8', 'Büyük bina ısıtma ve soğutma hatları için Sumak SML 160/65 ve SML 160/80 inline sirkülasyon pompaları. Boru hattına doğrudan flanşlı montaj ve teknik destek.',
            ],
            [
                'rekorlu-disli-sirkulasyon-pompalari',
                '585f7c3031dfebce214444cd057b7a1a', null,
                'c063b4d23b96fbe84f6f646858362285', 'Kalorifer ve yerden ısıtma devreleri için Sumak SSP 25 ve SSP 32 rekorlu dişli sirkülasyon pompaları. Kompakt yapı, kolay montaj ve teknik seçim desteği.',
            ],
            [
                'santrifuj-pompalar-sulama',
                'b5d4844e9ba22e275366099dcaa83736', null,
                '5149f7700869526941da23952f792352', 'Tarım ve bahçe sulaması için Pedrollo F ve Fm serisi santrifüj pompalar. DN32\'den DN80\'e çıkışlı flanşlı modeller, yüksek debi ve teknik seçim desteği.',
            ],
            [
                'yangin-pompalari',
                '943d5f5a1c0d103e716c11ede99c2d78', null,
                '624a8e38d7afeb1a370599e866ec672f', 'Bina yangın söndürme sistemleri için Sumak SHT yangın pompası grupları ve SMKT 750 modelleri. Proje debisi ve basıncına göre seçim ve teknik danışmanlık.',
            ],
        ];
    }

    /** Breadcrumb'ın Foseptik görünmesi için bu Pedrollo foseptik pompalardan kaldırılan drenaj kategorisi. */
    private function foseptikDrenajDetachMap(): array
    {
        return [
            ['pedrollo-vx-1550-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', 'paslanmaz-drenaj-dalgic-pompa'],
            ['pedrollo-vxm-1550-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', 'paslanmaz-drenaj-dalgic-pompa'],
            ['pedrollo-vx3040-dokum-govdeli-foseptik-dalgic-pompa', 'drenaj-dalgic-pompa'],
            ['pedrollo-vx4040-dokum-govdeli-foseptik-dalgic-pompa', 'drenaj-dalgic-pompa'],
            ['pedrollo-vx5540-dokum-govdeli-foseptik-dalgic-pompa', 'drenaj-dalgic-pompa'],
            ['pedrollo-vxm-1035-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', 'drenaj-dalgic-pompa'],
            ['pedrollo-vxm-1050-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', 'drenaj-dalgic-pompa'],
        ];
    }

    public function up(): void
    {
        foreach ($this->metaFixes() as [$slug, $oldTitleMd5, $newTitle, $oldDescriptionMd5, $newDescription]) {
            $category = Category::query()->where('slug', $slug)->first();
            if (! $category) {
                continue;
            }

            $changes = [];
            if ($newTitle !== null && md5((string) $category->getRawOriginal('meta_title')) === $oldTitleMd5) {
                $changes['meta_title'] = $newTitle;
            }
            if ($newDescription !== null && md5((string) $category->getRawOriginal('meta_description')) === $oldDescriptionMd5) {
                $changes['meta_description'] = $newDescription;
            }

            if ($changes !== []) {
                $category->forceFill($changes)->save();
            }
        }

        $pedrolloId = Brand::query()->where('slug', 'pedrollo')->value('id');
        $categoryIds = Category::query()->pluck('id', 'slug');
        $foseptikId = $categoryIds['foseptik-dalgic-pompa'] ?? null;
        if (! $pedrolloId || ! $foseptikId) {
            return;
        }

        foreach ($this->foseptikDrenajDetachMap() as [$productSlug, $categorySlug]) {
            $product = Product::query()->where('slug', $productSlug)->where('brand_id', $pedrolloId)->first();
            $categoryId = $categoryIds[$categorySlug] ?? null;
            if (! $product || ! $categoryId || ! $product->categories()->whereKey($foseptikId)->exists()) {
                continue;
            }

            $product->categories()->detach($categoryId);
        }
    }

    public function down(): void
    {
        // Meta ve kategori düzeltmesi; geri alma için yedekten dönülür.
    }
};
