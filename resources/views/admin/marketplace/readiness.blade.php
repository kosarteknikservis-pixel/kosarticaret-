@extends('layouts.admin')
@section('title', 'Katalog hazırlığı')

@section('content')
    <x-admin.page-header title="Pazaryeri katalog hazırlığı" subtitle="Gönderim öncesi zorunlu alan kontrolü">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.integrations.marketplace.logistics-import')">Barkod CSV import</x-admin.button>
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
                <x-admin.button variant="primary" type="submit" class="w-full md:w-auto">Filtrele</x-admin.button>
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
                            <x-admin.badge :tone="$row['ready'] ? 'success' : 'warning'">%{{ $row['score'] }}</x-admin.badge>
                        </td>
                        <td data-label="Eksikler" class="text-xs text-slate-600">
                            {{ $row['missing'] === [] ? '—' : implode(', ', $row['missing']) }}
                        </td>
                        <td data-label="İşlem" class="text-right">
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.products.edit', $product)">Düzenle</x-admin.button>
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
