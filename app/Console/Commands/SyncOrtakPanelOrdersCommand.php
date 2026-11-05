<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrtakPanel\OrtakPanelOrderSyncService;
use Illuminate\Console\Command;

class SyncOrtakPanelOrdersCommand extends Command
{
    protected $signature = 'orders:sync-ortak-panel
                            {--order= : Sipariş numarası (örn. KOS-TO19UW)}
                            {--pending : panel_synced_at boş website siparişlerini gönder}
                            {--limit=50 : Pending modunda maksimum kayıt}
                            {--dry-run : Gönderim yapmadan listele}';

    protected $description = 'Website siparişlerini ortak panele (panel.kosarvantilator.com) gönderir';

    public function handle(OrtakPanelOrderSyncService $sync): int
    {
        if (! $sync->isEnabled()) {
            $this->error('Ortak panel senkronu kapalı veya yapılandırılmamış.');

            return self::FAILURE;
        }

        $orderNumber = trim((string) $this->option('order'));
        $pending = (bool) $this->option('pending');
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));

        if ($orderNumber === '' && ! $pending) {
            $this->error('--order=SIPARIS_NO veya --pending kullanın.');

            return self::FAILURE;
        }

        $query = Order::query()->websiteChannel()->with('items')->orderBy('id');

        if ($orderNumber !== '') {
            $query->where('order_number', $orderNumber);
        } else {
            $query->whereNull('panel_synced_at')
                ->where('payment_status', 'basarili')
                ->excludePendingPayment()
                ->limit($limit);
        }

        $orders = $query->get();
        if ($orders->isEmpty()) {
            $this->info('Gönderilecek sipariş yok.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $skip = 0;

        foreach ($orders as $order) {
            if ($dryRun) {
                $this->line("[dry-run] {$order->order_number} — {$order->customer_name} — {$order->total}");
                $skip++;

                continue;
            }

            // Tek sipariş zorlamasında daha önce işaretlenmiş olsa bile tekrar dene.
            if ($orderNumber !== '' && $order->panel_synced_at) {
                $order->forceFill(['panel_synced_at' => null])->saveQuietly();
            }

            $result = $sync->sync($order->fresh('items'));
            if ($result['skipped'] ?? false) {
                $this->warn("Atlandı: {$order->order_number}");
                $skip++;

                continue;
            }

            if ($result['ok'] ?? false) {
                $panelId = $result['panel_id'] ?? null;
                $this->info("OK: {$order->order_number}".($panelId ? " → panel #{$panelId}" : ''));
                $ok++;
            } else {
                $this->error("FAIL: {$order->order_number} — ".($result['error'] ?? 'bilinmeyen'));
                $fail++;
            }
        }

        $this->line("Özet: ok={$ok} fail={$fail} skip={$skip}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
