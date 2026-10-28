<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SLUG = 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli';

    public function up(): void
    {
        if (! Schema::hasColumn('products', 'cost_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('cost_price', 12, 2)->nullable()->after('price');
            });
        }

        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Troy HMJ H 15 ürünü yok: '.self::SLUG);
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill([
                'cost_price' => 6800.00,
                'short_description' => 'Troy HMJ 15 jet pompalı paket hidrofor: 750 W (1 HP), 220 V, 4,5 A, 24 litre küre tank. H max 52 mss; 4 kat / 4 daire.',
                'meta_description' => 'Troy HMJ 15 paket hidrofor: 750 W (1 HP), 220 V, 4,5 A, max emiş 7 m, H max 52 mss, 24 litre küre tank. 4 kat 4 daire ev tipi hidrofor.',
                'description' => $this->descriptionHtml(),
                'specs' => [
                    'Marka' => 'Troy',
                    'Model' => 'HMJ 15',
                    'Tip' => 'Jet pompalı paket hidrofor',
                    'Motor Gücü' => '750 W (1 HP / 0,75 kW)',
                    'Akım' => '4,5 A',
                    'Voltaj' => '220 V monofaze',
                    'Giriş / Çıkış' => '1"',
                    'Maks. Emiş' => '7 m',
                    'Kondansatör' => '20 µF',
                    'Maks. Basma Yüksekliği' => '52 mss',
                    'Debi @ 0,5 m³/h' => '49 mss',
                    'Debi @ 1 m³/h' => '45 mss',
                    'Debi @ 1,5 m³/h' => '42 mss',
                    'Debi @ 2 m³/h' => '38 mss',
                    'Debi @ 3 m³/h' => '28 mss',
                    'Tank' => '24 litre küre tip genleşme tankı',
                    'Kat / Daire' => '4 kat / 4 daire',
                    'Pompa ölçüleri (G × U × Y)' => '180 × 428 × 195 mm',
                    'Paket içeriği' => 'HMJ 15 pompa, 24 lt küre tank, 5 yollu, basınç şalteri, manometre',
                ],
            ])->save();
        });
    }

    public function down(): void
    {
        // Bilinçli olarak cost_price kolonunu geri almıyoruz (başka ürünler kullanabilir).
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            return;
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill([
                'cost_price' => null,
            ])->save();
        });
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Troy HMJ 15 paket hidrofor</h2>
<p><strong>Troy HMJ 15</strong>, 750 W motora sahip kendinden emişli jet pompanın 24 litre küre tip genleşme tankı ile paketlandığı monofaze hidrofordur. 750 W yaklaşık <strong>1 HP (0,75 kW)</strong> demektir. Bu sayfada 4 kat / 4 daire konut basınçlandırması için listelenir.</p>
<p>Üretici teknik tablosuna göre pompa 220 V, 4,5 A çeker; giriş-çıkış 1", azami emiş 7 m, kondansatör 20 µF, azami basma yüksekliği 52 mss. Pompa gövdesi ölçüleri 180 × 428 × 195 mm. Debi–basma eğrisi: 0,5 m³/h’de 49 mss, 1 m³/h’de 45 mss, 1,5 m³/h’de 42 mss, 2 m³/h’de 38 mss, 3 m³/h’de 28 mss.</p>
<p>Pakette 24 litre küre tank, 5 yollu bağlantı, basınç şalteri ve manometre bulunur. Musluk açılınca pompa otomatik devreye girer; küçük su çekimlerinde önce tanktaki basınç kullanılır.</p>
<h3>Ne zaman seçilir?</h3>
<ul>
<li>4 kata / 4 daireye kadar ev tipi basınç ihtiyacı</li>
<li>220 V monofaze hat</li>
<li>24 litre küre tanklı hazır paket</li>
</ul>
<p>Daha yüksek debi veya kat için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> kategorisine bakın. 12–24 V karavan modelleri ayrıdır: <a href="/kategoriler/hidrofor-sistemleri/karavan-hidroforu">karavan hidroforu</a>. Marka: <a href="/marka/troy">Troy</a>.</p>
<p>İlk çalışmada pompa gövdesine su doldurulmalıdır. Depo emişinde çekvalf veya dip klapesi ve seviye flatörü önerilir. Sıcak su hattı için uygun değildir. Genleşme tankı ön gaz basıncı periyodik kontrol edilmelidir.</p>
HTML;
    }
};
