<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoDriftScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeoDriftCheckCommand extends Command
{
    protected $signature = 'seo:drift-check
                            {--base-url= : Olculen site}
                            {--fail-on-regression : Regresyon varsa exit code 1}';

    protected $description = 'Mevcut SEO sinyallerini baseline ile karsilastirir.';

    public function handle(SeoDriftScanner $scanner): int
    {
        $baselinePath = (string) config('seo_monitoring.drift_baseline_path');
        if (! is_readable($baselinePath)) {
            $this->error('Baseline bulunamadi. Once: php artisan seo:drift-baseline --base-url=https://kosarticaret.com');
            $this->line('Beklenen dosya: '.$baselinePath);

            return self::FAILURE;
        }

        try {
            $baseUrl = SeoDriftScanner::resolveBaseUrl($this->option('base-url'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $baseline = json_decode(file_get_contents($baselinePath) ?: '', true);
        if (! is_array($baseline)) {
            $this->error('Baseline JSON okunamadi.');

            return self::FAILURE;
        }

        $urls = config('seo_monitoring.drift_urls', []);
        $this->info('Drift kontrolu: '.$baseUrl);
        $current = $scanner->scan($baseUrl, $urls);
        $regressions = $scanner->diff($baseline, $current);

        $report = [
            'checked_at' => now()->toIso8601String(),
            'base_url' => $baseUrl,
            'baseline_scanned_at' => $baseline['scanned_at'] ?? null,
            'regression_count' => count($regressions),
            'regressions' => $regressions,
            'current' => $current,
        ];

        $output = (string) config('seo_monitoring.drift_report_path');
        File::ensureDirectoryExists(dirname($output));
        file_put_contents(
            $output,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        if ($regressions === []) {
            $this->info('Regresyon yok. Rapor: '.$output);

            return self::SUCCESS;
        }

        $this->warn('Regresyon: '.count($regressions).' sayfa');
        foreach ($regressions as $item) {
            $this->line('- '.$item['key'].' ('.$item['url'].')');
            foreach ($item['changes'] as $change) {
                $this->line('  · '.$change['field']);
            }
        }

        $this->line('Detay: '.$output);

        return $this->option('fail-on-regression') ? self::FAILURE : self::SUCCESS;
    }
}
