<?php

use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * Sumak SDF500 GF Trendyol buybox/katalog eşleşmesi: başka satıcı görseli.
 * Yeni 208… EAN + SKU -K ile createProducts; panelde eski barkodlu kaydı arşivle.
 */
return new class extends Migration
{
    private const SLUG = 'sumak-sdf500-gf-sifirdan-emisli-gizli-samandirali-drenaj-pompasi-05-hp';

    private const EAN_PREFIX = '208';

    public function up(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Ürün yok: '.self::SLUG);
        }

        $ean = $this->ean13FromProductId((int) $product->id);
        $oldBarcode = (string) ($product->barcode ?? '');
        $oldSku = (string) ($product->sku ?? 'SDF500GF');
        $newSku = str_ends_with($oldSku, '-K') ? $oldSku : ($oldSku.'-K');

        Product::withoutEvents(function () use ($product, $ean, $newSku): void {
            $product->forceFill([
                'barcode' => $ean,
                'sku' => $newSku,
            ])->save();
        });

        MarketplaceListing::query()
            ->where('product_id', $product->id)
            ->where('channel_key', 'trendyol')
            ->update([
                'status' => 'draft',
                'external_product_id' => null,
                'external_sku' => $newSku,
                'last_error' => 'Buybox unlock: eski barkod '.$oldBarcode.' → '.$ean.'; panelde eski kaydı arşivle.',
            ]);
    }

    public function down(): void
    {
        // Barkod geri alınmaz.
    }

    private function ean13FromProductId(int $productId): string
    {
        $body = self::EAN_PREFIX.str_pad((string) $productId, 9, '0', STR_PAD_LEFT);
        $body = substr($body, 0, 12);

        return $body.$this->ean13CheckDigit($body);
    }

    private function ean13CheckDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $d = (int) $twelveDigits[$i];
            $sum += ($i % 2 === 0) ? $d : $d * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }
};
