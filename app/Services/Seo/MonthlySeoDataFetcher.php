<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MonthlySeoDataFetcher
{
    public function __construct(private GscCategoryRankTracker $rankTracker) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(int $days): array
    {
        $credentials = $this->credentialsPath();
        $json = $this->runPythonFetcher($credentials, $days);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('B1 fetcher gecersiz JSON dondurdu.');
        }

        $queries = array_map(
            fn (array $row): array => [
                'query' => (string) ($row['query'] ?? ''),
                'clicks' => (int) ($row['clicks'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr' => (float) ($row['ctr'] ?? 0),
                'position' => (float) ($row['position'] ?? 0),
            ],
            $data['gsc']['top_queries'] ?? []
        );

        $data['gsc']['category_tracking'] = $this->rankTracker->track(
            $queries,
            config('seo_monitoring.category_keywords', [])
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function writeMonthlyBundle(array $report, ?string $month = null): string
    {
        $month ??= now()->format('Y-m');
        $dir = rtrim((string) config('google_seo.monthly_output_root'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$month;

        File::ensureDirectoryExists($dir);

        $jsonPath = $dir.DIRECTORY_SEPARATOR.'b1-report.json';
        file_put_contents(
            $jsonPath,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        $markdownPath = $dir.DIRECTORY_SEPARATOR.'RAPOR.md';
        file_put_contents($markdownPath, $this->buildMarkdownReport($report));

        $latestPath = storage_path('seo-reports/gsc-performance-latest.json');
        File::ensureDirectoryExists(dirname($latestPath));
        file_put_contents(
            $latestPath,
            json_encode($this->toWeeklyPerformanceShape($report), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        return $dir;
    }

    private function credentialsPath(): string
    {
        $path = (string) config('google_seo.credentials_path');

        if ($path === '' || ! is_readable($path)) {
            throw new RuntimeException(
                'Google SEO credentials bulunamadi. .env icinde GOOGLE_SEO_CREDENTIALS yolunu ayarlayin.'
            );
        }

        return $path;
    }

    private function runPythonFetcher(string $credentials, int $days): string
    {
        $script = base_path('scripts/seo/fetch_monthly_b1.py');
        if (! is_readable($script)) {
            throw new RuntimeException('B1 fetch script bulunamadi: '.$script);
        }

        $uvx = $this->resolveBinary([
            env('UVX_BINARY'),
            'C:\\Users\\PC\\.local\\bin\\uvx.exe',
            'uvx',
        ]);

        $python = $this->resolveBinary([
            env('PYTHON_BINARY'),
            'C:\\Users\\PC\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe',
        ], required: false);

        $command = [$uvx];
        if ($python !== null) {
            $command[] = '--python';
            $command[] = $python;
        }

        array_push(
            $command,
            '--with', 'google-auth',
            '--with', 'google-api-python-client',
            '--with', 'google-analytics-data',
            'python',
            $script,
            '--days',
            (string) $days,
        );

        $process = new Process($command, base_path(), [
            'GOOGLE_APPLICATION_CREDENTIALS' => $credentials,
            'GSC_SITE_URL' => (string) config('google_seo.gsc_site_url'),
            'GA4_PROPERTY_ID' => (string) config('google_seo.ga4_property_id'),
            'SEO_MONTHLY_PERIOD_DAYS' => (string) $days,
            'PYTHONIOENCODING' => 'utf-8',
        ], null, 180);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    /**
     * @param  list<string|null>  $candidates
     */
    private function resolveBinary(array $candidates, bool $required = true): ?string
    {
        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            if (is_file($candidate)) {
                return $candidate;
            }

            $which = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows'
                ? 'where '.escapeshellarg($candidate).' 2>nul'
                : 'command -v '.escapeshellarg($candidate).' 2>/dev/null'));

            if ($which !== '' && is_file(explode("\n", $which)[0])) {
                return explode("\n", $which)[0];
            }
        }

        if ($required) {
            throw new RuntimeException('Gerekli binary bulunamadi: '.implode(', ', array_filter($candidates)));
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function toWeeklyPerformanceShape(array $report): array
    {
        $queries = array_map(
            fn (array $row): array => [
                'query' => (string) $row['query'],
                'clicks' => (int) $row['clicks'],
                'impressions' => (int) $row['impressions'],
                'ctr' => (float) $row['ctr'],
                'position' => (float) $row['position'],
            ],
            $report['gsc']['top_queries'] ?? []
        );

        return [
            'imported_at' => now()->toIso8601String(),
            'source' => 'api:seo:fetch-monthly-data',
            'period' => $report['period'] ?? [],
            'queries' => $queries,
            'opportunities' => $report['gsc']['opportunities'] ?? [],
            'category_tracking' => $report['gsc']['category_tracking'] ?? [],
            'totals' => [
                'clicks' => (int) ($report['gsc']['totals']['clicks'] ?? 0),
                'impressions' => (int) ($report['gsc']['totals']['impressions'] ?? 0),
                'query_count' => count($queries),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function buildMarkdownReport(array $report): string
    {
        $period = $report['period'] ?? [];
        $gsc = $report['gsc'] ?? [];
        $ga4 = $report['ga4'] ?? [];
        $lines = [];

        $lines[] = '# Aylik SEO B1 Raporu — kosarticaret.com';
        $lines[] = '';
        $lines[] = '- Olusturulma: '.($report['generated_at'] ?? now()->toDateString());
        $lines[] = '- Donem: '.($period['start'] ?? '?').' → '.($period['end'] ?? '?').' ('.($period['days'] ?? '?').' gun)';
        $lines[] = '- GSC mülk: '.($gsc['site_url'] ?? '?');
        $lines[] = '- GA4 property: '.($ga4['property_id'] ?? '?');
        $lines[] = '';

        $lines[] = '## GSC ozet';
        $totals = $gsc['totals'] ?? [];
        $change = $gsc['change_pct'] ?? [];
        $lines[] = sprintf(
            '- Tiklama: **%d** (%s%% onceki doneme gore)',
            (int) ($totals['clicks'] ?? 0),
            $this->formatChange($change['clicks'] ?? null)
        );
        $lines[] = sprintf(
            '- Gosterim: **%d** (%s%% onceki doneme gore)',
            (int) ($totals['impressions'] ?? 0),
            $this->formatChange($change['impressions'] ?? null)
        );
        $lines[] = '';

        $lines[] = '### Top 10 sorgu';
        foreach (array_slice($gsc['top_queries'] ?? [], 0, 10) as $i => $row) {
            $lines[] = sprintf(
                '%d. %s — %d tiklama, poz. %.1f',
                $i + 1,
                $row['query'] ?? '',
                (int) ($row['clicks'] ?? 0),
                (float) ($row['position'] ?? 0)
            );
        }
        $lines[] = '';

        $lines[] = '### Top 10 sayfa';
        foreach (array_slice($gsc['top_pages'] ?? [], 0, 10) as $i => $row) {
            $lines[] = sprintf(
                '%d. %s — %d tiklama',
                $i + 1,
                $row['page'] ?? '',
                (int) ($row['clicks'] ?? 0)
            );
        }
        $lines[] = '';

        $lines[] = '### Cihaz kirilimi';
        foreach ($gsc['devices'] ?? [] as $row) {
            $lines[] = sprintf(
                '- %s: %d tiklama / %d gosterim',
                $row['device'] ?? '?',
                (int) ($row['clicks'] ?? 0),
                (int) ($row['impressions'] ?? 0)
            );
        }
        $lines[] = '';

        $lines[] = '### Sitemap';
        foreach ($gsc['sitemaps'] ?? [] as $sm) {
            if (isset($sm['error'])) {
                $lines[] = '- Hata: '.$sm['error'];
                continue;
            }
            $web = $sm['contents']['web']['submitted'] ?? '?';
            $lines[] = sprintf('- %s — gonderilen web URL: %s, hata: %s', $sm['path'] ?? '?', $web, $sm['errors'] ?? '0');
        }
        $lines[] = '';

        $lines[] = '## GA4 organik';
        $organic = $ga4['organic'] ?? [];
        $organicChange = $ga4['organic_change_pct'] ?? [];
        $lines[] = sprintf(
            '- Oturum: **%d** (%s%%)',
            (int) ($organic['sessions'] ?? 0),
            $this->formatChange($organicChange['sessions'] ?? null)
        );
        $lines[] = sprintf(
            '- Kullanici: **%d** (%s%%)',
            (int) ($organic['users'] ?? 0),
            $this->formatChange($organicChange['users'] ?? null)
        );
        $lines[] = '';

        $lines[] = '### Top organik acilis sayfalari';
        foreach (array_slice($ga4['landing_pages'] ?? [], 0, 10) as $i => $row) {
            $lines[] = sprintf(
                '%d. %s — %d oturum',
                $i + 1,
                $row['landing_page'] ?? '',
                (int) ($row['sessions'] ?? 0)
            );
        }
        $lines[] = '';

        $conv = $ga4['conversions'] ?? [];
        if (($conv['status'] ?? '') === 'ok') {
            $lines[] = '### Donusum / e-ticaret';
            $lines[] = sprintf('- %s (organik): **%d**', $conv['metric'] ?? 'metric', (int) ($conv['value'] ?? 0));
            $lines[] = '';
        } else {
            $lines[] = '### Donusum / e-ticaret';
            $lines[] = '- GA4 e-ticaret metrikleri alinamadi veya kurulu degil.';
            $lines[] = '';
        }

        $tracked = $gsc['category_tracking']['keywords'] ?? [];
        if ($tracked !== []) {
            $lines[] = '## Kategori kelime takibi';
            foreach ($tracked as $row) {
                if (($row['status'] ?? '') === 'no_data') {
                    $lines[] = '- '.$row['keyword'].' — veri yok';
                    continue;
                }
                $lines[] = sprintf(
                    '- %s — poz. %.1f (%s), %d tiklama',
                    $row['keyword'],
                    (float) ($row['position'] ?? 0),
                    $row['status'] ?? '',
                    (int) ($row['clicks'] ?? 0)
                );
            }
            $lines[] = '';
        }

        $lines[] = '## Manuel kontrol (API disi)';
        foreach ($report['manual_checks'] ?? [] as $item) {
            $lines[] = '- [ ] '.$item;
        }
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    private function formatChange(mixed $value): string
    {
        if ($value === null) {
            return 'n/a';
        }

        $num = (float) $value;

        return ($num > 0 ? '+' : '').number_format($num, 1, '.', '');
    }
}
