@extends('layouts.admin')
@section('title', 'Listelemeler')

@section('content')
    <x-admin.page-header title="Listeleme yönetimi" subtitle="Trendyol ürün gönderimi ve durum takibi">
        <x-slot:actions>
            <a href="{{ route('admin.integrations.marketplace.orders.index') }}" class="admin-btn admin-btn-secondary">Sipariş sync</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="marketplace-listings" />

    <form method="get" class="admin-card p-4 mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-[160px]">
            <label class="admin-label">Kanal</label>
            <select name="channel" class="admin-input" onchange="this.form.submit()">
                @foreach($channels as $channel)
                    <option value="{{ $channel->key }}" @selected($channelKey === $channel->key)>{{ $channel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label class="admin-label">Durum</label>
            <select name="status" class="admin-input">
                <option value="">Tümü</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[200px] flex-1">
            <label class="admin-label">Ürün ara</label>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="admin-input" placeholder="Ad, SKU, barkod">
        </div>
        <button class="admin-btn admin-btn-secondary px-4 py-2.5">Filtrele</button>
    </form>

    @if($channelKey === 'trendyol' && $readyProducts->isNotEmpty())
        <section class="admin-card p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Gönderime hazır ürünler</h2>
                    <p class="text-sm text-slate-600">Henüz yayında olmayan, pazaryeri açık ürünler</p>
                </div>
                <form method="post" action="{{ route('admin.integrations.marketplace.listings.bulk-publish') }}" id="bulk-publish-form">
                    @csrf
                    <input type="hidden" name="channel_key" value="{{ $channelKey }}">
                    <button type="submit" class="admin-btn admin-btn-primary" onclick="return confirm('Seçili ürünler Trendyol kuyruğuna eklensin mi?')">Seçilileri gönder</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table admin-table--stack min-w-[640px]">
                    <thead>
                        <tr>
                            <th><input type="checkbox" data-listing-check-all aria-label="Tümünü seç"></th>
                            <th>Ürün</th>
                            <th>SKU / Barkod</th>
                            <th>Fiyat</th>
                            <th>Stok</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readyProducts as $product)
                            <tr>
                                <td data-label="Seç">
                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" form="bulk-publish-form" data-listing-check>
                                </td>
                                <td data-label="Ürün" class="font-medium text-slate-900 max-w-xs truncate">{{ $product->name }}</td>
                                <td data-label="SKU" class="text-xs font-mono text-slate-600">{{ $product->sku ?: '—' }} · {{ $product->barcode ?: '—' }}</td>
                                <td data-label="Fiyat">{{ number_format($product->price, 2, ',', '.') }} ₺</td>
                                <td data-label="Stok">{{ $product->stock }}</td>
                                <td data-label="">
                                    <form method="post" action="{{ route('admin.integrations.marketplace.listings.publish') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="channel_key" value="{{ $channelKey }}">
                                        <button class="admin-btn admin-btn-secondary text-xs py-1.5">Gönder</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="admin-card overflow-hidden">
        <div class="admin-panel-head px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">Listeleme kayıtları</h2>
        </div>
        @if($listings->isEmpty())
            <p class="p-8 text-center text-slate-500">Henüz listeleme kaydı yok.</p>
        @else
            <div class="overflow-x-auto">
                <table class="admin-table admin-table--stack min-w-[720px]">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Durum</th>
                            <th>Fiyat</th>
                            <th>Stok limit</th>
                            <th>Son sync</th>
                            <th>Hata</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($listings as $listing)
                            <tr>
                                <td data-label="Ürün">
                                    <p class="font-medium text-slate-900">{{ $listing->product?->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $listing->external_sku ?: $listing->product?->sku }}</p>
                                </td>
                                <td data-label="Durum">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $listing->status === 'published' ? 'bg-emerald-50 text-emerald-700' : ($listing->status === 'error' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                                        {{ $listing->statusLabel() }}
                                    </span>
                                </td>
                                <td data-label="Fiyat">{{ $listing->channel_price ? number_format($listing->channel_price, 2, ',', '.').' ₺' : '—' }}</td>
                                <td data-label="Stok">{{ $listing->channel_stock_limit ?? '—' }}</td>
                                <td data-label="Sync" class="text-xs text-slate-500 whitespace-nowrap">{{ $listing->last_synced_at?->format('d.m.Y H:i') ?: '—' }}</td>
                                <td data-label="Hata" class="text-xs text-red-600 max-w-[200px] truncate">{{ $listing->last_error ?: '—' }}</td>
                                <td data-label="">
                                    @if(in_array($listing->status, ['error', 'rejected', 'draft'], true))
                                        <form method="post" action="{{ route('admin.integrations.marketplace.listings.retry', $listing) }}">
                                            @csrf
                                            <button class="admin-btn admin-btn-secondary text-xs py-1.5">Yeniden dene</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $listings->links() }}</div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.querySelector('[data-listing-check-all]')?.addEventListener('change', function () {
                document.querySelectorAll('[data-listing-check]').forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });
            });
        </script>
    @endpush
@endsection
