<?php

namespace App\Console\Commands;

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Console\Command;

class SubmitIndexingCommand extends Command
{
    protected $signature = 'seo:submit-indexing
                            {urls?* : Gönderilecek tam URL listesi}
                            {--sync : Kuyruk kullanmadan hemen gönder}';

    protected $description = 'Yeni veya güncellenen URL\'leri IndexNow / Google Indexing API ile bildirir';

    public function handle(UrlIndexingNotifier $notifier): int
    {
        $urls = $this->argument('urls') ?? [];

        if ($urls === []) {
            $this->error('En az bir URL girin. Örnek: php artisan seo:submit-indexing https://example.com/blog/yazi');

            return self::FAILURE;
        }

        if (! $notifier->isActive()) {
            $this->warn('IndexNow veya Google Indexing etkin değil. Panelden IndexNow\'u açın veya .env ayarlarını kontrol edin.');

            return self::FAILURE;
        }

        $notifier->submit($urls, queue: ! $this->option('sync'));

        $this->info(count($urls).' URL index bildirimi kuyruğa alındı.');

        return self::SUCCESS;
    }
}
