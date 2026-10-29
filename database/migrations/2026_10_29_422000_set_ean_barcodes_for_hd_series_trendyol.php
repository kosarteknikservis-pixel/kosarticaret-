<?php

use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * Reddedilmiş HD500 barkodu (HD500 metin) ile tekrar create/update kilitleniyor.
 * EAN-13 barkoda geçerek yeni Trendyol create açılır; paneldeki eski red kayıt arşivlenebilir.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const SLUGS = [
        'sumak-hd-500-drenaj-dalgic-pompa-acik-carkli-monofaze-220v-370-watt',
        'sumak-hd-1300-2-flatorlu-kirli-su-foseptik-dalgic-pompa-1-hp',
        'sumak-hd-1700-2-flatorlu-kirli-su-foseptik-dalgic-pompa-15-hp',
    ];

    public function up(): void
    {
        foreach (self::SLUGS as $slug) {
            $product = Product::query()->where('slug', $slug)->first();
            if ($product === null) {
                continue;
            }

            $ean = $this->ean13FromProductId((int) $product->id);
            $old = (string) ($product->barcode ?? '');
            if (preg_match('/^\d{13}$/', $old) && $this->isValidEan13($old)) {
                continue;
            }

            Product::withoutEvents(function () use ($product, $ean): void {
                $product->forceFill(['barcode' => $ean])->save();
            });

            MarketplaceListing::query()
                ->where('product_id', $product->id)
                ->where('channel_key', 'trendyol')
                ->update([
                    'status' => 'draft',
                    'external_product_id' => null,
                    'last_error' => null,
                ]);
        }
    }

    public function down(): void
    {
        // Barkod geri alınmaz.
    }

    private function ean13FromProductId(int $productId): string
    {
        $body = '200'.str_pad((string) $productId, 9, '0', STR_PAD_LEFT);
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

    private function isValidEan13(string $code): bool
    {
        if (! preg_match('/^\d{13}$/', $code)) {
            return false;
        }

        return $this->ean13CheckDigit(substr($code, 0, 12)) === (int) $code[12];
    }
};
