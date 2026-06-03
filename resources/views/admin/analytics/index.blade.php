@extends('layouts.admin')
@section('title', 'Müşteri Hareketleri')

@section('content')
    <x-admin.page-header title="Müşteri hareketleri" subtitle="Ziyaret, ürün ilgisi, sepet davranışı ve sipariş kaynaklarını gerçek site verisiyle takip edin.">
        <x-slot:actions>
            <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-secondary">Siparişlere git</a>
            <a href="{{ route('home') }}" target="_blank" class="admin-btn admin-btn-primary">Mağazayı aç</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-dashboard-stats">
        <div class="admin-metric-card admin-metric-card--primary">
            <span class="admin-metric-card__icon">●</span>
            <span class="admin-metric-card__label">Anlık ziyaretçi</span>
            <strong>{{ $activeVisitors }}</strong>
            <small>Son 5 dakika içinde aktif</small>
        </div>
        <div class="admin-metric-card">
            <span class="admin-metric-card__icon">↗</span>
            <span class="admin-metric-card__label">Bugün trafik</span>
            <strong>{{ $todayVisitors }}</strong>
            <small>{{ $todayPageViews }} sayfa · {{ $todayProductViews }} ürün görüntüleme</small>
        </div>
        <div class="admin-metric-card">
            <span class="admin-metric-card__icon">□</span>
            <span class="admin-metric-card__label">Sepet sinyali</span>
            <strong>{{ $todayCartAdds }}</strong>
            <small>Bugünkü sepete ekleme</small>
        </div>
        <div class="admin-metric-card">
            <span class="admin-metric-card__icon">!</span>
            <span class="admin-metric-card__label">Yarım kalanlar</span>
            <strong>{{ $abandonedCartCount }}</strong>
            <small>Aktif veya checkout aşamasında kalan sepet</small>
        </div>
    </div>

    <div class="admin-dashboard-grid mt-6">
        <section class="admin-card admin-dashboard-panel admin-dashboard-panel--wide">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Dönüşüm hunisi</p>
                    <h2>Bu ay müşteri akışı</h2>
                </div>
            </div>
            <div class="admin-analytics-funnel">
                <div>
                    <span>Ziyaretçi</span>
                    <strong>{{ $monthVisitors }}</strong>
                    <small>{{ $monthPageViews }} sayfa görüntüleme</small>
                </div>
                <div>
                    <span>Checkout başlangıcı</span>
                    <strong>{{ $checkoutStarts }}</strong>
                    <small>Sepetten ödeme adımına geçenler</small>
                </div>
                <div>
                    <span>Sipariş</span>
                    <strong>{{ $ordersThisMonth }}</strong>
                    <small>Bu ay oluşan sipariş</small>
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
                            <small>{{ number_format($source['revenue'], 2, ',', '.') }} ₺ · dönüşüm {{ $source['conversion'] ?? '—' }}%</small>
                        </span>
                        <strong>{{ $source['orders'] }} sipariş</strong>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sipariş kaynak verisi sipariş geldikçe oluşacak.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="admin-card admin-dashboard-panel mt-6">
        <div class="admin-panel-head">
            <div>
                <p class="admin-dashboard-eyebrow">Anlık trafik</p>
                <h2>Şu an hangi sayfalardalar?</h2>
            </div>
        </div>
        <div class="admin-top-products">
            @forelse($activeVisitorList as $visitor)
                <a href="{{ route('admin.analytics.visitor', $visitor) }}" class="admin-top-product">
                    <div class="min-w-0">
                        <p class="truncate">{{ $visitor->last_url }}</p>
                        <span>{{ $visitor->device_type ?: 'cihaz bilinmiyor' }} · {{ $visitor->utm_source ?: 'direct' }}</span>
                    </div>
                    <strong>{{ $visitor->last_seen_at?->diffForHumans(null, true) }}</strong>
                </a>
            @empty
                <p class="text-sm text-slate-500">Son 5 dakikada aktif ziyaretçi görünmüyor.</p>
            @endforelse
        </div>
    </section>

    <div class="admin-dashboard-grid admin-dashboard-grid--secondary mt-6">
        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Ürün ilgisi</p>
                    <h2>En çok bakılanlar</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($topViewedProducts as $product)
                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="admin-top-product">
                        <div class="min-w-0">
                            <p class="truncate">{{ $product->name }}</p>
                            <span>Son 30 gün ürün görüntüleme</span>
                        </div>
                        <strong>{{ (int) $product->views }}</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Ürün görüntüleme verisi oluşunca burada listelenir.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Dönüşüm</p>
                    <h2>Ürün görüntüleme → sepet</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($productConversions as $product)
                    <a href="{{ route('products.show', $product['slug']) }}" target="_blank" class="admin-top-product">
                        <div class="min-w-0">
                            <p class="truncate">{{ $product['name'] }}</p>
                            <span>{{ $product['views'] }} görüntüleme · {{ $product['cart_adds'] }} sepete ekleme</span>
                        </div>
                        <strong>%{{ $product['rate'] }}</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Dönüşüm verisi oluşunca burada listelenir.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Sepet ilgisi</p>
                    <h2>Sepete en çok eklenenler</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($topCartProducts as $product)
                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="admin-top-product">
                        <div class="min-w-0">
                            <p class="truncate">{{ $product->name }}</p>
                            <span>Son 30 gün sepete ekleme</span>
                        </div>
                        <strong>{{ (int) $product->cart_adds }}</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Sepet verisi oluşunca burada listelenir.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-card admin-dashboard-panel">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Yarım sepet</p>
                    <h2>Son kalan sepetler</h2>
                </div>
            </div>
            <div class="admin-top-products">
                @forelse($abandonedCarts->take(6) as $cart)
                    <a href="{{ $cart->visitor ? route('admin.analytics.visitor', $cart->visitor) : '#' }}" class="admin-top-product">
                        <div class="min-w-0">
                            <p class="truncate">{{ collect($cart->items ?? [])->pluck('name')->take(2)->implode(', ') ?: 'Sepet' }}</p>
                            <span>{{ $cart->email ?: ($cart->phone ?: 'Anonim') }} · {{ $cart->last_activity_at?->diffForHumans() }}</span>
                        </div>
                        <strong>{{ number_format((float) $cart->subtotal, 2, ',', '.') }} ₺</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Yarım kalan sepet görünmüyor.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="admin-card mt-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
            <p class="admin-dashboard-eyebrow">Canlı akış</p>
            <h2 class="font-bold text-slate-900 mt-1">Son müşteri hareketleri</h2>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--stack">
                <thead>
                    <tr>
                        <th>Olay</th>
                        <th>Ürün / Sipariş</th>
                        <th>Adres</th>
                        <th>Zaman</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentEvents as $event)
                        <tr>
                            <td data-label="Olay">{{ str_replace('_', ' ', $event->event_type) }}</td>
                            <td data-label="Ürün / Sipariş">
                                @if($event->product)
                                    <a href="{{ route('products.show', $event->product->slug) }}" target="_blank" class="link">{{ $event->product->name }}</a>
                                @elseif($event->order)
                                    <a href="{{ route('admin.orders.show', $event->order) }}" class="link">{{ $event->order->order_number }}</a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td data-label="Adres" class="max-w-[280px] truncate">{{ $event->url }}</td>
                            <td data-label="Zaman">
                                @if($event->visitor)
                                    <a href="{{ route('admin.analytics.visitor', $event->visitor) }}" class="link">{{ $event->occurred_at?->format('d.m.Y H:i') }}</a>
                                @else
                                    {{ $event->occurred_at?->format('d.m.Y H:i') }}
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
@endsection
