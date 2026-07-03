<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Support\RichContent;
use Illuminate\Console\Command;

class SeedBrandSeoCommand extends Command
{
    protected $signature = 'seo:seed-brands {--force : Mevcut marka SEO alanlarinin ustune yazar}';

    protected $description = 'Marka sayfasi SEO iceriklerini config dosyasindan veritabanina yazar.';

    public function handle(): int
    {
        $entries = config('brand_seo', []);
        $updated = 0;
        $skipped = 0;

        foreach ($entries as $slug => $data) {
            $brand = Brand::query()->where('slug', $slug)->first();
            if ($brand === null) {
                $this->warn("Marka bulunamadi: {$slug}");
                $skipped++;

                continue;
            }

            $description = RichContent::normalize($data['description'] ?? null);
            $metaTitle = trim((string) ($data['meta_title'] ?? ''));
            $metaDescription = trim((string) ($data['meta_description'] ?? ''));
            $faq = $data['faq'] ?? null;

            $hasContent = $description !== null || $metaTitle !== '' || $metaDescription !== '' || is_array($faq);
            if (! $hasContent) {
                $skipped++;

                continue;
            }

            $hasExisting = filled($brand->description) || filled($brand->meta_title) || filled($brand->meta_description) || filled($brand->faq);
            if ($hasExisting && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            if ($description !== null) {
                $brand->description = $description;
            }

            if ($metaTitle !== '') {
                $brand->meta_title = $metaTitle;
            }

            if ($metaDescription !== '') {
                $brand->meta_description = $metaDescription;
            }

            if (is_array($faq)) {
                $brand->faq = $faq;
            }

            $brand->save();
            $updated++;
            $this->line("Guncellendi: {$slug}");
        }

        $this->info("Tamamlandi: {$updated} marka guncellendi, {$skipped} atlandi.");

        return self::SUCCESS;
    }
}
