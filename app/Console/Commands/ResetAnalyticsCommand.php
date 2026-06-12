<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsVisitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ResetAnalyticsCommand extends Command
{
    protected $signature = 'analytics:reset {--force : Onay sormadan tüm analitik verileri sil}';

    protected $description = 'Müşteri hareketleri analitik verilerini sıfırlar (olaylar, ziyaretçiler, yarım sepetler).';

    public function handle(): int
    {
        if (! Schema::hasTable('analytics_events')) {
            $this->warn('Analitik tabloları bulunamadı.');

            return self::SUCCESS;
        }

        $events = AnalyticsEvent::query()->count();
        $visitors = AnalyticsVisitor::query()->count();
        $carts = AbandonedCart::query()->count();

        if ($events === 0 && $visitors === 0 && $carts === 0) {
            $this->info('Silinecek analitik veri yok.');

            return self::SUCCESS;
        }

        $this->line("Silinecek: {$events} olay, {$visitors} ziyaretçi, {$carts} sepet kaydı.");
        $this->warn('Siparişler silinmez; sipariş kaynak alanları korunur.');

        if (! $this->option('force') && ! $this->confirm('Tüm müşteri hareketleri verileri silinsin mi?')) {
            $this->info('İşlem iptal edildi.');

            return self::SUCCESS;
        }

        $deletedEvents = AnalyticsEvent::query()->delete();
        $deletedCarts = AbandonedCart::query()->delete();
        $deletedVisitors = AnalyticsVisitor::query()->delete();

        $this->info("Analitik sıfırlandı: {$deletedEvents} olay, {$deletedVisitors} ziyaretçi, {$deletedCarts} sepet kaydı silindi.");

        return self::SUCCESS;
    }
}
