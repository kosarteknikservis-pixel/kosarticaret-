<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PublishDueBlogPostsCommand extends Command
{
    protected $signature = 'blog:publish-due
                            {--dry-run : Yalnızca hangi yazıların yayınlanacağını göster}
                            {--all : Manifestteki tüm yazıları içe aktar (tarih filtresi yok)}
                            {--force : Onay sormadan içe aktar}';

    protected $description = 'Blog kuyruğunda vadesi gelen yazıları slug bazlı yayınlar';

    public function handle(): int
    {
        $manifestPath = base_path('database/blog-queue/manifest.json');

        if (! File::exists($manifestPath)) {
            $this->error('Manifest bulunamadı: database/blog-queue/manifest.json');

            return self::FAILURE;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $entries = collect($manifest['posts'] ?? []);

        if ($this->option('all')) {
            $due = $entries;
        } else {
            $today = now()->toDateString();
            $due = $entries->filter(function (array $entry) use ($today) {
                $publishOn = $entry['publish_on'] ?? null;

                return filled($publishOn) && $publishOn <= $today;
            });
        }

        if ($due->isEmpty()) {
            $this->info('Yayınlanacak blog yazısı yok.');

            return self::SUCCESS;
        }

        if (! $this->option('all')) {
            $this->line('Tarih: '.now()->toDateString());
        }
        $this->line('Yayınlanacak: '.$due->count());

        foreach ($due as $entry) {
            $file = base_path('database/blog-queue/'.($entry['file'] ?? ''));

            if (! File::exists($file)) {
                $this->warn('Dosya henüz hazır değil: '.($entry['file'] ?? '?'));

                continue;
            }

            $this->line('- '.($entry['title'] ?? $entry['file']));

            if ($this->option('dry-run')) {
                continue;
            }

            $code = Artisan::call('blog:import', [
                'path' => 'database/blog-queue/'.($entry['file'] ?? ''),
                '--force' => $this->option('force'),
            ]);

            if ($code !== self::SUCCESS) {
                $this->error('Import başarısız: '.($entry['file'] ?? '?'));
                $output = trim(Artisan::output());
                if ($output !== '') {
                    $this->line($output);
                }

                return self::FAILURE;
            }

            $output = trim(Artisan::output());
            if ($output !== '') {
                $this->line($output);
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run tamamlandı.');
        } else {
            $this->info('Vadesi gelen blog yazıları işlendi.');
        }

        return self::SUCCESS;
    }
}
