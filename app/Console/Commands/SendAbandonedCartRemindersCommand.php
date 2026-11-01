<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCartReminderMail;
use App\Models\AbandonedCart;
use App\Support\MailSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartRemindersCommand extends Command
{
    protected $signature = 'carts:send-abandoned-reminders {--hours=6 : Son aktiviteden sonra kaç saat beklesin} {--dry-run : Sadece say}';

    protected $description = 'E-postası bilinen terk edilmiş sepetlere hatırlatma gönderir';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $dryRun = (bool) $this->option('dry-run');

        $query = AbandonedCart::query()
            ->whereIn('status', ['active', 'checkout'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('item_count', '>', 0)
            ->whereNull('reminded_at')
            ->where('last_activity_at', '<=', now()->subHours($hours));

        $count = $query->count();
        $this->info("Aday sepet: {$count}");

        if ($dryRun || $count === 0) {
            return self::SUCCESS;
        }

        if (! MailSettings::isConfigured()) {
            $this->error('SMTP ayarları yapılandırılmamış.');

            return self::FAILURE;
        }

        MailSettings::apply();
        $sent = 0;

        $query->orderBy('id')->chunkById(50, function ($carts) use (&$sent): void {
            foreach ($carts as $cart) {
                try {
                    Mail::to($cart->email)->send(new AbandonedCartReminderMail($cart));
                    $cart->update(['reminded_at' => now()]);
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('abandoned cart reminder failed', [
                        'cart_id' => $cart->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Gönderilen: {$sent}");

        return self::SUCCESS;
    }
}
