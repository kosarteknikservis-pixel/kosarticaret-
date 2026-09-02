@php
    $snapshot = $gscKeywords['snapshot'] ?? [];
    $livePeriods = $gscKeywords['live'] ?? [];
    $tracked = $gscKeywords['tracked'] ?? [];
    $gscPeriod = (int) ($gscKeywords['gsc_period'] ?? 28);
    $activeLive = $gscActiveLive ?? ($livePeriods[(string) $gscPeriod] ?? null);
    $gscUrl = $gscKeywords['gsc_url'] ?? '#';

    $statusLabels = [
        'top_5' => 'İlk 5',
        'top_10' => 'İlk 10',
        'on_target' => 'Hedefte',
        'page_2' => '2. sayfa',
        'needs_work' => 'Geliştir',
        'no_data' => 'Veri yok',
    ];
@endphp

<section id="google-arama-kelimeleri" class="admin-card admin-dashboard-panel admin-analytics-gsc mt-6">
    <div class="admin-panel-head admin-analytics-gsc__head">
        <div>
            <p class="admin-dashboard-eyebrow">Google organik</p>
            <h2>Hangi kelimelerden trafik alıyoruz?</h2>
            <p class="admin-analytics-gsc__intro">
                Arama kelimeleri Google Search Console verisinden gelir. Mağaza ziyaretçi sayımı ile birebir aynı olmayabilir;
                GSC yalnızca Google arama sonuçlarındaki tıklamaları sayar.
            </p>
        </div>
        <div class="admin-analytics-gsc__actions">
            <a href="{{ $gscUrl }}" target="_blank" rel="noopener noreferrer" class="admin-btn admin-btn-secondary">
                Search Console’da aç
            </a>
        </div>
    </div>

    <div class="admin-analytics-gsc__grid">
        {{-- A: Aylık / snapshot özet --}}
        <div class="admin-analytics-gsc__panel">
            <div class="admin-analytics-gsc__panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">A · Aylık özet</p>
                    <h3>GSC performans dosyası</h3>
                </div>
                @if(($snapshot['available'] ?? false) && ! empty($snapshot['imported_at']))
                    <span class="admin-analytics-gsc__meta">{{ \Illuminate\Support\Carbon::parse($snapshot['imported_at'])->format('d.m.Y H:i') }}</span>
                @endif
            </div>

            @if($snapshot['available'] ?? false)
                <div class="admin-analytics-gsc__totals">
                    <div><span>Tıklama</span><strong>{{ number_format((int) ($snapshot['totals']['clicks'] ?? 0)) }}</strong></div>
                    <div><span>Gösterim</span><strong>{{ number_format((int) ($snapshot['totals']['impressions'] ?? 0)) }}</strong></div>
                    <div><span>Sorgu</span><strong>{{ number_format((int) ($snapshot['totals']['query_count'] ?? 0)) }}</strong></div>
                </div>
                @if(! empty($snapshot['period_label']))
                    <p class="admin-analytics-gsc__period">{{ $snapshot['period_label'] }} · {{ $snapshot['source_label'] }}</p>
                @endif
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kelime</th>
                                <th>Tıklama</th>
                                <th>Gösterim</th>
                                <th>CTR</th>
                                <th>Sıra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($snapshot['queries'] ?? [] as $row)
                                <tr>
                                    <td class="font-medium text-slate-900 max-w-xs truncate">{{ $row['query'] }}</td>
                                    <td>{{ number_format((int) $row['clicks']) }}</td>
                                    <td>{{ number_format((int) $row['impressions']) }}</td>
                                    <td>{{ number_format((float) $row['ctr'] * 100, 1, ',', '.') }}%</td>
                                    <td>{{ number_format((float) $row['position'], 1, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="admin-analytics-gsc__empty">{{ $snapshot['message'] ?? 'Aylık GSC özeti henüz yok.' }}</p>
            @endif
        </div>

        {{-- B: Canlı dönem (7/28/90) --}}
        <div class="admin-analytics-gsc__panel">
            <div class="admin-analytics-gsc__panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">B · Günlük cache</p>
                    <h3>Canlı GSC dönemleri</h3>
                </div>
                <div class="admin-chart-toolbar">
                    @foreach([7 => '7 gün', 28 => '28 gün', 90 => '90 gün'] as $days => $label)
                        <a href="{{ route('admin.analytics.index', array_filter(['period' => $period, 'gsc_period' => $days])) }}"
                           class="admin-chart-range {{ $gscPeriod === $days ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            @if($activeLive['available'] ?? false)
                <div class="admin-analytics-gsc__totals">
                    <div><span>Tıklama</span><strong>{{ number_format((int) ($activeLive['totals']['clicks'] ?? 0)) }}</strong></div>
                    <div><span>Gösterim</span><strong>{{ number_format((int) ($activeLive['totals']['impressions'] ?? 0)) }}</strong></div>
                    <div><span>Sorgu</span><strong>{{ number_format((int) ($activeLive['totals']['query_count'] ?? 0)) }}</strong></div>
                </div>
                @if(! empty($activeLive['period_label']))
                    <p class="admin-analytics-gsc__period">{{ $activeLive['period_label'] }} · {{ $activeLive['source_label'] }}</p>
                @endif
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kelime</th>
                                <th>Tıklama</th>
                                <th>Gösterim</th>
                                <th>CTR</th>
                                <th>Sıra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeLive['queries'] ?? [] as $row)
                                <tr>
                                    <td class="font-medium text-slate-900 max-w-xs truncate">{{ $row['query'] }}</td>
                                    <td>{{ number_format((int) $row['clicks']) }}</td>
                                    <td>{{ number_format((int) $row['impressions']) }}</td>
                                    <td>{{ number_format((float) $row['ctr'] * 100, 1, ',', '.') }}%</td>
                                    <td>{{ number_format((float) $row['position'], 1, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="admin-analytics-gsc__empty">{{ $activeLive['message'] ?? 'Canlı GSC verisi henüz çekilmedi.' }}</p>
                <p class="admin-analytics-gsc__hint">Sunucuda günlük cron veya <code class="text-xs">php artisan seo:fetch-gsc-keywords</code> çalıştırın.</p>
            @endif
        </div>
    </div>

    {{-- C: Ticari kelime takibi --}}
    <div class="admin-analytics-gsc__panel admin-analytics-gsc__panel--tracked mt-5">
        <div class="admin-analytics-gsc__panel-head">
            <div>
                <p class="admin-dashboard-eyebrow">C · Ticari kelimeler</p>
                <h3>Öncelikli kelime sıralaması</h3>
                <p class="admin-analytics-gsc__intro admin-analytics-gsc__intro--compact">
                    Hidrofor, dalgıç pompa gibi hedef kelimeler — seçili GSC dönemine göre ({{ $gscPeriod }} gün).
                    @if(! empty($tracked['source_label']))
                        Kaynak: {{ $tracked['source_label'] }}.
                    @endif
                </p>
            </div>
            @if(! empty($tracked['tracked_at']))
                <span class="admin-analytics-gsc__meta">{{ \Illuminate\Support\Carbon::parse($tracked['tracked_at'])->format('d.m.Y H:i') }}</span>
            @endif
        </div>

        @if($tracked['available'] ?? false)
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Hedef kelime</th>
                            <th>Eşleşen sorgu</th>
                            <th>Sıra</th>
                            <th>Hedef</th>
                            <th>Tıklama</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tracked['keywords'] ?? [] as $row)
                            @php($status = (string) ($row['status'] ?? 'no_data'))
                            <tr>
                                <td class="font-medium text-slate-900">{{ $row['keyword'] }}</td>
                                <td class="max-w-xs truncate text-slate-600">{{ $row['matched_query'] ?? '—' }}</td>
                                <td>{{ isset($row['position']) ? number_format((float) $row['position'], 1, ',', '.') : '—' }}</td>
                                <td>≤ {{ (int) ($row['target_position'] ?? 0) }}</td>
                                <td>{{ number_format((int) ($row['clicks'] ?? 0)) }}</td>
                                <td><span class="admin-analytics-gsc__status admin-analytics-gsc__status--{{ $status }}">{{ $statusLabels[$status] ?? $status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="admin-analytics-gsc__empty">{{ $tracked['message'] ?? 'Ticari kelime takibi için GSC verisi bekleniyor.' }}</p>
        @endif
    </div>
</section>
