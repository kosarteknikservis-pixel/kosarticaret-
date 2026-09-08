<?php

namespace App\Console\Commands;

use App\Models\CompetitorOffer;
use App\Services\Pricing\CompetitorPricingService;
use Illuminate\Console\Command;

class RefreshCompetitorPricesCommand extends Command
{
    protected $signature = 'pricing:refresh-competitors
                            {--offer= : Tek bir teklif ID}
                            {--limit=50 : En fazla kaç aktif teklif güncellensin}
                            {--approved-only : Yalnızca onaylı eşleşmeler}';

    protected $description = 'Aktif rakip tekliflerinin fiyatlarını çeker (otomatik fiyat uygulamaz).';

    public function handle(CompetitorPricingService $pricing): int
    {
        $query = CompetitorOffer::query()->where('active', true)->orderBy('id');

        if ($this->option('offer')) {
            $query->whereKey((int) $this->option('offer'));
        }

        if ($this->option('approved-only')) {
            $query->where('match_status', CompetitorOffer::STATUS_APPROVED);
        }

        $limit = max(1, (int) $this->option('limit'));
        $offers = $query->limit($limit)->get();

        if ($offers->isEmpty()) {
            $this->warn('Güncellenecek teklif yok.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($offers as $offer) {
            $offer = $pricing->refreshOffer($offer);
            if ($offer->last_error) {
                $fail++;
                $this->line("✗ #{$offer->id} {$offer->competitor_name}: {$offer->last_error}");
            } else {
                $ok++;
                $this->line('✓ #'.$offer->id.' '.$offer->competitor_name.': '.number_format((float) $offer->last_price, 2, ',', '.').' ₺');
            }
        }

        $this->info("Tamam: {$ok} başarılı, {$fail} hatalı.");

        return self::SUCCESS;
    }
}
