@extends('layouts.admin')
@section('title', 'Pazaryerleri')

@section('content')
    <x-admin.page-header title="Pazaryerleri" subtitle="Trendyol, Hepsiburada, N11, Idefix, Pazarama ve Akakçe entegrasyon merkezi">
        <x-slot:actions>
            <a href="{{ route('admin.integrations.marketplace.listings.index') }}" class="admin-btn admin-btn-secondary">Listelemeler</a>
            <a href="{{ route('admin.integrations.marketplace.mappings.index') }}" class="admin-btn admin-btn-secondary">Eşleştirmeler</a>
            <a href="{{ route('admin.integrations.marketplace.readiness') }}" class="admin-btn admin-btn-secondary">Katalog hazırlığı</a>
            <a href="{{ route('admin.integrations.marketplace.channels.index') }}" class="admin-btn admin-btn-primary">Kanal ayarları</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="marketplace" />

    <div class="admin-dashboard-stats mb-6">
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Aktif ürün</span>
            <strong>{{ number_format($stats['active_products']) }}</strong>
            <small>Pazaryerine açık: {{ number_format($stats['marketplace_enabled']) }}</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Barkodlu ürün</span>
            <strong>{{ number_format($stats['with_barcode']) }}</strong>
            <small>EAN/GTIN tanımlı</small>
        </div>
        <div class="admin-metric-card admin-metric-card--primary admin-analytics-metric">
            <span class="admin-metric-card__label">Gönderime hazır</span>
            <strong>{{ number_format($stats['ready']) }}</strong>
            <small>Eksik: {{ number_format($stats['not_ready']) }} (örnek set)</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Listeleme kaydı</span>
            <strong>{{ number_format($stats['listings']) }}</strong>
            <small>Yayında: {{ number_format($stats['published_listings']) }}</small>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2 mb-6">
        <section class="admin-card p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Pazaryeri kanalları</h2>
            <div class="space-y-3">
                @foreach($channels as $channel)
                    <a href="{{ route('admin.integrations.marketplace.channels.edit', $channel) }}" class="admin-analytics-row block">
                        <div class="admin-analytics-row__body">
                            <p class="font-semibold text-slate-900">{{ $channel->name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $channel->type === 'feed' ? 'Feed kanalı' : 'Tam pazaryeri' }}
                                · {{ $channel->is_active ? 'Aktif' : 'Pasif' }}
                                · {{ $channel->isConfigured() ? 'API kayıtlı' : 'API eksik' }}
                            </p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $channel->is_active && $channel->isConfigured() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $channel->is_active && $channel->isConfigured() ? 'Hazır' : 'Kurulum' }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="admin-card p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-2">Modül durumu</h2>
            <p class="text-sm text-slate-600 leading-relaxed mb-4">
                <strong>Faz 3 (Trendyol pilot)</strong> aktif: ürün gönderimi ve sipariş import. Otomatik stok/fiyat sync <strong>Faz 4</strong> ile gelecek.
            </p>
            <ul class="text-sm text-slate-600 space-y-2 list-disc pl-5">
                <li>Trendyol API bağlantı testi ve ürün gönderimi</li>
                <li>Listeleme durumu takibi (taslak → onay → yayın)</li>
                <li>Trendyol sipariş import ve stok düşümü</li>
                <li>Kategori, marka ve özellik eşleştirme merkezi</li>
            </ul>
        </section>
    </div>

    <section class="admin-card overflow-hidden">
        <div class="admin-panel-head px-5 py-4 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Son sync logları</h2>
        </div>
        @if($recentLogs->isEmpty())
            <p class="p-8 text-center text-slate-500">Henüz log kaydı yok.</p>
        @else
            <table class="admin-table admin-table--stack">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Kanal</th>
                        <th>İşlem</th>
                        <th>Durum</th>
                        <th>Mesaj</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                        <tr>
                            <td data-label="Tarih" class="text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                            <td data-label="Kanal">{{ $log->channel_key ?: '—' }}</td>
                            <td data-label="İşlem" class="font-mono text-xs">{{ $log->action }}</td>
                            <td data-label="Durum">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $log->status === 'success' ? 'bg-emerald-50 text-emerald-700' : ($log->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td data-label="Mesaj" class="text-sm text-slate-600 max-w-md truncate">{{ $log->message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
