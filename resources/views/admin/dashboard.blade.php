@extends('layouts.admin')
@section('title', 'Özet')

@section('content')
    <section class="admin-dashboard-hero">
        <div>
            <p class="admin-dashboard-eyebrow">Bugünün kontrol merkezi</p>
            <h2>Hoş geldiniz, {{ auth()->user()?->name ?? 'Koşar Admin' }}</h2>
            <p>Satış, sipariş, stok ve müşteri aksiyonlarını tek ekrandan takip edin.</p>
        </div>
        <div class="admin-dashboard-hero__actions">
            <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-primary">Siparişleri yönet</a>
            <a href="{{ route('home') }}" target="_blank" class="admin-btn admin-btn-secondary">Mağazayı aç</a>
        </div>
    </section>

    <div class="admin-dashboard-stats">
        <a href="{{ route('admin.orders.index') }}" class="admin-metric-card admin-metric-card--primary">
            <span class="admin-metric-card__icon">₺</span>
            <span class="admin-metric-card__label">Bu ay ciro</span>
            <strong>{{ number_format($monthlyRevenue, 2, ',', '.') }} ₺</strong>
            <small>{{ $ordersThisMonth }} sipariş · geçen aya göre {{ $orderGrowth >= 0 ? '+' : '' }}{{ $orderGrowth }}%</small>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="admin-metric-card">
            <span class="admin-metric-card__icon">↗</span>
            <span class="admin-metric-card__label">Bugün</span>
            <strong>{{ number_format($todayRevenue, 2, ',', '.') }} ₺</strong>
            <small>{{ $ordersToday }} yeni sipariş</small>
        </a>
        <a href="{{ route('admin.products.index') }}" class="admin-metric-card">
            <span class="admin-metric-card__icon">□</span>
            <span class="admin-metric-card__label">Katalog</span>
            <strong>{{ $productCount }}</strong>
            <small>{{ $lowStock->count() + $outOfStock->count() }} stok uyarısı</small>
        </a>
        <a href="{{ route('admin.reviews.index') }}" class="admin-metric-card">
            <span class="admin-metric-card__icon">★</span>
            <span class="admin-metric-card__label">Müşteri sinyali</span>
            <strong>{{ $pendingReviews + $unreadContactMessages }}</strong>
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
                <div class="admin-chart-toolbar">
                    @foreach($salesCharts as $key => $chart)
                        <button type="button" class="admin-chart-range {{ $key === 'month' ? 'is-active' : '' }}" data-dashboard-chart-range="{{ $key }}">
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
                        <span>{{ $item['label'] }}</span>
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
                        <div class="flex items-center justify-between gap-3">
                            <span>{{ $status['label'] }}</span>
                            <strong>{{ $status['total'] }}</strong>
                        </div>
                        <span class="admin-status-row__track">
                            <span style="width: {{ max(6, round(($status['total'] / $statusMax) * 100)) }}%"></span>
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Henüz sipariş durumu yok.</p>
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
                @forelse($topProducts as $item)
                    <div class="admin-top-product">
                        <div class="min-w-0">
                            <p class="truncate">{{ $item->product_name }}</p>
                            <span>{{ number_format((float) $item->revenue, 2, ',', '.') }} ₺</span>
                        </div>
                        <strong>{{ (int) $item->sold_qty }} adet</strong>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Satış verisi oluşunca burada ürünler listelenir.</p>
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
                        <strong>0</strong>
                    </a>
                @endforeach
                @foreach($lowStock->take(4) as $p)
                    <a href="{{ route('admin.products.edit', $p) }}" class="admin-stock-row admin-stock-row--warn">
                        <span class="truncate">{{ $p->name }}</span>
                        <strong>{{ $p->stock }}</strong>
                    </a>
                @endforeach
                @if($outOfStock->isEmpty() && $lowStock->isEmpty())
                    <p class="text-sm text-slate-500">Stok uyarısı yok. Harika!</p>
                @endif
            </div>
        </section>
    </div>

    <section class="admin-quick-actions">
        <a href="{{ route('admin.products.create') }}" class="admin-quick-action">
            <strong>+ Yeni ürün</strong>
            <span>Kataloğa ürün ekle</span>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="admin-quick-action">
            <strong>Sipariş akışı</strong>
            <span>Filtrele, takip no gir, Paraşüt’e aktar</span>
        </a>
        <a href="{{ route('admin.home-banners.builder') }}" class="admin-quick-action">
            <strong>Ana sayfa düzenleyici</strong>
            <span>Vitrin bloklarını yönet</span>
        </a>
        <a href="{{ route('admin.settings.edit') }}" class="admin-quick-action">
            <strong>Site ayarları</strong>
            <span>İletişim, promo ve entegrasyonlar</span>
        </a>
    </section>
@endsection
