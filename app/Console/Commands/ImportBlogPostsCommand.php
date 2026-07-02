<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Seo\UrlIndexingNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImportBlogPostsCommand extends Command
{
    protected $signature = 'blog:import
                            {path=database/blog-export.json : İçe aktarılacak JSON dosyası}
                            {--dry-run : Yazmadan önizleme yap}
                            {--force : Onay sormadan içe aktar}
                            {--from-queue : blog-queue kaynağı; anında yayınla (gelecek tarih kullanma)}
                            {--publish-offset=0 : Aynı batch içinde liste sırası için dakika geri kaydır}';

    protected $description = 'Blog export JSON dosyasını canlı veritabanına slug bazlı ekler veya günceller';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! File::exists($path)) {
            $this->error("Dosya bulunamadı: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode(File::get($path), true);

        if (! is_array($payload) || ($payload['type'] ?? null) !== 'kosar-blog-export') {
            $this->error('Geçersiz blog export dosyası.');

            return self::FAILURE;
        }

        $posts = collect($payload['posts'] ?? [])->filter(fn ($post) => filled($post['slug'] ?? null));

        if ($posts->isEmpty()) {
            $this->warn('İçe aktarılacak blog yazısı bulunamadı.');

            return self::SUCCESS;
        }

        $existingSlugs = BlogPost::query()
            ->whereIn('slug', $posts->pluck('slug')->all())
            ->pluck('slug')
            ->all();

        $updateCount = $posts->whereIn('slug', $existingSlugs)->count();
        $createCount = $posts->count() - $updateCount;

        $this->line("Dosya: {$path}");
        $this->line("Toplam: {$posts->count()}");
        $this->line("Eklenecek: {$createCount}");
        $this->line("Güncellenecek: {$updateCount}");

        if ($this->option('dry-run')) {
            $this->info('Dry-run tamamlandı. Veritabanına yazılmadı.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Blog yazıları slug bazlı eklenecek/güncellenecek. Devam?', true)) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($posts) {
            $posts->each(function (array $post, int $index) {
                $this->importPost($post, $index);
            });
        });

        $this->notifyIndexing($posts);

        $this->info('Blog import tamamlandı.');

        return self::SUCCESS;
    }

    private function importPost(array $post, int $index = 0): void
    {
        $data = [
            'slug' => Str::slug($post['slug']),
            'title' => $post['title'] ?? '',
            'excerpt' => $post['excerpt'] ?? null,
            'content' => $post['content'] ?? '',
            'image' => $post['image'] ?? null,
            'image_alt' => $post['image_alt'] ?? null,
            'published_at' => $this->resolvePublishedAt($post, $index),
            'published' => (bool) ($post['published'] ?? true),
            'meta_title' => $post['meta_title'] ?? null,
            'meta_description' => $post['meta_description'] ?? null,
            'tags' => $post['tags'] ?? [],
            'translations' => $post['translations'] ?? [],
        ];

        if ($data['title'] === '' || $data['content'] === '') {
            throw new RuntimeException("Eksik blog verisi: {$data['slug']}");
        }

        $this->restoreImage($post['image_file'] ?? null);

        BlogPost::query()->updateOrCreate(
            ['slug' => $data['slug']],
            $data,
        );
    }

    private function resolvePublishedAt(array $post, int $index): Carbon
    {
        if ($this->option('from-queue')) {
            $slug = Str::slug($post['slug'] ?? '');
            $existing = $slug !== ''
                ? BlogPost::query()->where('slug', $slug)->first()
                : null;

            if ($existing?->published_at && ! $existing->published_at->isFuture()) {
                return $existing->published_at;
            }

            $batchOffset = max(0, (int) $this->option('publish-offset'));
            $fileOffset = max(0, $index);

            return now()->subMinutes($batchOffset + $fileOffset);
        }

        $raw = $post['published_at'] ?? null;
        if (blank($raw)) {
            return now();
        }

        $parsed = Carbon::parse($raw);

        return $parsed->isFuture() ? now() : $parsed;
    }

    private function restoreImage(?array $imageFile): void
    {
        if (! $imageFile || blank($imageFile['path'] ?? null) || blank($imageFile['base64'] ?? null)) {
            return;
        }

        Storage::disk('public')->put($imageFile['path'], base64_decode($imageFile['base64'], true) ?: '');
    }

    /** @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $posts */
    private function notifyIndexing($posts): void
    {
        $urls = $posts
            ->filter(fn (array $post) => (bool) ($post['published'] ?? true))
            ->map(fn (array $post) => route('blog.show', ['post' => Str::slug($post['slug'])], absolute: true))
            ->values()
            ->all();

        if ($urls === []) {
            return;
        }

        app(UrlIndexingNotifier::class)->submit($urls);
    }
}
