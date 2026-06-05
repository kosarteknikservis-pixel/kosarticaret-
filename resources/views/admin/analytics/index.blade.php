@extends('layouts.admin')
@section('title', 'Müşteri Hareketleri')

@section('content')
    <x-admin.page-header title="Müşteri hareketleri" subtitle="Ziyaret, ürün ilgisi, sepet davranışı ve sipariş kaynaklarını gerçek site verisiyle takip edin.">
        <x-slot:actions>
            <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-secondary">Siparişlere git</a>
            <a href="{{ route('home') }}" target="_blank" class="admin-btn admin-btn-primary">Mağazayı aç</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-analytics-page">
    <div class="admin-card admin-analytics-periods mb-5">
        <div>
            <p class="admin-dashboard-eyebrow">Rapor dönemi</p>
            <strong>{{ $periodLabel }} müşteri hareketleri</strong>
        </div>
        <div class="admin-chart-toolbar">
            @foreach($periods as $key => $periodOption)
                <a href="{{ route('admin.analytics.index', ['period' => $key]) }}" class="admin-chart-range {{ $period === $key ? 'is-active' : '' }}">
                    {{ $periodOption['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="admin-dashboard-stats">
        <div class="admin-metric-card admin-metric-card--primary admin-analytics-metric">
            <span class="admin-metric-card__icon">●</span>
            <span class="admin-metric-card__label">Anlık ziyaretçi</span>
            <strong>{{ $activeVisitors }}</strong>
            <small>Son 2 dakikadaki gerçek tarayıcı sinyali</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__icon">↗</span>
            <span class="admin-metric-card__label">{{ $periodLabel }} trafik</span>
            <strong>{{ $periodVisitors }}</strong>
            <small>{{ $periodPageViews }} sayfa · {{ $periodProductViews }} ürün görüntüleme</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__icon">□</span>
            <span class="admin-metric-card__label">Sepet sinyali</span>
            <strong>{{ $periodCartAdds }}</strong>
            <small>{{ $periodLabel }} sepete ekleme</small>
        </div>
        <a href="#yarim-kalan-sepetler" class="admin-metric-card admin-analytics-metric" aria-label="Yarım kalan sepetleri görüntüle">
            <span class="admin-metric-card__icon">!</span>
            <span class="admin-metric-card__label">Yarım kalanlar</span>
            <strong>{{ $abandonedCartCount }}</strong>
            <small>{{ $periodLabel }} aktif veya checkout aşamasında kalan sepet · Listeye git</small>
        </a>
    </div>

    <div class="admin-dashboard-grid mt-6">
        <section class="admin-card admin-dashboard-panel admin-dashboard-panel--wide">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Dönüşüm hunisi</p>
                    <h2>{{ $periodLabel }} müşteri akışı</h2>
                </div>
            </div>
            <div class="admin-analytics-funnel">
                <div>
                    <span>Aktif ziyaretçi</span>
                    <strong>{{ $periodVisitors }}</strong>
                    <small>{{ $periodPageViews }} sayfa görüntüleme</small>
                </div>
                <div>
                    <span>Checkout başlangıcı</span>
                    <strong>{{ $checkoutStarts }}</strong>
                    <small>Sepetten ödeme adımına geçenler</small>
                </div>
                <div>
                    <span>Sipariş</span>
                    <strong>{{ $ordersThisPeriod }}</strong>
                    <small>{{ $periodLabel }} oluşan sipariş</small>
                </div>
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Kaynak</p>
                    <h2>Sipariş nereden geldi?</h2>
                </div>
            </div>
            <div class="admin-action-list">
                @forelse($sourcePerformance as $source)
                    <div class="admin-action-row">
                        <span class="min-w-0">
                            <span class="block truncate">{{ $source['source'] }}</span>
                            <small>{{ $periodLabel }} · {{ number_format($source['revenue'], 2, ',', '.') }} ₺ · dönüşüm {{ $source['conversion'] ?? '—' }}%</small>
                        </span>
                        <strong>{{ $source['orders'] }} sipariş</strong>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sipariş kaynak verisi sipariş geldikçe oluşacak.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="admin-card admin-dashboard-panel admin-analytics-live mt-6">
        <div class="admin-panel-head">
            <div>
                <p class="admin-dashboard-eyebrow">Anlık trafik</p>
                <h2>Şu an hangi sayfalardalar?</h2>
            </div>
        </div>
        <div class="admin-top-products">
            @forelse($activeVisitorList as $visitor)
                <a href="{{ route('admin.analytics.visitor', $visitor) }}" class="admin-analytics-row">
                    <div class="admin-analytics-row__body">
                        <p>{{ $visitor->last_url }}</p>
                        <span>{{ $visitor->device_type ?: 'cihaz bilinmiyor' }} · {{ $visitor->utm_source ?: 'direct' }}</span>
                    </div>
                    <strong>{{ $visitor->last_seen_at?->diffForHumans(null, true) }}</strong>
                </a>
            @empty
                <p class="text-sm text-slate-500">Son 2 dakikada gerçek tarayıcı sinyali görünmüyor.</p>
            @endforelse
        </div>
    </section>

    <div class="admin-analytics-insights mt-6">
        <section class="admin-card admin-dashboard-panel admin-analytics-card">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Ürün ilgisi</p>
                    <h2>En çok bakılanlar</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($topViewedProducts as $product)
                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="admin-analytics-row">
                        <div class="admin-analytics-row__body">
                            <p>{{ $product->name }}</p>
                            <span>{{ $periodLabel }} ürün görüntüleme</span>
                        </div>
                        <strong>{{ (int) $product->views }}</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Ürün görüntüleme verisi oluşunca burada listelenir.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel admin-analytics-card">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Dönüşüm</p>
                    <h2>Ürün görüntüleme → sepet</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($productConversions as $product)
                    <a href="{{ route('products.show', $product['slug']) }}" target="_blank" class="admin-analytics-row">
                        <div class="admin-analytics-row__body">
                            <p>{{ $product['name'] }}</p>
                            <span>{{ $product['views'] }} görüntüleme · {{ $product['cart_adds'] }} sepete ekleme</span>
                        </div>
                        <strong>%{{ $product['rate'] }}</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Dönüşüm verisi oluşunca burada listelenir.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel admin-analytics-card">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Sepet ilgisi</p>
                    <h2>Sepete en çok eklenenler</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($topCartProducts as $product)
                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="admin-analytics-row">
                        <div class="admin-analytics-row__body">
                            <p>{{ $product->name }}</p>
                            <span>{{ $periodLabel }} sepete ekleme</span>
                        </div>
                        <strong>{{ (int) $product->cart_adds }}</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Sepet verisi oluşunca burada listelenir.</p>
                @endforelse
            </div>
        </section>

        <section id="yarim-kalan-sepetler" class="admin-card admin-dashboard-panel admin-analytics-card">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Yarım sepet</p>
                    <h2>Son kalan sepetler</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($abandonedCarts->take(6) as $cart)
                    @php($customerInfo = collect([$cart->customer_name, $cart->email, $cart->phone])->filter()->implode(' · ') ?: 'Müşteri bilgisi yok')
                    <div class="admin-analytics-row admin-analytics-row--actions">
                        <div class="admin-analytics-row__body">
                            <p>{{ collect($cart->items ?? [])->pluck('name')->take(2)->implode(', ') ?: 'Sepet' }}</p>
                            <span>{{ $customerInfo }} · {{ $cart->last_activity_at?->diffForHumans() }}</span>
                        </div>
                        <div class="admin-analytics-row__actions">
                            <strong>{{ number_format((float) $cart->subtotal, 2, ',', '.') }} ₺</strong>
                            @if($cart->visitor)
                                <a href="{{ route('admin.analytics.visitor', $cart->visitor) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Detay</a>
                            @endif
                            <form method="post" action="{{ route('admin.analytics.abandoned-carts.destroy', $cart) }}" onsubmit="return confirm('Bu yarım kalan sepet kaydı silinsin mi? Bu işlem geri alınamaz.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger text-xs py-1.5">Sil</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Yarım kalan sepet görünmüyor.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="admin-card admin-analytics-events mt-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
            <p class="admin-dashboard-eyebrow">{{ $periodLabel }} — müşteri akışı</p>
            <h2 class="font-bold text-slate-900 mt-1">Son müşteri özetleri</h2>
            <p class="mt-1 text-xs text-slate-500">Seçilen döneme ait hareketler, ziyaretçi bazlı özetlenerek gösterilir. Detay için tarih bağlantısına tıklayın.</p>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--stack">
                <thead>
                    <tr>
                        <th>Son hareket</th>
                        <th>Özet</th>
                        <th>Son sayfa</th>
                        <th>Detay</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentVisitorSummaries as $summary)
                        <tr>
                            <td data-label="Son hareket"><span class="admin-analytics-event-pill">{{ $summary['last_label'] }}</span></td>
                            <td data-label="Özet">
                                <div class="admin-analytics-flow-summary">
                                    <strong>{{ $summary['summary'] ?: 'Tek hareket' }}</strong>
                                    <span>{{ $summary['latest']->visitor?->device_type ?: 'cihaz bilinmiyor' }} · {{ $summary['latest']->visitor?->utm_source ?: 'direct' }}</span>
                                </div>
                            </td>
                            <td data-label="Son sayfa" class="admin-analytics-url">{{ $summary['latest']->visitor?->last_url ?: $summary['latest']->url }}</td>
                            <td data-label="Detay">
                                @if($summary['visitor'])
                                    <a href="{{ route('admin.analytics.visitor', $summary['visitor']) }}" class="link">{{ $summary['latest']->occurred_at?->format('d.m.Y H:i') }}</a>
                                @else
                                    {{ $summary['latest']->occurred_at?->format('d.m.Y H:i') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-slate-500 py-8">Henüz müşteri hareketi kaydedilmedi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    </div>
@endsection
