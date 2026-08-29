<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportCategorySeoFromSeedersCommand extends Command
{
    protected $signature = 'seo:import-category-seo
                            {--force : Mevcut description/faq uzerine yazar}
                            {--dry-run : Sadece raporla}';

    protected $description = 'database/seeders/cat_seo JSON ciktisi ile kategori description/faq/meta gunceller.';

    public function handle(): int
    {
        $jsonPath = database_path('seeders/category_seo_export.json');
        if (! is_readable($jsonPath)) {
            $this->error('JSON bulunamadi: '.$jsonPath);
            $this->line('Once: php database/seeders/build_category_seo_export.php');

            return self::FAILURE;
        }

        /** @var list<array{id: int, description: string, faq: list<array{q: string, a: string}>, meta_title?: string, meta_description?: string}>|null $rows */
        $rows = json_decode(file_get_contents($jsonPath) ?: '', true);
        if (! is_array($rows)) {
            $this->error('Gecersiz JSON.');

            return self::FAILURE;
        }

        $updated = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $category = Category::query()->find($id);
            if ($category === null) {
                $missing++;
                $this->warn("Kategori yok: id={$id}");

                continue;
            }

            $description = trim((string) ($row['description'] ?? ''));
            $faq = is_array($row['faq'] ?? null) ? $row['faq'] : [];
            $metaTitle = trim((string) ($row['meta_title'] ?? ''));
            $metaDescription = trim((string) ($row['meta_description'] ?? ''));

            $hasContent = $description !== '' || $faq !== [];
            if (! $hasContent) {
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("✓ id={$id} {$category->nestedSlugPath()}");
                $updated++;

                continue;
            }

            $dirty = false;

            if ($description !== '' && ($this->option('force') || trim(strip_tags((string) $category->description)) === '')) {
                $category->description = $description;
                $dirty = true;
            }

            if ($faq !== [] && ($this->option('force') || empty($category->faq))) {
                $category->faq = $faq;
                $dirty = true;
            }

            if ($metaTitle !== '' && ($this->option('force') || blank($category->meta_title))) {
                $category->meta_title = $metaTitle;
                $dirty = true;
            }

            if ($metaDescription !== '' && ($this->option('force') || blank($category->meta_description))) {
                $category->meta_description = $metaDescription;
                $dirty = true;
            }

            if ($dirty) {
                $category->save();
                $updated++;
                $this->line("Guncellendi: id={$id} {$category->nestedSlugPath()}");
            } else {
                $skipped++;
            }
        }

        $this->info("Tamamlandi: {$updated} guncellendi, {$skipped} atlandi, {$missing} bulunamadi.");

        return self::SUCCESS;
    }
}
