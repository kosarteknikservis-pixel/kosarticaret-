<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunSeoBulkOperationsCommand extends Command
{
    protected $signature = 'seo:bulk-run
                            {--skip-covers : Kategori OG gorsellerini atla}
                            {--skip-blog : Blog link enjeksiyonunu atla}
                            {--skip-guides : Buying guide seed atla}
                            {--skip-descriptions : Kategori description/faq import atla}';

    protected $description = 'SEO toplu islemleri sirasiyla calistirir (guide, description, blog link, OG kapak).';

    public function handle(): int
    {
        $steps = [];

        if (! $this->option('skip-guides')) {
            $steps[] = ['seo:seed-buying-guides', ['--force' => true]];
        }

        if (! $this->option('skip-descriptions')) {
            $export = database_path('seeders/category_seo_export.json');
            if (! is_readable($export)) {
                $this->warn('category_seo_export.json yok; build_category_seo_export.php calistiriliyor...');
                require database_path('seeders/build_category_seo_export.php');
            }

            $steps[] = ['seo:import-category-seo', ['--force' => true]];
        }

        if (! $this->option('skip-blog')) {
            $steps[] = ['seo:inject-blog-category-links', []];
        }

        if (! $this->option('skip-covers')) {
            $steps[] = ['seo:assign-category-covers', ['--force' => true]];
        }

        foreach ($steps as [$command, $options]) {
            $this->newLine();
            $this->info('▶ '.$command);

            $code = Artisan::call($command, $options, $this->output);
            if ($code !== self::SUCCESS) {
                $this->error("Basarisiz: {$command}");

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('SEO toplu islemler tamamlandi.');

        return self::SUCCESS;
    }
}
