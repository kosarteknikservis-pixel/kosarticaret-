<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoDriftScanner;
use App\Services\Seo\SeoRedirectChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeoCheckRedirectsCommand extends Command
{
    protected $signature = 'seo:check-redirects
                            {--base-url= : Test edilecek site}';

    protected $description = 'Legacy kategori URL yonlendirmelerini ve zincirleri kontrol eder.';

    public function handle(SeoRedirectChecker $checker): int
    {
        try {
            $baseUrl = SeoDriftScanner::resolveBaseUrl($this->option('base-url'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $checks = config('seo_monitoring.legacy_redirect_checks', []);
        if ($checks === []) {
            $this->error('config/seo_monitoring.php legacy_redirect_checks bos.');

            return self::FAILURE;
        }

        $this->info('Redirect kontrolu: '.$baseUrl);
        $report = $checker->check($baseUrl, $checks);
        $output = (string) config('seo_monitoring.redirect_report_path');

        File::ensureDirectoryExists(dirname($output));
        file_put_contents(
            $output,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        $failed = collect($report['results'])->where('ok', false);
        foreach ($report['results'] as $row) {
            $status = $row['ok'] ? 'OK' : 'HATA';
            $this->line("[{$status}] {$row['from']} → {$row['final_path']}");
            if (! $row['ok'] && filled($row['issue'])) {
                $this->line('       '.$row['issue']);
            }
        }

        $this->line('Rapor: '.$output);

        return $failed->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
