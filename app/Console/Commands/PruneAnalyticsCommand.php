<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsVisitor;
use Illuminate\Console\Command;

class PruneAnalyticsCommand extends Command
{
    protected $signature = 'analytics:prune {--days=180 : Kaç günden eski analitik veriler temizlensin}';

    protected $description = 'Eski müşteri davranışı analitik verilerini temizler.';

    public function handle(): int
    {
        $days = max(30, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $events = AnalyticsEvent::query()
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        $carts = AbandonedCart::query()
            ->where('last_activity_at', '<', $cutoff)
            ->whereIn('status', ['converted', 'emptied'])
            ->delete();

        $visitors = AnalyticsVisitor::query()
            ->where('last_seen_at', '<', $cutoff)
            ->whereDoesntHave('orders')
            ->delete();

        $this->info("Analitik temizlik tamamlandı: {$events} olay, {$carts} sepet, {$visitors} ziyaretçi silindi.");

        return self::SUCCESS;
    }
}
