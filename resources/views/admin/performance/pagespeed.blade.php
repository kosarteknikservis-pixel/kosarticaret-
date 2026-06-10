@extends('layouts.admin')

@section('title', 'Site Hızı')

@section('content')
    @php
        $scoreClass = fn (?int $score) => match (true) {
            $score === null => 'is-neutral',
            $score >= 90 => 'is-good',
            $score >= 50 => 'is-average',
            default => 'is-poor',
        };
        $fieldLabel = fn (?string $category) => match ($category) {
            'FAST' => 'Hızlı',
            'AVERAGE' => 'Orta',
            'SLOW' => 'Yavaş',
            default => 'Veri yok',
        };
        $fieldClass = fn (?string $category) => match ($category) {
            'FAST' => 'is-good',
            'AVERAGE' => 'is-average',
            'SLOW' => 'is-poor',
            default => 'is-neutral',
        };
        $formatMs = fn (?int $ms) => $ms === null ? '—' : number_format($ms / 1000, 2, ',', '.').' sn';
        $formatCls = fn (?float $cls) => $cls === null ? '—' : number_format($cls, 3, ',', '.');
    @endphp

    <div class="admin-image-optimizer admin-pagespeed">
        <section class="admin-image-optimizer__hero">
            <div class="admin-image-optimizer__hero-copy">
                <p class="admin-dashboard-eyebrow">Google PageSpeed Insights</p>
                <h2>Site hızı kontrolü</h2>
                <p>
                    Ana sayfa, kategori, ürün ve blog için Google’ın resmi Lighthouse ölçümünü panelden takip edin.
                    Lab verisi (simülasyon) ile gerçek Chrome kullanıcı verisi (CrUX) birlikte gösterilir.
                </p>
            </div>
            <div class="admin-pagespeed__hero-actions">
                <a href="{{ $reportUrl }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary">PageSpeed.web.dev</a>
                @if($configured)
                    <form method="post" action="{{ route('admin.performance.pagespeed.run') }}">
                        @csrf
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="admin-btn admin-btn-primary">Tüm sayfaları ölç</button>
                    </form>
                @endif
            </div>
        </section>

        @unless($configured)
            <section class="admin-image-optimizer__action-card admin-pagespeed__setup">
                <div>
                    <p class="admin-dashboard-eyebrow">Kurulum gerekli</p>
                    <h2>Google API anahtarı ekleyin</h2>
                    <p>
                        Doğru ve resmi veri için Google Cloud Console’da <strong>PageSpeed Insights API</strong> anahtarı oluşturup panele kaydedin.
                        Ücretsiz kotada günde binlerce ölçüm yapılabilir; sonuçlar pagespeed.web.dev ile aynı kaynaktan gelir.
                    </p>
                    <ol class="admin-pagespeed__setup-steps">
                        <li><a href="https://console.cloud.google.com/apis/library/pagespeedonline.googleapis.com" target="_blank" rel="noopener">PageSpeed Insights API</a>’yi etkinleştirin</li>
                        <li>API anahtarı oluşturun</li>
                        <li><a href="{{ route('admin.settings.edit', ['tab' => 'integrations']) }}" class="text-teal-700 font-semibold">Site ayarları → Entegrasyonlar</a> → <strong>Google PageSpeed</strong> alanına yapıştırıp kaydedin</li>
                    </ol>
                    <a href="{{ route('admin.settings.edit', ['tab' => 'integrations']) }}" class="admin-btn admin-btn-primary mt-4">Entegrasyonlara git</a>
                </div>
            </section>
        @endunless

        <div class="admin-image-optimizer__stats">
            <div class="admin-image-optimizer__stat">
                <span class="admin-image-optimizer__stat-icon">LAB</span>
                <span class="admin-image-optimizer__stat-label">Lab verisi</span>
                <strong>Lighthouse</strong>
                <small>Google’ın simülasyon motoru; geliştirme sonrası karşılaştırma için ideal</small>
            </div>
            <div class="admin-image-optimizer__stat">
                <span class="admin-image-optimizer__stat-icon">CrUX</span>
                <span class="admin-image-optimizer__stat-label">Gerçek kullanıcı</span>
                <strong>28 gün</strong>
                <small>Chrome ziyaretçilerinin alan verisi; yeterli trafikte LCP / CLS / INP p75</small>
            </div>
            <div class="admin-image-optimizer__stat">
                <span class="admin-image-optimizer__stat-icon">↻</span>
                <span class="admin-image-optimizer__stat-label">Önbellek</span>
                <strong>{{ $cacheMinutes }} dk</strong>
                <small>Aynı sayfa bu süre içinde tekrar ölçülmez (zorla ölç hariç)</small>
            </div>
        </div>

        @if($configured)
            <section class="admin-card admin-pagespeed__table-wrap">
                <div class="admin-panel-head">
                    <div>
                        <p class="admin-dashboard-eyebrow">Sayfa bazlı rapor</p>
                        <h2>Son ölçümler</h2>
                    </div>
                </div>

                <div class="admin-pagespeed__table-scroll">
                    <table class="admin-pagespeed__table">
                        <thead>
                            <tr>
                                <th>Sayfa</th>
                                <th>Cihaz</th>
                                <th>Performans</th>
                                <th>LCP</th>
                                <th>CLS</th>
                                <th>TBT</th>
                                <th>Gerçek kullanıcı</th>
                                <th>Ölçüm</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($targets as $target)
                                @foreach(['mobile' => 'Mobil', 'desktop' => 'Masaüstü'] as $strategy => $strategyLabel)
                                    @php $audit = $latest[$target['key']][$strategy] ?? null; @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $target['label'] }}</strong>
                                            <small class="admin-pagespeed__url">{{ $target['url'] }}</small>
                                        </td>
                                        <td>{{ $strategyLabel }}</td>
                                        <td>
                                            @if($audit?->error_message)
                                                <span class="admin-pagespeed__pill is-poor">Hata</span>
                                            @else
                                                <span class="admin-pagespeed__score {{ $scoreClass($audit?->performance_score) }}">
                                                    {{ $audit?->performance_score ?? '—' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $formatMs($audit?->lcp_ms) }}</td>
                                        <td>{{ $formatCls($audit?->cls) }}</td>
                                        <td>{{ $formatMs($audit?->tbt_ms) }}</td>
                                        <td>
                                            <span class="admin-pagespeed__pill {{ $fieldClass($audit?->field_overall_category) }}">
                                                {{ $fieldLabel($audit?->field_overall_category) }}
                                            </span>
                                            @if($audit?->field_lcp_p75_ms)
                                                <small class="admin-pagespeed__metric-note">LCP p75 {{ $formatMs($audit->field_lcp_p75_ms) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($audit?->measured_at)
                                                <time datetime="{{ $audit->measured_at->toIso8601String() }}">{{ $audit->measured_at->timezone(config('kosar.report_timezone', 'Europe/Istanbul'))->format('d.m.Y H:i') }}</time>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <form method="post" action="{{ route('admin.performance.pagespeed.run') }}">
                                                @csrf
                                                <input type="hidden" name="page_key" value="{{ $target['key'] }}">
                                                <input type="hidden" name="strategy" value="{{ $strategy }}">
                                                <input type="hidden" name="force" value="1">
                                                <button type="submit" class="admin-btn admin-btn-secondary admin-btn--compact">Ölç</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @if($audit?->error_message)
                                        <tr class="admin-pagespeed__error-row">
                                            <td colspan="9">{{ $audit->error_message }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @php
                $topOpportunities = collect($latest)
                    ->flatten(1)
                    ->filter(fn ($audit) => $audit && ! $audit->error_message && ! empty($audit->opportunities))
                    ->flatMap(fn ($audit) => collect($audit->opportunities)->map(fn ($item) => array_merge($item, [
                        'page' => $audit->label,
                        'strategy' => $audit->isMobile() ? 'Mobil' : 'Masaüstü',
                    ])))
                    ->sortByDesc('savings_ms')
                    ->take(8);
            @endphp

            @if($topOpportunities->isNotEmpty())
                <section class="admin-card admin-pagespeed__opportunities">
                    <div class="admin-panel-head">
                        <div>
                            <p class="admin-dashboard-eyebrow">İyileştirme fırsatları</p>
                            <h2>Google önerileri</h2>
                        </div>
                    </div>
                    <div class="admin-action-list">
                        @foreach($topOpportunities as $item)
                            <div class="admin-action-row">
                                <span class="min-w-0">
                                    <span class="block font-semibold text-slate-900">{{ $item['title'] }}</span>
                                    <small>{{ $item['page'] }} · {{ $item['strategy'] }}</small>
                                </span>
                                <strong>
                                    @if($item['savings_ms'])
                                        ~{{ number_format($item['savings_ms'] / 1000, 1, ',', '.') }} sn
                                    @elseif($item['savings_bytes'])
                                        ~{{ number_format($item['savings_bytes'] / 1024, 0, ',', '.') }} KB
                                    @else
                                        —
                                    @endif
                                </strong>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($history->isNotEmpty())
                <section class="admin-card admin-pagespeed__history">
                    <div class="admin-panel-head">
                        <div>
                            <p class="admin-dashboard-eyebrow">Geçmiş</p>
                            <h2>Son ölçüm kayıtları</h2>
                        </div>
                    </div>
                    <div class="admin-action-list">
                        @foreach($history as $audit)
                            <div class="admin-action-row">
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $audit->label }} · {{ $audit->isMobile() ? 'Mobil' : 'Masaüstü' }}</span>
                                    <small>{{ $audit->measured_at?->timezone(config('kosar.report_timezone', 'Europe/Istanbul'))->format('d.m.Y H:i') }}</small>
                                </span>
                                <strong class="admin-pagespeed__score {{ $scoreClass($audit->performance_score) }}">{{ $audit->performance_score ?? '—' }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

        <section class="admin-image-optimizer__info">
            <p class="admin-dashboard-eyebrow">Veri kaynağı</p>
            <h2>Lab ve gerçek kullanıcı farkı</h2>
            <p>
                <strong>Lab (Lighthouse):</strong> Google’ın kontrollü simülasyonu; kod veya tema değişikliği sonrası etkiyi görmek için güvenilir kaynaktır.
                <strong>Gerçek kullanıcı (CrUX):</strong> Son 28 günde sitenizi ziyaret eden gerçek Chrome kullanıcılarının p75 değerleridir; Search Console ve Google sıralama sinyalleriyle uyumludur.
                Trafik düşükse CrUX alanı “Veri yok” görünebilir — bu normaldir.
            </p>
        </section>
    </div>
@endsection
