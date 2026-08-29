<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Seo\BlogCategoryLinkInjector;
use Illuminate\Console\Command;

class InjectBlogCategoryLinksCommand extends Command
{
    protected $signature = 'seo:inject-blog-category-links
                            {--dry-run : Veritabanina yazmadan raporla}
                            {--slug= : Yalnizca belirli blog slug}
                            {--limit=0 : Islenecek maksimum yazi (0 = sinirsiz)}';

    protected $description = 'Blog yazilarinin govde metnine kontekstuel kategori linkleri ekler.';

    public function handle(BlogCategoryLinkInjector $injector): int
    {
        $query = BlogPost::query()->published()->orderBy('published_at');

        if ($slug = $this->option('slug')) {
            $query->where('slug', $slug);
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $posts = $query->get();
        if ($posts->isEmpty()) {
            $this->info('Islenecek blog yazisi yok.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($posts as $post) {
            $result = $injector->inject((string) $post->content);
            if ($result['added'] === []) {
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("{$post->slug}: ".implode(', ', $result['added']));
                $updated++;

                continue;
            }

            $post->update(['content' => $result['body']]);
            $this->line("Guncellendi: {$post->slug} (+".count($result['added']).' link)');
            $updated++;
        }

        $mode = $this->option('dry-run') ? 'dry-run' : 'yazildi';
        $this->info("Tamamlandi ({$mode}): {$updated} guncellendi, {$skipped} atlandi.");

        return self::SUCCESS;
    }
}
