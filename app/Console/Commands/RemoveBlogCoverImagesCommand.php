<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Blog\BlogCoverImageService;
use Illuminate\Console\Command;

class RemoveBlogCoverImagesCommand extends Command
{
    protected $signature = 'blog:remove-covers
                            {--slug= : Yalnızca belirli blog slug}
                            {--force : Onay sormadan sil}';

    protected $description = 'Blog kapak görsellerini kaldırır (image alanını boşaltır)';

    public function handle(BlogCoverImageService $service): int
    {
        $query = BlogPost::query()
            ->published()
            ->whereNotNull('image')
            ->where('image', '!=', '');

        if ($slug = $this->option('slug')) {
            $query->where('slug', $slug);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $this->info('Silinecek kapak görseli yok.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm($posts->count().' blog kapak görseli kaldırılsın mı?', false)) {
            return self::SUCCESS;
        }

        $removed = 0;
        foreach ($posts as $post) {
            if ($service->remove($post)) {
                $this->line("Kaldırıldı: {$post->slug}");
                $removed++;
            }
        }

        $this->info("Toplam kaldırılan: {$removed}.");

        return self::SUCCESS;
    }
}
