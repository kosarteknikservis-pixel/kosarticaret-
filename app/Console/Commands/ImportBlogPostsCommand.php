<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
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
                            {--force : Onay sormadan içe aktar}';

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
            $posts->each(function (array $post) {
                $this->importPost($post);
            });
        });

        $this->info('Blog import tamamlandı.');

        return self::SUCCESS;
    }

    private function importPost(array $post): void
    {
        $data = [
            'slug' => Str::slug($post['slug']),
            'title' => $post['title'] ?? '',
            'excerpt' => $post['excerpt'] ?? null,
            'content' => $post['content'] ?? '',
            'image' => $post['image'] ?? null,
            'image_alt' => $post['image_alt'] ?? null,
            'published_at' => $post['published_at'] ?? null,
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

    private function restoreImage(?array $imageFile): void
    {
        if (! $imageFile || blank($imageFile['path'] ?? null) || blank($imageFile['base64'] ?? null)) {
            return;
        }

        Storage::disk('public')->put($imageFile['path'], base64_decode($imageFile['base64'], true) ?: '');
    }
}
