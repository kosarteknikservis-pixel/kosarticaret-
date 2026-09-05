<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Support\LegalPagesContent;
use Illuminate\Console\Command;

class SeedLegalPagesCommand extends Command
{
    protected $signature = 'seo:seed-legal-pages
                            {--force : Mevcut icerigin ustune yazar}
                            {--slug= : Yalnizca belirli slug}';

    protected $description = 'Yasal ve bilgilendirme sayfalarini kurumsal icerikle doldurur.';

    public function handle(): int
    {
        $pages = LegalPagesContent::all();
        $only = $this->option('slug');
        if (is_string($only) && $only !== '') {
            $pages = array_values(array_filter(
                $pages,
                static fn (array $page): bool => $page['slug'] === $only
            ));
            if ($pages === []) {
                $this->error("Slug bulunamadi: {$only}");

                return self::FAILURE;
            }
        }

        $updated = 0;
        $skipped = 0;

        foreach ($pages as $data) {
            $page = Page::query()->firstOrNew(['slug' => $data['slug']]);
            $existingLen = strlen(trim(strip_tags((string) $page->content)));
            $isThin = $existingLen < 250;

            if ($page->exists && ! $isThin && ! $this->option('force')) {
                $this->line("Atlandi (dolu): {$data['slug']} ({$existingLen} karakter)");
                $skipped++;

                continue;
            }

            $page->fill([
                'title' => $data['title'],
                'content' => $data['content'],
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'published' => $data['published'],
                'sort_order' => $data['sort_order'],
            ]);
            $page->save();

            $updated++;
            $this->line('Guncellendi: '.$data['slug']);
        }

        $this->info("Tamamlandi: {$updated} sayfa yazildi, {$skipped} atlandi.");

        return self::SUCCESS;
    }
}
