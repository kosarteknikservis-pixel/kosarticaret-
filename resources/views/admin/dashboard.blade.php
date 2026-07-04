@extends('layouts.admin')
@section('title', 'Özet')

@section('content')
    @php
        $stockAlerts = $lowStock->count() + $outOfStock->count();
        $signalCount = $pendingReviews + $unreadContactMessages;
        $growthPositive = $orderGrowth >= 0;
    @endphp

    <section class="admin-dashboard-hero">
        <div class="admin-dashboard-hero__copy">
            <p class="admin-dashboard-eyebrow">Kontrol merkezi</p>
            <h2>Hoş geldiniz, {{ auth()->user()?->name ?? 'Koşar Admin' }}</h2>
            <p>Satış, sipariş, stok ve müşteri aksiyonlarını tek ekrandan takip edin.</p>
            <div class="admin-dashboard-hero__meta">
                <span>{{ now()->timezone(config('kosar.report_timezone', 'Europe/Istanbul'))->translatedFormat('d F Y, l') }}</span>
                <span>{{ $ordersToday }} sipariş bugün</span>
                <span>{{ number_format($todayRevenue, 0, ',', '.') }} ₺ ciro</span>
            </div>
        </div>
        <div class="admin-dashboard-hero__actions">
            <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-primary">Siparişleri yönet</a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary">Mağazayı aç</a>
        </div>
    </section>

    <div class="admin-dashboard-stats">
        <a href="{{ route('admin.orders.index') }}" class="admin-metric-card admin-metric-card--primary">
            <div class="admin-metric-card__head">
                <span class="admin-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 15l3-4 3 2 4-6"/></svg>
                </span>
                <span class="admin-metric-card__trend {{ $growthPositive ? 'is-up' : 'is-down' }}">
                    {{ $growthPositive ? '+' : '' }}{{ $orderGrowth }}%
                </span>
            </div>
            <span class="admin-metric-card__label">Bu ay ciro</span>
            <strong>{{ number_format($monthlyRevenue, 2, ',', '.') }} ₺</strong>
            <small>{{ $ordersThisMonth }} sipariş · geçen aya göre</small>
        </a>

        <a href="{{ route('admin.orders.index') }}" class="admin-metric-card">
            <div class="admin-metric-card__head">
                <span class="admin-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg>
                </span>
            </div>
            <span class="admin-metric-card__label">Bugün</span>
            <strong>{{ number_format($todayRevenue, 2, ',', '.') }} ₺</strong>
            <small>{{ $ordersToday }} yeni sipariş</small>
        </a>

        <a href="{{ route('admin.products.index') }}" class="admin-metric-card">
            <div class="admin-metric-card__head">
                <span class="admin-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path d="M12 12v9"/><path d="m4 7.5 8 4.5 8-4.5"/></svg>
                </span>
                @if($stockAlerts > 0)
                    <span class="admin-metric-card__trend is-warn">{{ $stockAlerts }} uyarı</span>
                @endif
            </div>
            <span class="admin-metric-card__label">Katalog</span>
            <strong>{{ number_format($productCount, 0, ',', '.') }}</strong>
            <small>{{ $stockAlerts }} stok uyarısı</small>
        </a>

        <a href="{{ $pendingReviews > 0 ? route('admin.reviews.index') : route('admin.contact-messages.index') }}" class="admin-metric-card">
            <div class="admin-metric-card__head">
                <span class="admin-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </span>
                @if($signalCount > 0)
                    <span class="admin-metric-card__trend is-info">Bekliyor</span>
                @endif
            </div>
            <span class="admin-metric-card__label">Müşteri sinyali</span>
            <strong>{{ $signalCount }}</strong>
            <small>{{ $pendingReviews }} yorum · {{ $unreadContactMessages }} mesaj</small>
        </a>
    </div>

    <div class="admin-dashboard-grid">
        <section class="admin-card admin-dashboard-panel admin-dashboard-panel--wide">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Satış görünümü</p>
                    <h2>Satış grafiği</h2>
                </div>
                <div class="admin-chart-toolbar" role="tablist" aria-label="Grafik aralığı">
                    @foreach($salesCharts as $key => $chart)
                        <button type="button"
                                class="admin-chart-range {{ $key === 'month' ? 'is-active' : '' }}"
                                data-dashboard-chart-range="{{ $key }}">
                            {{ $chart['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="admin-chart-summary">
                <span class="admin-dashboard-pill" data-dashboard-chart-total>Toplam: {{ number_format($salesSeries->sum('revenue'), 2, ',', '.') }} ₺</span>
                <span class="admin-dashboard-pill" data-dashboard-chart-orders>{{ $salesSeries->sum('orders') }} sipariş</span>
                <span class="admin-dashboard-pill">Ort. sepet: {{ number_format($averageOrder, 2, ',', '.') }} ₺</span>
            </div>
            <div class="admin-sales-chart"
                 aria-label="Satış grafiği"
                 data-dashboard-chart
                 data-chart-series='@json($salesCharts, JSON_UNESCAPED_UNICODE)'>
                @foreach($salesSeries as $point)
                    <div class="admin-sales-chart__bar-wrap">
                        <span class="admin-sales-chart__tooltip">{{ number_format($point['revenue'], 2, ',', '.') }} ₺ · {{ $point['orders'] }} sipariş</span>
                        <span class="admin-sales-chart__bar" style="height: {{ max(8, round(($point['revenue'] / $maxRevenue) * 100)) }}%"></span>
                        <span class="admin-sales-chart__label">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Öncelik</p>
                    <h2>Aksiyon merkezi</h2>
                </div>
            </div>
            <div class="admin-action-list">
                @forelse($riskItems as $item)
                    <a href="{{ $item['route'] }}" class="admin-action-row admin-action-row--{{ $item['tone'] }}">
                        <span class="admin-action-row__dot" aria-hidden="true"></span>
                        <span class="admin-action-row__body">
                            <span class="admin-action-row__label">{{ $item['label'] }}</span>
                            <small>İncelemek için tıklayın</small>
                        </span>
                        <strong>{{ $item['count'] }}</strong>
                    </a>
                @empty
                    <div class="admin-action-empty">
                        <strong>Her şey temiz</strong>
                        <span>Şu anda acil aksiyon gerektiren kayıt görünmüyor.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="admin-dashboard-grid admin-dashboard-grid--secondary">
        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Operasyon</p>
                    <h2>Sipariş durumları</h2>
                </div>
            </div>
            <div class="admin-status-breakdown">
                @forelse($statusBreakdown as $status)
                    <div class="admin-status-row">
                        <div class="admin-status-row__meta">
                            <span>{{ $status['label'] }}</span>
                            <strong>{{ $status['total'] }}</strong>
                        </div>
                        <span class="admin-status-row__track">
                            <span style="width: {{ max(6, round(($status['total'] / $statusMax) * 100)) }}%"></span>
                        </span>
                    </div>
                @empty
                    <p class="admin-dashboard-empty">Henüz sipariş durumu yok.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Ürün performansı</p>
                    <h2>En çok satanlar</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($topProducts as $index => $item)
                    <div class="admin-top-product">
                        <span class="admin-top-product__rank" aria-hidden="true">{{ $index + 1 }}</span>
                        <div class="admin-top-product__body min-w-0">
                            <p class="truncate">{{ $item->product_name }}</p>
                            <span>{{ number_format((float) $item->revenue, 2, ',', '.') }} ₺</span>
                        </div>
                        <strong>{{ (int) $item->sold_qty }} adet</strong>
                    </div>
                @empty
                    <p class="admin-dashboard-empty">Satış verisi oluşunca burada ürünler listelenir.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Stok</p>
                    <h2>Kritik ürünler</h2>
                </div>
                <a href="{{ route('admin.products.index') }}" class="admin-link">Tümü</a>
            </div>
            <div class="admin-stock-list">
                @foreach($outOfStock->take(4) as $p)
                    <a href="{{ route('admin.products.edit', $p) }}" class="admin-stock-row admin-stock-row--danger">
                        <span class="truncate">{{ $p->name }}</span>
                        <strong>
                            <span class="admin-stock-badge admin-stock-badge--danger">Tükendi</span>
                            0
                        </strong>
                    </a>
                @endforeach
                @foreach($lowStock->take(4) as $p)
                    <a href="{{ route('admin.products.edit', $p) }}" class="admin-stock-row admin-stock-row--warn">
                        <span class="truncate">{{ $p->name }}</span>
                        <strong>
                            <span class="admin-stock-badge admin-stock-badge--warn">Az</span>
                            {{ $p->stock }}
                        </strong>
                    </a>
                @endforeach
                @if($outOfStock->isEmpty() && $lowStock->isEmpty())
                    <div class="admin-action-empty">
                        <strong>Stok uyarısı yok</strong>
                        <span>Kritik stok seviyesinde ürün bulunmuyor.</span>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <section class="admin-card admin-dashboard-panel admin-dashboard-recent">
        <div class="admin-panel-head">
            <div>
                <p class="admin-dashboard-eyebrow">Son hareketler</p>
                <h2>Son siparişler</h2>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="admin-link">Tüm siparişler</a>
        </div>
        <div class="admin-recent-orders">
            @forelse($recentOrders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="admin-recent-order">
                    <div class="admin-recent-order__main min-w-0">
                        <strong class="truncate">{{ $order->order_number }}</strong>
                        <span class="truncate">{{ $order->customer_name ?: $order->email }}</span>
                    </div>
                    <div class="admin-recent-order__meta">
                        <span class="admin-recent-order__status">{{ \App\Support\OrderStatus::label($order->status) }}</span>
                        <strong>{{ number_format((float) $order->total, 2, ',', '.') }} ₺</strong>
                        <time datetime="{{ $order->created_at?->toIso8601String() }}">
                            {{ $order->created_at?->timezone(config('kosar.report_timezone', 'Europe/Istanbul'))->format('d.m.Y H:i') }}
                        </time>
                    </div>
                </a>
            @empty
                <p class="admin-dashboard-empty">Henüz sipariş yok.</p>
            @endforelse
        </div>
    </section>

    <section class="admin-quick-actions" aria-label="Hızlı işlemler">
        <a href="{{ route('admin.products.create') }}" class="admin-quick-action">
            <span class="admin-quick-action__index" aria-hidden="true">01</span>
            <strong>Yeni ürün</strong>
            <span>Kataloğa ürün ekle</span>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="admin-quick-action">
            <span class="admin-quick-action__index" aria-hidden="true">02</span>
            <strong>Sipariş akışı</strong>
            <span>Filtrele, takip no gir, Paraşüt’e aktar</span>
        </a>
        <a href="{{ route('admin.home-banners.builder') }}" class="admin-quick-action">
            <span class="admin-quick-action__index" aria-hidden="true">03</span>
            <strong>Ana sayfa düzenleyici</strong>
            <span>Vitrin bloklarını yönet</span>
        </a>
        <a href="{{ route('admin.settings.edit') }}" class="admin-quick-action">
            <span class="admin-quick-action__index" aria-hidden="true">04</span>
            <strong>Site ayarları</strong>
            <span>İletişim, promo ve entegrasyonlar</span>
        </a>
    </section>
@endsection
