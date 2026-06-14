@extends('layouts.admin')
@section('title', 'Katalog hazırlığı')

@section('content')
    <x-admin.page-header title="Pazaryeri katalog hazırlığı" subtitle="Gönderim öncesi zorunlu alan kontrolü">
        <x-slot:actions>
            <a href="{{ route('admin.integrations.marketplace.logistics-import') }}" class="admin-btn admin-btn-secondary">Barkod CSV import</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="marketplace-readiness" />

    <form method="get" class="admin-card p-4 sm:p-5 mb-5">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="admin-label">Arama</label>
                <input name="q" value="{{ request('q') }}" class="admin-input" placeholder="Ürün adı, SKU, barkod">
            </div>
            <div>
                <label class="admin-label">Filtre</label>
                <select name="filter" class="admin-input">
                    <option value="all" @selected($filter === 'all')>Tümü</option>
                    <option value="missing_barcode" @selected($filter === 'missing_barcode')>Barkod eksik</option>
                    <option value="missing_brand" @selected($filter === 'missing_brand')>Marka eksik</option>
                    <option value="missing_category" @selected($filter === 'missing_category')>Kategori eksik</option>
                    <option value="missing_image" @selected($filter === 'missing_image')>Görsel eksik</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="admin-btn admin-btn-primary px-5 py-2.5 w-full md:w-auto">Filtrele</button>
            </div>
        </div>
    </form>

    <div class="admin-card overflow-hidden">
        <table class="admin-table admin-table--stack">
            <thead>
                <tr>
                    <th>Ürün</th>
                    <th>SKU</th>
                    <th>Barkod</th>
                    <th>Hazırlık</th>
                    <th>Eksikler</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php $product = $row['product']; @endphp
                    <tr>
                        <td data-label="Ürün" class="max-w-xs">
                            <p class="font-semibold text-slate-900 line-clamp-2">{{ $product->name }}</p>
                        </td>
                        <td data-label="SKU" class="font-mono text-xs">{{ $product->sku ?: '—' }}</td>
                        <td data-label="Barkod" class="font-mono text-xs">{{ $product->barcode ?: '—' }}</td>
                        <td data-label="Hazırlık">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $row['ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                %{{ $row['score'] }}
                            </span>
                        </td>
                        <td data-label="Eksikler" class="text-xs text-slate-600">
                            {{ $row['missing'] === [] ? '—' : implode(', ', $row['missing']) }}
                        </td>
                        <td data-label="İşlem" class="text-right">
                            <a href="{{ route('admin.products.edit', $product) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-8">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
