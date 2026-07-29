<?php

namespace App\Console\Commands;

use App\Services\Seo\GscPerformanceImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportGscPerformanceCommand extends Command
{
    protected $signature = 'seo:import-gsc-performance
                            {file? : GSC performans xlsx dosyasi}
                            {--output= : JSON cikti dosyasi (varsayilan: storage/seo-reports/gsc-performance-latest.json)}';

    protected $description = 'Google Search Console performans exportunu JSON ozete cevirir.';

    public function handle(GscPerformanceImporter $importer): int
    {
        $file = $this->argument('file')
            ?: $this->defaultInputFile();

        if ($file === null || ! is_readable($file)) {
            $this->error('GSC performans dosyasi bulunamadi.');
            $this->line('Once GSC -> Performans -> Disa aktar (xlsx) yapin.');
            $this->line('Dosyayi su klasore koyun: storage/seo-reports/inbox/');
            $this->line('Ornek: php artisan seo:import-gsc-performance storage/seo-reports/inbox/performans.xlsx');

            return self::FAILURE;
        }

        try {
            $report = $importer->import($file);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $output = $this->option('output')
            ?: storage_path('seo-reports/gsc-performance-latest.json');

        File::ensureDirectoryExists(dirname($output));
        file_put_contents(
            $output,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        $this->info('GSC performans ozeti olusturuldu: '.$output);
        $this->line('Sorgu: '.$report['totals']['query_count']);
        $this->line('Gosterim: '.$report['totals']['impressions']);
        $this->line('Tiklama: '.$report['totals']['clicks']);
        $this->line('Ilk sayfa firsati: '.count($report['opportunities']['near_first_page']));
        $this->line('Tiklamasiz yuksek gosterim: '.count($report['opportunities']['high_impressions_no_clicks']));

        return self::SUCCESS;
    }

    private function defaultInputFile(): ?string
    {
        $inbox = storage_path('seo-reports/inbox');
        if (! is_dir($inbox)) {
            return null;
        }

        $files = array_merge(
            glob($inbox.'/*.xlsx') ?: [],
            glob($inbox.'/*.xls') ?: []
        );

        if ($files === []) {
            return null;
        }

        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return $files[0];
    }
}
