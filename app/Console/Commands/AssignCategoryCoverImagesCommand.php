<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Support\CategoryCoverImageGenerator;
use Illuminate\Console\Command;

class AssignCategoryCoverImagesCommand extends Command
{
    protected $signature = 'seo:assign-category-covers
                            {--dry-run : Sadece islenecek kategorileri listele}
                            {--force : Mevcut gorselleri yenile}
                            {--path= : Yalnizca belirli kategori yolu (orn. su-pompalari/dalgic-pompalar)}';

    protected $description = 'Kategori OG/kapak gorsellerini markali sablon ile uretir (1200x630).';

    public function handle(CategoryCoverImageGenerator $generator): int
    {
        $query = Category::query()->where('active', true)->orderBy('sort_order');

        if ($path = $this->option('path')) {
            $category = Category::resolveFromStorefrontPath(trim($path, '/'));
            if ($category === null) {
                $this->error("Kategori bulunamadi: {$path}");

                return self::FAILURE;
            }

            $categories = collect([$category]);
        } else {
            if (! $this->option('force')) {
                $query->where(function ($q): void {
                    $q->whereNull('image')->orWhere('image', '');
                });
            }

            $categories = $query->get();
        }

        if ($categories->isEmpty()) {
            $this->info('Islenecek kategori yok.');

            return self::SUCCESS;
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            $storePath = $category->nestedSlugPath();

            if ($this->option('dry-run')) {
                $this->line("✓ {$storePath} → \"{$category->name}\"");
                $assigned++;

                continue;
            }

            $imagePath = $generator->generate($category->slug, (string) $category->name);
            if ($imagePath === null) {
                $this->warn("Atlandi (GD/font yok?): {$storePath}");
                $skipped++;

                continue;
            }

            $category->update(['image' => $imagePath]);
            $this->line("Gorsel uretildi: {$storePath}");
            $assigned++;
        }

        $this->info("Tamamlandi. Uretilen: {$assigned}, atlanan: {$skipped}.");

        return self::SUCCESS;
    }
}
