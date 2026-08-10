<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Support\RichContent;
use Illuminate\Console\Command;

class SeedCategoryBuyingGuidesCommand extends Command
{
    protected $signature = 'seo:seed-buying-guides
                            {--force : Mevcut buying_guide alanlarinin ustune yazar}
                            {--path=* : Yalnizca belirtilen kategori yolu veya yollari guncellenir}';

    protected $description = 'Kategori satın alma rehberi içeriklerini config dosyasından veritabanına yazar.';

    public function handle(): int
    {
        $landings = config('category_buying_guides.landings', []);
        $paths = array_values(array_filter(array_map(
            static fn (string $path): string => trim($path, '/'),
            $this->option('path')
        )));

        if ($paths !== []) {
            $unknownPaths = array_diff($paths, array_keys($landings));
            if ($unknownPaths !== []) {
                foreach ($unknownPaths as $path) {
                    $this->error("Config kategorisi bulunamadi: {$path}");
                }

                return self::FAILURE;
            }

            $landings = array_intersect_key($landings, array_flip($paths));
        }

        $updated = 0;
        $skipped = 0;

        foreach ($landings as $path => $data) {
            $category = Category::resolveFromStorefrontPath($path);
            if ($category === null) {
                $this->warn("Kategori bulunamadi: {$path}");
                $skipped++;

                continue;
            }

            $guide = RichContent::normalize($data['buying_guide'] ?? null);
            if ($guide === null) {
                $skipped++;

                continue;
            }

            $existing = RichContent::normalize($category->buying_guide);
            if ($existing !== null && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            $category->buying_guide = $guide;

            if (empty($category->meta_description) && ! empty($data['subtitle'])) {
                $category->meta_description = (string) $data['subtitle'];
            }

            $category->save();
            $updated++;
            $this->line("Guncellendi: {$path}");
        }

        $this->info("Tamamlandi: {$updated} kategori guncellendi, {$skipped} atlandi.");

        return self::SUCCESS;
    }
}
