<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Support\CategorySeoContentBuilder;
use App\Support\CategorySeoFacts;
use App\Support\RichContent;
use Illuminate\Console\Command;

final class EnrichCategorySeoContentCommand extends Command
{
    protected $signature = 'seo:enrich-categories
                            {--min=380 : Aciklama icin en dusuk kelime sayisi}
                            {--force : Mevcut iceriklerin ustune yazar}
                            {--path=* : Yalnizca belirtilen kategori yolu veya yollari}
                            {--dry-run : Veritabanina yazmadan sonucu gosterir}
                            {--guides : Bos satin alma rehberlerini de olusturur}';

    protected $description = 'Aktif kategorilere benzersiz teknik SEO aciklamalari ve satin alma rehberleri yazar.';

    public function handle(CategorySeoContentBuilder $builder): int
    {
        $min = max(1, (int) $this->option('min'));
        $requestedPaths = array_values(array_unique(array_filter(array_map(
            static fn (string $path): string => trim($path, '/'),
            $this->option('path')
        ))));
        $knownPaths = array_keys(CategorySeoFacts::all());

        if ($requestedPaths !== []) {
            $unknown = array_diff($requestedPaths, $knownPaths);
            if ($unknown !== []) {
                foreach ($unknown as $path) {
                    $this->error("SEO bilgisi bulunamadi: {$path}");
                }

                return self::FAILURE;
            }
        }

        $categories = Category::query()
            ->where('active', true)
            ->with('parent')
            ->orderBy('id')
            ->get();

        $updated = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($categories as $category) {
            $path = $category->nestedSlugPath();
            if ($requestedPaths !== [] && ! in_array($path, $requestedPaths, true)) {
                continue;
            }

            $built = $builder->build($category);
            if ($built === null) {
                if ($requestedPaths !== []) {
                    $this->warn("Atlandi, SEO bilgisi yok: {$path}");
                }
                $missing++;

                continue;
            }

            $oldWords = RichContent::wordCount($category->description);
            $newWords = $oldWords;
            $changed = false;

            if ($oldWords < $min || $this->option('force')) {
                $description = RichContent::normalize($built['description']);
                if ($description !== null) {
                    $category->description = $description;
                    $newWords = RichContent::wordCount($description);
                    $changed = true;
                }
            }

            if ($this->option('guides')) {
                $existingGuide = RichContent::normalize($category->buying_guide);
                if ($existingGuide === null || $this->option('force')) {
                    $guide = RichContent::normalize($built['buying_guide']);
                    if ($guide !== null) {
                        $category->buying_guide = $guide;
                        $changed = true;
                    }
                }
            }

            if (empty($category->meta_description) && $built['subtitle'] !== '') {
                $category->meta_description = $built['subtitle'];
                $changed = true;
            }

            if (! $changed) {
                $skipped++;
                $this->line("Atlandi: {$path} {$oldWords}w");

                continue;
            }

            if (! $this->option('dry-run')) {
                $category->save();
            }

            $updated++;
            $prefix = $this->option('dry-run') ? 'Planlandi' : 'Guncellendi';
            $this->line("{$prefix}: {$path} {$oldWords}w -> {$newWords}w");
        }

        $this->info("Tamamlandi: {$updated} guncelleme, {$skipped} atlama, {$missing} eslesmeyen aktif kategori.");

        return self::SUCCESS;
    }
}
