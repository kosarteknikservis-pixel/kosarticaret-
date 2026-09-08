@extends('layouts.admin')
@section('title', 'Google piyasa fiyatları')

@section('content')
    <x-admin.page-header title="Google piyasa fiyatları" subtitle="Ürünleriniz Google Shopping’da aranır — yanlış eşleşmeyi onaylamadan fiyat değişmez">
        <x-slot:actions>
            <a href="{{ route('admin.competitor-pricing.index') }}" class="admin-btn admin-btn-secondary">Manuel teklifler</a>
            <a href="{{ route('admin.competitor-pricing.settings') }}" class="admin-btn admin-btn-secondary">Kurallar</a>
        </x-slot:actions>
    </x-admin.page-header>

    @unless($dataforseoReady)
        <div class="admin-alert-error mb-4">
            DataForSEO kimlik bilgileri yok. `.env` içine <code>DATAFORSEO_USERNAME</code> ve <code>DATAFORSEO_PASSWORD</code> ekleyin (canlı sunucu dahil).
        </div>
    @endunless

    <form method="post" action="{{ route('admin.competitor-pricing.market.scan-batch') }}" class="admin-card p-4 mb-5">
        @csrf
        <div class="flex flex-col lg:flex-row lg:items-end gap-3">
            <div class="flex-1 min-w-0">
                <label class="admin-label">Toplu Google tarama (kuyruk)</label>
                <p class="text-xs text-slate-500 mb-2">~1.400 ürün tek seferde kuyruğa alınabilir; sunucu dakikada işler. Fiyat otomatik düşmez.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="admin-label text-xs">Kapsam</label>
                        <select name="mode" class="admin-input">
                            <option value="missing" selected>Sadece taranmamışlar (önerilen)</option>
                            <option value="stale">Taranmamış + 7 günden eski</option>
                            <option value="all">Tüm aktif ürünler (yeniden tara)</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label text-xs">Adet limiti</label>
                        <select name="limit" class="admin-input">
                            <option value="50">50 ürün</option>
                            <option value="100">100 ürün</option>
                            <option value="250">250 ürün</option>
                            <option value="500">500 ürün</option>
                            <option value="" selected>Limit yok — hepsi</option>
                        </select>
                    </div>
                </div>
            </div>
            <button
                type="submit"
                class="admin-btn admin-btn-primary shrink-0 h-[42px] px-5"
                @if(! $dataforseoReady) disabled @endif
                onclick="return confirm('Seçilen ürünler Google Shopping kuyruğuna alınsın mı? API ücreti oluşur; fiyat otomatik değişmez.')"
            >
                Kuyruğa al
            </button>
        </div>
        @if(($stats['queued'] ?? 0) > 0)
            <p class="text-sm text-teal-800 mt-3 font-medium">Kuyrukta bekleyen tarama işi: {{ number_format($stats['queued'], 0, ',', '.') }}</p>
        @endif
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Taranmış</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $stats['scanned'] }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">İnceleme</p>
            <p class="text-2xl font-semibold text-amber-700 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Onaylı</p>
            <p class="text-2xl font-semibold text-teal-800 mt-1">{{ $stats['approved'] }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Henüz taranmadı</p>
            <p class="text-2xl font-semibold text-slate-700 mt-1">{{ $stats['missing'] }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Kuyruk</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $stats['queued'] ?? 0 }}</p>
        </div>
    </div>

    <div class="admin-card p-4 mb-4 flex flex-col lg:flex-row gap-3 lg:items-center justify-between">
        <div class="flex flex-wrap gap-2 text-sm">
            @foreach([
                'all' => 'Tümü',
                'expensive' => 'Biz pahalıyız',
                'pending' => 'İnceleme',
                'approved' => 'Onaylı',
                'missing' => 'Taranmamış',
            ] as $key => $label)
                <a href="{{ route('admin.competitor-pricing.market', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
                   class="px-3 py-1.5 rounded-lg border {{ $filter === $key ? 'border-teal-700 bg-teal-50 text-teal-900 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <form method="get" class="flex gap-2 w-full lg:w-auto">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Ürün / SKU / barkod…" class="admin-input text-sm min-w-0 flex-1 lg:w-64">
            <button class="admin-btn admin-btn-secondary shrink-0">Ara</button>
        </form>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Bizim</th>
                        <th>Google min</th>
                        <th>Öneri</th>
                        <th>Durum</th>
                        <th>Teklifler</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $product = $row['product'];
                            $scan = $row['scan'];
                            $suggestion = $row['suggestion'];
                        @endphp
                        <tr>
                            <td class="align-top max-w-[240px]">
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-slate-900 hover:text-teal-800 line-clamp-2">{{ $product->name }}</a>
                                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $product->sku }}</p>
                                @if($scan?->search_query)
                                    <p class="text-xs text-slate-400 mt-1 line-clamp-1">Arama: {{ $scan->search_query }}</p>
                                @endif
                            </td>
                            <td class="align-top whitespace-nowrap font-semibold">{{ number_format((float) $product->price, 2, ',', '.') }} ₺</td>
                            <td class="align-top whitespace-nowrap">
                                @if($scan?->google_min_price)
                                    <span class="font-semibold {{ (float) $product->price > (float) ($scan->competitivePrice() ?? $scan->google_min_price) ? 'text-amber-700' : 'text-teal-800' }}">
                                        {{ number_format((float) ($scan->competitivePrice() ?? $scan->google_min_price), 2, ',', '.') }} ₺
                                    </span>
                                    @if($scan->google_median_price)
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            medyan {{ number_format((float) $scan->google_median_price, 2, ',', '.') }} ₺
                                            @if((float) $scan->google_min_price < (float) $scan->google_median_price * 0.85)
                                                · ham min {{ number_format((float) $scan->google_min_price, 2, ',', '.') }} ₺ (elenmiş)
                                            @endif
                                        </p>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="align-top whitespace-nowrap">
                                @if($suggestion['suggested'])
                                    <span class="{{ $suggestion['can_apply'] ? 'text-teal-800 font-semibold' : 'text-slate-500' }}">
                                        {{ number_format((float) $suggestion['suggested'], 2, ',', '.') }} ₺
                                    </span>
                                    @if($suggestion['reason'])
                                        <p class="text-xs text-slate-400 max-w-[160px]">{{ $suggestion['reason'] }}</p>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="align-top">
                                @if($scan)
                                    @php
                                        $badge = match($scan->status) {
                                            'approved' => 'admin-badge-success',
                                            'rejected', 'error', 'no_results' => 'admin-badge-muted',
                                            default => 'admin-badge-warning',
                                        };
                                    @endphp
                                    <span class="admin-badge {{ $badge }}">{{ $scan->statusLabel() }}</span>
                                    @if($scan->last_scanned_at)
                                        <p class="text-xs text-slate-400 mt-1">{{ $scan->last_scanned_at->format('d.m.Y H:i') }}</p>
                                    @endif
                                    @if($scan->last_error && $scan->status === 'error')
                                        <p class="text-xs text-red-600 mt-1 max-w-[160px]">{{ \Illuminate\Support\Str::limit($scan->last_error, 90) }}</p>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-sm">Taranmadı</span>
                                @endif
                            </td>
                            <td class="align-top max-w-[220px]">
                                @if($scan && is_array($scan->offers) && count($scan->offers))
                                    <ul class="space-y-1 text-xs text-slate-600">
                                        @foreach(array_slice($scan->offers, 0, 3) as $offer)
                                            <li class="line-clamp-2">
                                                <span class="font-semibold tabular-nums">{{ number_format((float) $offer['price'], 2, ',', '.') }} ₺</span>
                                                · {{ $offer['seller'] ?? '?' }}
                                                <span class="text-slate-400">(skor {{ number_format((float) ($offer['score'] ?? 0), 2) }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if(count($scan->offers) > 3)
                                        <p class="text-xs text-slate-400 mt-1">+{{ count($scan->offers) - 3 }} teklif</p>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="align-top text-right">
                                <div class="inline-flex flex-wrap justify-end gap-1.5 max-w-[240px]">
                                    <form method="post" action="{{ route('admin.competitor-pricing.market.scan', $product) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-secondary text-xs py-1.5 px-2.5" @if(! $dataforseoReady) disabled @endif>Tara</button>
                                    </form>
                                    @if($scan && in_array($scan->status, ['pending_review', 'rejected', 'no_results'], true) && $scan->google_min_price)
                                        <form method="post" action="{{ route('admin.competitor-pricing.market.approve', $scan) }}">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-secondary text-xs py-1.5 px-2.5">Onayla</button>
                                        </form>
                                    @endif
                                    @if($scan && $scan->status !== 'rejected')
                                        <form method="post" action="{{ route('admin.competitor-pricing.market.reject', $scan) }}">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-secondary text-xs py-1.5 px-2.5">Reddet</button>
                                        </form>
                                    @endif
                                    @if(($suggestion['can_apply'] ?? false) && $scan)
                                        <form method="post" action="{{ route('admin.competitor-pricing.market.apply', $scan) }}" onsubmit="return confirm('Önerilen fiyat uygulansın mı?')">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-primary text-xs py-1.5 px-2.5">Uygula</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-slate-500 py-10">Ürün bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
