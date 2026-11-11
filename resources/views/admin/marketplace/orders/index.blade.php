@extends('layouts.admin')
@section('title', 'Pazaryeri sipariş sync')

@section('content')
    <x-admin.page-header title="Pazaryeri sipariş senkronu" subtitle="Tüm kanallardan sipariş çekilir; yeni sipariş Telegram’a düşer">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.integrations.marketplace.listings.index')">Listelemeler</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="marketplace-orders" />

    <div class="admin-dashboard-stats mb-6">
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Pazaryeri sipariş</span>
            <strong>{{ number_format($totalMarketplaceOrders) }}</strong>
            <small>Mağazada kayıtlı</small>
        </div>
        @foreach($channels as $ch)
            <div class="admin-metric-card admin-analytics-metric">
                <span class="admin-metric-card__label">{{ $ch->name ?: $ch->key }}</span>
                <strong>{{ number_format((int) ($channelCounts[$ch->key] ?? 0)) }}</strong>
                <small>
                    {{ $ch->isConfigured() && $ch->is_active ? 'Hazır' : 'Kurulum' }}
                    · {{ $ch->setting('orders_last_sync_at') ? \Illuminate\Support\Carbon::parse($ch->setting('orders_last_sync_at'))->format('d.m H:i') : 'henüz yok' }}
                </small>
            </div>
        @endforeach
    </div>

    <section class="admin-card p-5 sm:p-6 max-w-3xl mb-6">
        <h2 class="font-semibold text-slate-900 mb-2">Manuel import</h2>
        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
            Aktif ve API’si kayıtlı tüm pazaryerlerinden sipariş çekilir. Yeni siparişler mağazaya eklenir ve Telegram bildirimi gider; mevcut siparişlerin durumu güncellenir.
        </p>
        <div class="flex flex-wrap gap-3">
            <form method="post" action="{{ route('admin.integrations.marketplace.orders.import') }}">
                @csrf
                <input type="hidden" name="queue" value="1">
                <x-admin.button variant="primary" type="submit">Tümünü kuyruğa al</x-admin.button>
            </form>
            <form method="post" action="{{ route('admin.integrations.marketplace.orders.import') }}">
                @csrf
                <input type="hidden" name="queue" value="0">
                <x-admin.button variant="secondary" type="submit">Şimdi çalıştır</x-admin.button>
            </form>
        </div>
        @error('import')<p class="text-sm text-red-600 mt-3">{{ $message }}</p>@enderror
        <p class="text-xs text-slate-500 mt-4">Cron: <code class="font-mono">php artisan marketplace:import-orders --sync</code> · her 10 dk</p>
    </section>

    <section class="admin-card overflow-hidden">
        <div class="admin-panel-head px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
            <h2 class="font-semibold text-slate-900">Son pazaryeri siparişleri</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-[var(--admin-primary)] font-medium">Tüm siparişler →</a>
        </div>
        @if($recentOrders->isEmpty())
            <p class="p-8 text-center text-slate-500">Henüz pazaryeri siparişi yok.</p>
        @else
            <table class="admin-table admin-table--stack">
                <thead>
                    <tr>
                        <th>Sipariş</th>
                        <th>Kanal</th>
                        <th>Dış no</th>
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
                            <td data-label="Kanal">{{ config('marketplace.sales_channels.'.$order->sales_channel, $order->sales_channel) }}</td>
                            <td data-label="Dış no" class="font-mono text-xs">{{ $order->external_order_id }}</td>
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
