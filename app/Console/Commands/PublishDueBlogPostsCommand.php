<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublishDueBlogPostsCommand extends Command
{
    protected $signature = 'blog:publish-due
                            {--dry-run : Yalnızca hangi yazıların yayınlanacağını göster}
                            {--all : Manifestteki tüm yazıları içe aktar (mevcut slug güncellenir)}
                            {--force : Onay sormadan içe aktar}';

    protected $description = 'Blog kuyruğundaki yazıları anında yayınlar (deploy ile canlıya gider)';

    public function handle(): int
    {
        $manifestPath = base_path('database/blog-queue/manifest.json');

        if (! File::exists($manifestPath)) {
            $this->error('Manifest bulunamadı: database/blog-queue/manifest.json');

            return self::FAILURE;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $entries = collect($manifest['posts'] ?? []);

        $due = $entries->filter(function (array $entry) {
            $file = base_path('database/blog-queue/'.($entry['file'] ?? ''));

            return File::exists($file) && $this->shouldPublish($entry, $file);
        })->values();

        if ($due->isEmpty()) {
            $this->info('Yayınlanacak blog yazısı yok.');

            return self::SUCCESS;
        }

        $this->line('Yayınlanacak: '.$due->count());

        foreach ($due as $index => $entry) {
            $file = base_path('database/blog-queue/'.($entry['file'] ?? ''));

            $this->line('- '.($entry['title'] ?? $entry['file']));

            if ($this->option('dry-run')) {
                continue;
            }

            $code = Artisan::call('blog:import', [
                'path' => 'database/blog-queue/'.($entry['file'] ?? ''),
                '--force' => $this->option('force'),
                '--from-queue' => true,
                '--publish-offset' => $index,
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
            $this->info('Blog yazıları anında yayınlandı.');
        }

        return self::SUCCESS;
    }

    private function shouldPublish(array $entry, string $file): bool
    {
        if ($this->option('all')) {
            return true;
        }

        $slug = $this->slugFromQueueFile($file);
        if ($slug === '') {
            return false;
        }

        $existing = BlogPost::query()->where('slug', $slug)->first();
        if ($existing === null) {
            return true;
        }

        return $existing->published_at === null || $existing->published_at->isFuture();
    }

    private function slugFromQueueFile(string $file): string
    {
        $payload = json_decode(File::get($file), true);
        if (! is_array($payload)) {
            return '';
        }

        $slug = $payload['posts'][0]['slug'] ?? '';

        return filled($slug) ? Str::slug($slug) : '';
    }
}
