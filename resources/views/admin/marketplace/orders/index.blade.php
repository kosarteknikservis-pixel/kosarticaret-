@extends('layouts.admin')
@section('title', 'Trendyol sipariş sync')

@section('content')
    <x-admin.page-header title="Trendyol sipariş senkronu" subtitle="Pazaryeri siparişlerini mağaza siparişlerine aktarın">
        <x-slot:actions>
            <a href="{{ route('admin.integrations.marketplace.listings.index') }}" class="admin-btn admin-btn-secondary">Listelemeler</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="marketplace-orders" />

    <div class="admin-dashboard-stats mb-6">
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Trendyol sipariş</span>
            <strong>{{ number_format($totalTrendyolOrders) }}</strong>
            <small>Mağazada kayıtlı</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Son sync</span>
            <strong class="text-base">{{ $channel?->setting('orders_last_sync_at') ? \Illuminate\Support\Carbon::parse($channel->setting('orders_last_sync_at'))->format('d.m.Y H:i') : '—' }}</strong>
            <small>{{ $channel?->isConfigured() ? 'API bağlı' : 'API eksik' }}</small>
        </div>
    </div>

    <section class="admin-card p-5 sm:p-6 max-w-3xl mb-6">
        <h2 class="font-semibold text-slate-900 mb-2">Manuel import</h2>
        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
            Son 7 gün (veya son sync’ten itibaren) Trendyol sipariş paketleri çekilir. Yeni siparişler mağazaya eklenir; mevcut siparişlerin durumu güncellenir.
            Stok, eşleşen ürün bulunursa düşülür.
        </p>
        <div class="flex flex-wrap gap-3">
            <form method="post" action="{{ route('admin.integrations.marketplace.orders.import') }}">
                @csrf
                <input type="hidden" name="queue" value="1">
                <button class="admin-btn admin-btn-primary px-5 py-2.5">Kuyruğa al</button>
            </form>
            <form method="post" action="{{ route('admin.integrations.marketplace.orders.import') }}">
                @csrf
                <input type="hidden" name="queue" value="0">
                <button class="admin-btn admin-btn-secondary px-5 py-2.5">Şimdi çalıştır</button>
            </form>
        </div>
        @error('import')<p class="text-sm text-red-600 mt-3">{{ $message }}</p>@enderror
        <p class="text-xs text-slate-500 mt-4">Cron: <code class="font-mono">php artisan marketplace:import-trendyol-orders --sync</code></p>
    </section>

    <section class="admin-card overflow-hidden">
        <div class="admin-panel-head px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
            <h2 class="font-semibold text-slate-900">Son Trendyol siparişleri</h2>
            <a href="{{ route('admin.orders.index', ['sales_channel' => 'trendyol']) }}" class="text-sm text-[var(--admin-primary)] font-medium">Tümünü gör →</a>
        </div>
        @if($recentOrders->isEmpty())
            <p class="p-8 text-center text-slate-500">Henüz Trendyol siparişi yok.</p>
        @else
            <table class="admin-table admin-table--stack">
                <thead>
                    <tr>
                        <th>Sipariş</th>
                        <th>Trendyol no</th>
                        <th>Müşteri</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td data-label="Sipariş"><a href="{{ route('admin.orders.show', $order) }}" class="link font-mono text-xs">{{ $order->order_number }}</a></td>
                            <td data-label="Trendyol" class="font-mono text-xs">{{ $order->external_order_id }}</td>
                            <td data-label="Müşteri">{{ $order->customer_name }}</td>
                            <td data-label="Tutar">{{ number_format($order->total, 2, ',', '.') }} ₺</td>
                            <td data-label="Durum">{{ \App\Support\OrderStatus::label($order->status) }}</td>
                            <td data-label="Tarih" class="text-xs text-slate-500">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
