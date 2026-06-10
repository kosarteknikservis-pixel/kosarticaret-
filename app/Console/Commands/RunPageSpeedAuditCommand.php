<?php

namespace App\Console\Commands;

use App\Services\PageSpeedInsightsService;
use App\Support\PageSpeedTargets;
use Illuminate\Console\Command;

class RunPageSpeedAuditCommand extends Command
{
    protected $signature = 'pagespeed:audit
                            {--force : Önbellek süresini yok say}
                            {--mobile-only : Yalnızca mobil}
                            {--desktop-only : Yalnızca masaüstü}';

    protected $description = 'Google PageSpeed Insights ile temel sayfaların hızını ölçer';

    public function handle(PageSpeedInsightsService $pageSpeed): int
    {
        if (! PageSpeedInsightsService::isConfigured()) {
            $this->error('PageSpeed API anahtarı tanımlı değil. Site ayarları → Entegrasyonlar bölümünden ekleyin.');

            return self::FAILURE;
        }

        $strategies = ['mobile', 'desktop'];
        if ($this->option('mobile-only')) {
            $strategies = ['mobile'];
        } elseif ($this->option('desktop-only')) {
            $strategies = ['desktop'];
        }

        $force = (bool) $this->option('force');
        $targets = PageSpeedTargets::resolve();

        foreach ($targets as $target) {
            foreach ($strategies as $strategy) {
                if (! $force) {
                    $existing = $pageSpeed->latestFor($target['key'], $strategy);
                    if ($pageSpeed->isFresh($existing)) {
                        $this->line("Atlandı (güncel): {$target['label']} · {$strategy}");

                        continue;
                    }
                }

                $this->info("Ölçülüyor: {$target['label']} · {$strategy}");
                $audit = $pageSpeed->auditAndStore($target, $strategy);

                if ($audit->error_message) {
                    $this->error($audit->error_message);

                    continue;
                }

                $this->line("Skor: {$audit->performance_score} · LCP {$audit->lcp_ms}ms");
            }
        }

        $this->info('PageSpeed denetimi bitti.');

        return self::SUCCESS;
    }
}
