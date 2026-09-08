@extends('layouts.admin')
@section('title', 'Rakip fiyat')

@section('content')
    <x-admin.page-header title="Manuel rakip teklifleri" subtitle="İsteğe bağlı URL eşleştirmesi — asıl akış Google piyasa taramasıdır">
        <x-slot:actions>
            <a href="{{ route('admin.competitor-pricing.market') }}" class="admin-btn admin-btn-primary">Google piyasa</a>
            <a href="{{ route('admin.competitor-pricing.settings') }}" class="admin-btn admin-btn-secondary">Kurallar</a>
            <a href="{{ route('admin.competitor-pricing.create') }}" class="admin-btn admin-btn-secondary">+ URL teklif</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Toplam teklif</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Onaylı</p>
            <p class="text-2xl font-semibold text-teal-800 mt-1">{{ $stats['approved'] }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Onay bekleyen</p>
            <p class="text-2xl font-semibold text-amber-700 mt-1">{{ $stats['pending'] }}</p>
        </div>
    </div>

    <div class="admin-card p-4 mb-4 flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
        <p class="text-sm text-slate-600">
            Kural: <span class="font-semibold text-slate-900">%{{ rtrim(rtrim(number_format((float) $rule->undercut_percent, 2, ',', '.'), '0'), ',') }}</span> altında
            @if($rule->min_price)
                · taban {{ number_format((float) $rule->min_price, 2, ',', '.') }} ₺
            @endif
        </p>
        <form method="get" action="{{ route('admin.competitor-pricing.index') }}" class="flex gap-2 w-full sm:w-auto">
            <input type="search" name="q" value="{{ $q }}" placeholder="Ürün, SKU veya rakip…" class="admin-input text-sm min-w-0 flex-1 sm:w-64">
            <button type="submit" class="admin-btn admin-btn-secondary shrink-0">Ara</button>
        </form>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table min-w-[920px]">
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Rakip</th>
                        <th>Durum</th>
                        <th>Rakip fiyat</th>
                        <th>Bizim / Öneri</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            /** @var \App\Models\CompetitorOffer $offer */
                            $offer = $row['offer'];
                            $product = $row['product'];
                            $suggestion = $row['suggestion'];
                        @endphp
                        <tr>
                            <td class="align-top max-w-[220px]">
                                @if($product)
                                    <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-slate-900 hover:text-teal-800 line-clamp-2">{{ $product->name }}</a>
                                    <p class="text-xs text-slate-500 mt-0.5 font-mono">{{ $product->sku }}</p>
                                @else
                                    <span class="text-slate-400">Ürün silinmiş</span>
                                @endif
                            </td>
                            <td class="align-top max-w-[200px]">
                                <p class="font-medium text-slate-800">{{ $offer->competitor_name }}</p>
                                <a href="{{ $offer->competitor_url }}" target="_blank" rel="noopener" class="text-xs text-teal-700 hover:underline break-all line-clamp-2">{{ \Illuminate\Support\Str::limit($offer->competitor_url, 60) }}</a>
                                @if($offer->last_error)
                                    <p class="text-xs text-red-600 mt-1">{{ \Illuminate\Support\Str::limit($offer->last_error, 80) }}</p>
                                @endif
                            </td>
                            <td class="align-top">
                                @php
                                    $badge = match($offer->match_status) {
                                        'approved' => 'admin-badge-success',
                                        'rejected' => 'admin-badge-muted',
                                        default => 'admin-badge-warning',
                                    };
                                @endphp
                                <span class="admin-badge {{ $badge }}">{{ $offer->statusLabel() }}</span>
                                @if(! $offer->active)
                                    <span class="admin-badge admin-badge-muted mt-1 inline-block">Pasif</span>
                                @endif
                            </td>
                            <td class="align-top whitespace-nowrap">
                                @if($offer->last_price !== null)
                                    <span class="font-semibold">{{ number_format((float) $offer->last_price, 2, ',', '.') }} ₺</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if($offer->last_fetched_at)
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $offer->last_fetched_at->format('d.m.Y H:i') }}</p>
                                @endif
                            </td>
                            <td class="align-top whitespace-nowrap">
                                @if($product)
                                    <p class="text-slate-700">{{ number_format((float) $product->price, 2, ',', '.') }} ₺</p>
                                    @if($suggestion && $suggestion['suggested'])
                                        <p class="text-sm mt-0.5 {{ $suggestion['can_apply'] ? 'text-teal-800 font-semibold' : 'text-slate-500' }}">
                                            Öneri: {{ number_format((float) $suggestion['suggested'], 2, ',', '.') }} ₺
                                        </p>
                                        @if($suggestion['reason'])
                                            <p class="text-xs text-slate-400 max-w-[180px]">{{ $suggestion['reason'] }}</p>
                                        @endif
                                    @endif
                                @endif
                            </td>
                            <td class="align-top text-right">
                                <div class="inline-flex flex-wrap justify-end gap-1.5 max-w-[220px]">
                                    <form method="post" action="{{ route('admin.competitor-pricing.fetch', $offer) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-secondary text-xs py-1.5 px-2.5">Çek</button>
                                    </form>
                                    @if($offer->match_status !== 'approved')
                                        <form method="post" action="{{ route('admin.competitor-pricing.approve', $offer) }}">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-secondary text-xs py-1.5 px-2.5">Onayla</button>
                                        </form>
                                    @endif
                                    @if($offer->match_status !== 'rejected')
                                        <form method="post" action="{{ route('admin.competitor-pricing.reject', $offer) }}">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-secondary text-xs py-1.5 px-2.5">Reddet</button>
                                        </form>
                                    @endif
                                    @if($suggestion && ($suggestion['can_apply'] ?? false))
                                        <form method="post" action="{{ route('admin.competitor-pricing.apply', $offer) }}" onsubmit="return confirm('Önerilen fiyat ürüne uygulansın mı?')">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-primary text-xs py-1.5 px-2.5">Uygula</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.competitor-pricing.edit', $offer) }}" class="admin-btn admin-btn-secondary text-xs py-1.5 px-2.5">Düzenle</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500 py-10">
                                Henüz rakip teklif yok.
                                <a href="{{ route('admin.competitor-pricing.create') }}" class="text-teal-700 font-medium hover:underline">İlk teklifi ekleyin</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($offers->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $offers->links() }}</div>
        @endif
    </div>
@endsection
