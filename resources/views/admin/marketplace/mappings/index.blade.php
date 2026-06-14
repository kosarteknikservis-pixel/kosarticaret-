@extends('layouts.admin')
@section('title', 'Eşleştirmeler')

@section('content')
    <x-admin.page-header title="Eşleştirme merkezi" subtitle="Kategori, marka ve özellik eşleştirmelerini pazaryeri kanallarına göre yönetin">
        <x-slot:actions>
            <a href="{{ route('admin.integrations.marketplace.mappings.categories', ['channel' => $channelKey]) }}" class="admin-btn admin-btn-primary">Kategori eşleştir</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="marketplace-mappings" />

    @include('admin.marketplace.mappings.partials.channel-select', ['channels' => $channels, 'channelKey' => $channelKey])

    <div class="admin-dashboard-stats mb-6">
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Kategori</span>
            <strong>{{ number_format($stats['categories']['mapped']) }}</strong>
            <small>/ {{ number_format($stats['categories']['total']) }} eşleşti</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Marka</span>
            <strong>{{ number_format($stats['brands']['mapped']) }}</strong>
            <small>/ {{ number_format($stats['brands']['total']) }} eşleşti</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Özellik eşlemesi</span>
            <strong>{{ number_format($stats['attributes']) }}</strong>
            <small>specs → platform alanı</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Harici kategori</span>
            <strong>{{ number_format($stats['external_categories']) }}</strong>
            <small>Import edilmiş liste</small>
        </div>
    </div>

    @php
        $categoryPct = $stats['categories']['total'] > 0
            ? round(($stats['categories']['mapped'] / $stats['categories']['total']) * 100)
            : 0;
        $brandPct = $stats['brands']['total'] > 0
            ? round(($stats['brands']['mapped'] / $stats['brands']['total']) * 100)
            : 0;
    @endphp

    @if($categoryPct < 100 || $stats['external_categories'] === 0)
        <div class="admin-card p-5 mb-6 border-amber-200 bg-amber-50/60">
            <p class="text-sm font-semibold text-amber-900 mb-1">Eksik eşleştirme uyarısı</p>
            <p class="text-sm text-amber-800 leading-relaxed">
                @if($stats['external_categories'] === 0)
                    Bu kanal için harici kategori listesi henüz import edilmedi. Otomatik öneri ve doğru eşleştirme için önce kategori JSON dosyasını yükleyin.
                @else
                    {{ $stats['categories']['total'] - $stats['categories']['mapped'] }} kategori ve {{ $stats['brands']['total'] - $stats['brands']['mapped'] }} marka henüz eşleştirilmedi.
                @endif
            </p>
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3 mb-6">
        <a href="{{ route('admin.integrations.marketplace.mappings.categories', ['channel' => $channelKey]) }}" class="admin-card p-5 hover:border-slate-300 transition-colors block">
            <h2 class="font-semibold text-slate-900 mb-1">Kategori eşleştirme</h2>
            <p class="text-sm text-slate-600">Mağaza kategorilerini platform kategorilerine bağlayın. JSON import ve otomatik öneri.</p>
        </a>
        <a href="{{ route('admin.integrations.marketplace.mappings.brands', ['channel' => $channelKey]) }}" class="admin-card p-5 hover:border-slate-300 transition-colors block">
            <h2 class="font-semibold text-slate-900 mb-1">Marka eşleştirme</h2>
            <p class="text-sm text-slate-600">Markalarınızı pazaryeri marka kimlikleriyle eşleştirin.</p>
        </a>
        <a href="{{ route('admin.integrations.marketplace.mappings.attributes', ['channel' => $channelKey]) }}" class="admin-card p-5 hover:border-slate-300 transition-colors block">
            <h2 class="font-semibold text-slate-900 mb-1">Özellik eşleştirme</h2>
            <p class="text-sm text-slate-600">Ürün specs alanlarını platform zorunlu attribute’larına map edin.</p>
        </a>
    </div>

    <section class="admin-card p-5 sm:p-6 max-w-3xl">
        <h2 class="text-lg font-semibold text-slate-900 mb-2">Yedekleme & geri yükleme</h2>
        <p class="text-sm text-slate-600 mb-4">Tüm eşleştirmeleri JSON olarak dışa aktarın veya önceki yedeği geri yükleyin.</p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.integrations.marketplace.mappings.export', ['channel' => $channelKey]) }}" class="admin-btn admin-btn-secondary">JSON indir ({{ $channelKey }})</a>
            <a href="{{ route('admin.integrations.marketplace.mappings.export') }}" class="admin-btn admin-btn-secondary">Tüm kanallar JSON</a>
        </div>
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.integrations.marketplace.mappings.import') }}" class="mt-5 space-y-3 border-t border-slate-100 pt-5">
            @csrf
            <div>
                <label class="admin-label">Yedek JSON dosyası</label>
                <input type="file" name="json_file" accept=".json,application/json" required class="admin-input">
                @error('json_file')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button class="admin-btn admin-btn-primary px-5 py-2.5">Geri yükle</button>
        </form>
    </section>
@endsection
