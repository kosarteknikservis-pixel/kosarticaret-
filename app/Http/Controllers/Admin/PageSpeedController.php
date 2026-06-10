<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSpeedAudit;
use App\Services\PageSpeedInsightsService;
use App\Support\PageSpeedAuditUrl;
use App\Support\PageSpeedTargets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageSpeedController extends Controller
{
    public function index(PageSpeedInsightsService $pageSpeed): View
    {
        $targets = PageSpeedTargets::resolve();
        $latest = [];

        foreach ($targets as $target) {
            foreach (['mobile', 'desktop'] as $strategy) {
                $audit = PageSpeedAudit::query()
                    ->where('page_key', $target['key'])
                    ->where('strategy', $strategy)
                    ->latest('measured_at')
                    ->first();

                $latest[$target['key']][$strategy] = $audit;
            }
        }

        $history = PageSpeedAudit::query()
            ->whereNull('error_message')
            ->latest('measured_at')
            ->take(20)
            ->get();

        return view('admin.performance.pagespeed', [
            'configured' => PageSpeedInsightsService::isConfigured(),
            'auditBaseUrl' => PageSpeedAuditUrl::base(),
            'auditUrlReady' => PageSpeedAuditUrl::isConfigured(),
            'targets' => $targets,
            'latest' => $latest,
            'history' => $history,
            'cacheMinutes' => (int) config('kosar.pagespeed.cache_minutes', 360),
            'reportUrl' => 'https://pagespeed.web.dev/',
        ]);
    }

    public function run(Request $request, PageSpeedInsightsService $pageSpeed): RedirectResponse
    {
        if (! PageSpeedInsightsService::isConfigured()) {
            return back()->withErrors([
                'pagespeed' => 'Önce Site ayarları → Entegrasyonlar bölümüne Google PageSpeed API anahtarını ekleyin.',
            ]);
        }

        $data = $request->validate([
            'page_key' => ['nullable', 'string', 'max:64'],
            'strategy' => ['nullable', 'in:mobile,desktop,both'],
            'force' => ['nullable', 'boolean'],
        ]);

        if (! PageSpeedAuditUrl::isConfigured()) {
            return back()->withErrors([
                'pagespeed' => 'Google sunuculari localhost adresine erisemez. Site ayarlari → Entegrasyonlar → Google PageSpeed bolumune canli site adresini girin (ornek: https://kosarticaret.com).',
            ]);
        }

        @set_time_limit(600);

        $targets = collect(PageSpeedTargets::resolve())
            ->when(filled($data['page_key'] ?? null), fn ($collection) => $collection->where('key', $data['page_key']))
            ->values();

        if ($targets->isEmpty()) {
            return back()->withErrors(['pagespeed' => 'Ölçülecek sayfa bulunamadı.']);
        }

        $strategies = match ($data['strategy'] ?? 'both') {
            'mobile' => ['mobile'],
            'desktop' => ['desktop'],
            default => ['mobile', 'desktop'],
        };

        $force = (bool) ($data['force'] ?? false);
        $ran = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($targets as $target) {
            foreach ($strategies as $strategy) {
                if (! $force) {
                    $existing = $pageSpeed->latestFor($target['key'], $strategy);
                    if ($pageSpeed->isFresh($existing)) {
                        $skipped++;

                        continue;
                    }
                }

                $audit = $pageSpeed->auditAndStore($target, $strategy);
                $ran++;

                if ($audit->error_message) {
                    $errors++;
                }
            }
        }

        if ($ran === 0 && $skipped > 0) {
            return back()->with('success', 'Ölçüm atlandı: son '.config('kosar.pagespeed.cache_minutes').' dakika içinde güncel rapor var. Zorlamak için Ölç butonunu kullanın.');
        }

        $message = "PageSpeed ölçümü tamamlandı · {$ran} rapor";
        if ($skipped > 0) {
            $message .= " · {$skipped} güncel rapor atlandı";
        }
        if ($errors > 0) {
            $message .= " · {$errors} hata";
        }

        return back()->with('success', $message);
    }
}
