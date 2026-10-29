<?php

use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * Sumak SMJ220 Trendyol buybox'a girmiş; katalog görselinde başka satıcı etiketi (KAMPA).
 * Mevcut EAN (200…2326) içerik eşleşmesine bağlı. Yeni seri barkod + draft listing
 * ile createProducts açılır; kendi görsellerimiz kullanılır.
 *
 * Panelde eski barkodlu Trendyol kaydı arşivlenmeli.
 */
return new class extends Migration
{
    private const SLUG = 'sumak-smj-220-jet-paket-hidrofor-8-kat-16-daire-50-litre-tankli';

    /** Buybox’tan çıkış için 200… serisinden farklı önek. */
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
        $oldSku = (string) ($product->sku ?? 'SMJH220');

        // productMainId (=sku) da benzersiz olsun; buybox gruplamasını kırar.
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
