<?php

use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sdf-151-temiz-su-dalgic-pompa-monofaze-220v-15-hp';

    private const TRENDYOL_PRICE = 23599.00;

    public function up(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Ürün yok: '.self::SLUG);
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) ? $product->barcode : 'SDF151',
                'sku' => filled($product->sku) ? $product->sku : 'SDF151',
                'short_description' => 'Sumak SDF 15/1 temiz su dalgıç pompa: 1.5 HP (1.1 kW), 220 V monofaze, maks. 31 m basma, 6-10 m³/h debi, 1" çıkış.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Sumak',
                    'Model' => 'SDF 15/1',
                    'Motor Gücü' => '1.5 HP (1.1 kW)',
                    'Voltaj' => '220V (Monofaze)',
                    'Maksimum Debi' => '6-10 m³/h',
                    'Maksimum Basma Yüksekliği' => '31 m',
                    'Çıkış Ölçüsü' => '1" (25 mm)',
                    'Devir' => '2900 d/d',
                ]),
            ])->save();
        });

        MarketplaceListing::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'channel_key' => 'trendyol',
            ],
            [
                'status' => 'draft',
                'external_sku' => $product->sku ?: 'SDF151',
                'channel_price' => self::TRENDYOL_PRICE,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }

    public function down(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            return;
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill(['marketplace_enabled' => false])->save();
        });
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak SDF 15/1 Temiz Su Dalgıç Pompa Monofaze 1.5 HP</h2>
<p>Sumak SDF 15/1, 1.5 HP (1.1 kW) monofaze temiz su dalgıç pompadır. Su temini, sulama, yağmur suyu tahliyesi ve süs havuzu uygulamalarında kullanılır. Tamamen su içinde çalışır; termik korumalı motoru aşırı ısınmaya karşı korur.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Marka</td><td>Sumak</td></tr>
<tr><td>Model</td><td>SDF 15/1</td></tr>
<tr><td>Motor gücü</td><td>1.5 HP (1.1 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maksimum debi</td><td>6-10 m³/h</td></tr>
<tr><td>Maksimum basma yüksekliği</td><td>31 m</td></tr>
<tr><td>Çıkış ölçüsü</td><td>1" (25 mm)</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Çark</td><td>Kapalı norly</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Süs havuzları ve şelaleler</li>
<li>Yağmur suyu tahliyesi</li>
<li>Tarım sulama sistemleri</li>
<li>Isı pompası uygulamaları</li>
<li>Temiz su transferi ve depo boşaltma</li>
</ul>

<h3>Paket ve kullanım notu</h3>
<p>Ürün 10 m kablo ve flatör ile birlikte gelir. Temiz su uygulamaları içindir; kumlu, çamurlu veya foseptik hatlarda kullanmayın. İlk çalışmada emiş/basınç hattı bağlantılarını sızdırmaz yapın; flatör serbest hareket edecek şekilde konumlandırın.</p>
HTML;
    }
};

