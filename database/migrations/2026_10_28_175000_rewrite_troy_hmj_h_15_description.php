<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli';

    public function up(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Troy HMJ H 15 ürünü yok: '.self::SLUG);
        }

        Product::withoutEvents(function () use ($product): void {
            $name = 'Troy HMJ H 15 Hidrofor 4 Kat 4 Daire 24 Litre Küre Tanklı';

            $product->fill([
                'name' => $name,
                'short_description' => 'Troy HMJ 15 jet pompalı paket hidrofor: 750 W (1 HP), 220 V, 4,5 A, 24 litre küre tank. H max 52 mss; 4 kat / 4 daire.',
                'meta_title' => $name,
                'meta_description' => 'Troy HMJ 15 paket hidrofor: 750 W (1 HP), 220 V, 4,5 A, max emiş 7 m, H max 52 mss, 24 litre küre tank. 4 kat 4 daire ev tipi hidrofor.',
                'image_alt' => $name,
                'description' => $this->descriptionHtml(),
                'specs' => [
                    'Marka' => 'Troy',
                    'Model' => 'HMJ 15',
                    'Motor Gücü (HP)' => '1 HP',
                    'Motor Gücü (kW)' => '0,75 kW (750 W)',
                    'Akım' => '4,5 A',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Maks. Debi (m³/h)' => '3 m³/h',
                    'Maks. Basma Yüksekliği (mSS)' => '52 mss',
                    'Kat Sayısı' => '4 Kat',
                    'Daire Sayısı' => '4 Daire',
                    'Emiş Derinliği' => '7 Metre',
                    'Giriş / Çıkış' => '1"',
                    'Kondansatör' => '20 µF',
                    'Genleşme Tankı' => '24 Litre Küre',
                    'Pompa Ölçüleri (G × U × Y)' => '180 × 428 × 195 mm',
                ],
            ])->save();
        });
    }

    public function down(): void
    {
        // Önceki gövde 2026_10_28_174500 migration'ında; geri alma gerekmez.
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Troy HMJ H 15 Hidrofor 4 Kat 4 Daire 24 Litre Küre Tanklı</h2>
<p>Troy HMJ 15, 750 W (1 HP / 0,75 kW) kendinden emişli jet pompa ile 24 litre küre tip genleşme tankının birleştiği monofaze paket hidrofordur. 4 kat / 4 daireye kadar konutlarda şebeke veya depo basıncını yükseltmek için kullanılır. Su talebi başladığında otomatik devreye girer; talep bitince basınç şalteri pompayı durdurur.</p>

<table>
<thead>
<tr><th>Teknik Veriler</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Marka</td><td>Troy</td></tr>
<tr><td>Model</td><td>HMJ 15</td></tr>
<tr><td>Motor Gücü</td><td>750 W (1 HP / 0,75 kW)</td></tr>
<tr><td>Akım</td><td>4,5 A</td></tr>
<tr><td>Voltaj</td><td>220 Volt (Monofaze)</td></tr>
<tr><td>Giriş / Çıkış</td><td>1"</td></tr>
<tr><td>Maks. Emiş</td><td>7 Metre</td></tr>
<tr><td>Kondansatör</td><td>20 µF</td></tr>
<tr><td>Maks. Basma Yüksekliği</td><td>52 mss</td></tr>
<tr><td>Kat / Daire</td><td>4 Kat / 4 Daire</td></tr>
<tr><td>Genleşme Tankı</td><td>24 Litre Küre</td></tr>
<tr><td>Pompa Ölçüleri (G × U × Y)</td><td>180 × 428 × 195 mm</td></tr>
</tbody>
</table>

<table>
<thead>
<tr><th>Debi (m³/h)</th><th>Basma yüksekliği (mss)</th></tr>
</thead>
<tbody>
<tr><td>0,5</td><td>49</td></tr>
<tr><td>1</td><td>45</td></tr>
<tr><td>1,5</td><td>42</td></tr>
<tr><td>2</td><td>38</td></tr>
<tr><td>3</td><td>28</td></tr>
</tbody>
</table>

<h3>Otomatik çalışma</h3>
<p>Paket; pompa, 24 litre küre tank, 5 yollu bağlantı, basınç şalteri ve manometreden oluşur. Az su çekiminde önce tanktaki basınç kullanılır; pompa her muslukta dur-kalk yapmaz. Su tüketimi bittiğinde sistem otomatik durur.</p>

<h3>Kullanım alanları</h3>
<ul>
<li>Müstakil ev ve villa basınçlandırması</li>
<li>4 kata / 4 daireye kadar küçük apartmanlar</li>
<li>Depodan temiz su basma</li>
<li>Bahçe sulama destek hattı</li>
</ul>

<h3>Kurulum ve bakım</h3>
<p>Tesisat ve elektrik bağlantısı yapıldıktan sonra kullanıma hazırdır. İlk çalışmada pompa gövdesine su doldurulmalıdır. Depo veya sığ kuyu emişinde emiş hattına çekvalf / dip klapesi; depoda su bitmesine karşı seviye flatörü önerilir. Genleşme tankı ön gaz basıncı 6 ayda bir kontrol edilmelidir. Sıcak su hattı için uygun değildir.</p>

<p>Benzer paketler için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> kategorisine, 12–24 V karavan modelleri için <a href="/kategoriler/hidrofor-sistemleri/karavan-hidroforu">karavan hidroforu</a> sayfasına bakın. Marka özeti: <a href="/marka/troy">Troy</a>.</p>
HTML;
    }
};
