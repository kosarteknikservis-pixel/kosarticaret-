<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Blog\BlogCoverImageService;
use Illuminate\Console\Command;

class AssignBlogCoverImagesCommand extends Command
{
    protected $signature = 'blog:assign-covers
                            {--dry-run : Sadece başlıkları göster}
                            {--force : Mevcut kapak görsellerini de yenile}
                            {--slug= : Yalnızca belirli blog slug}';

    protected $description = 'Blog yazılarına başlık metinli 960x540 kapak görseli üretir';

    public function handle(BlogCoverImageService $service): int
    {
        $query = BlogPost::query()->published()->orderBy('published_at');

        if ($slug = $this->option('slug')) {
            $query->where('slug', $slug);
        } elseif (! $this->option('force')) {
            $query->where(function ($q): void {
                $q->whereNull('image')->orWhere('image', '');
            });
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $this->info('İşlenecek blog yazısı yok.');

            return self::SUCCESS;
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($posts as $post) {
            if ($this->option('dry-run')) {
                $title = $service->previewTitle($post);
                $this->line("✓ {$post->slug} → \"{$title}\"");
                $assigned++;

                continue;
            }

            if ($service->assign($post, (bool) $this->option('force'))) {
                $this->line("Kapak üretildi: {$post->slug}");
                $assigned++;
            } else {
                $this->warn("Atlandı: {$post->slug}");
                $skipped++;
            }
        }

        $this->info("Tamamlandı. Üretilen: {$assigned}, atlanan: {$skipped}.");

        return self::SUCCESS;
    }
}
