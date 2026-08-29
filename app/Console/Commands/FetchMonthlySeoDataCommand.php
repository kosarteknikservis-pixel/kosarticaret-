<?php

namespace App\Console\Commands;

use App\Services\Seo\MonthlySeoDataFetcher;
use Illuminate\Console\Command;

class FetchMonthlySeoDataCommand extends Command
{
    protected $signature = 'seo:fetch-monthly-data
                            {--days= : Donem gun sayisi (varsayilan: config google_seo.default_period_days)}
                            {--month= : Cikti klasoru YYYY-MM (varsayilan: bu ay)}';

    protected $description = 'GSC + GA4 API ile aylik B1 verisini ceker (manuel export yerine).';

    public function handle(MonthlySeoDataFetcher $fetcher): int
    {
        $days = (int) ($this->option('days') ?: config('google_seo.default_period_days', 90));
        $month = $this->option('month') ?: now()->format('Y-m');

        $this->info('B1 verisi cekiliyor ('.$days.' gun)...');

        try {
            $report = $fetcher->fetch($days);
            $dir = $fetcher->writeMonthlyBundle($report, $month);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $gsc = $report['gsc'] ?? [];
        $ga4 = $report['ga4'] ?? [];

        $this->newLine();
        $this->info('B1 paketi hazir: '.$dir);
        $this->line('  · b1-report.json');
        $this->line('  · RAPOR.md');
        $this->line('  · gsc-performance-latest.json (haftalik uyumlu ozet)');

        $this->newLine();
        $this->info('GSC: '.($gsc['totals']['clicks'] ?? 0).' tiklama, '.($gsc['totals']['impressions'] ?? 0).' gosterim');
        $change = $gsc['change_pct'] ?? [];
        $this->line('  Onceki doneme gore: tiklama '.($change['clicks'] ?? 'n/a').'%, gosterim '.($change['impressions'] ?? 'n/a').'%');

        $this->newLine();
        $this->info('GA4 organik: '.($ga4['organic']['sessions'] ?? 0).' oturum, '.($ga4['organic']['users'] ?? 0).' kullanici');
        $organicChange = $ga4['organic_change_pct'] ?? [];
        $this->line('  Onceki doneme gore: oturum '.($organicChange['sessions'] ?? 'n/a').'%');

        $tracked = $gsc['category_tracking']['keywords'] ?? [];
        if ($tracked !== []) {
            $this->newLine();
            $this->info('Kategori kelime takibi:');
            foreach ($tracked as $row) {
                if (($row['status'] ?? '') === 'no_data') {
                    $this->line('  · '.$row['keyword'].' — veri yok');
                    continue;
                }
                $this->line(sprintf(
                    '  · %s — poz. %.1f (%s) · %d tiklama',
                    $row['keyword'],
                    (float) $row['position'],
                    $row['status'],
                    (int) $row['clicks']
                ));
            }
        }

        $this->newLine();
        $this->comment('Manuel kontrol (API disi): GSC manuel islem, guvenlik, CWV ekran goruntusu, index export.');

        return self::SUCCESS;
    }
}
