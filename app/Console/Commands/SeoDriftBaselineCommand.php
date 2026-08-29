<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoDriftScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeoDriftBaselineCommand extends Command
{
    protected $signature = 'seo:drift-baseline
                            {--base-url= : Olculen site (ornek: https://kosarticaret.com)}';

    protected $description = 'SEO drift baseline olusturur (title, canonical, H1, schema).';

    public function handle(SeoDriftScanner $scanner): int
    {
        try {
            $baseUrl = SeoDriftScanner::resolveBaseUrl($this->option('base-url'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $urls = config('seo_monitoring.drift_urls', []);
        if ($urls === []) {
            $this->error('config/seo_monitoring.php drift_urls bos.');

            return self::FAILURE;
        }

        $this->info('Baseline taranıyor: '.$baseUrl);
        $report = $scanner->scan($baseUrl, $urls);
        $output = (string) config('seo_monitoring.drift_baseline_path');

        File::ensureDirectoryExists(dirname($output));
        file_put_contents(
            $output,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        $this->info('Baseline kaydedildi: '.$output);
        $this->line('Sayfa: '.count($report['pages']));

        return self::SUCCESS;
    }
}
