@extends('layouts.admin')
@section('title', 'Ürünler')

@section('content')
    <x-admin.page-header title="Ürünler" subtitle="Katalogdaki tüm ürünler">
        <x-slot:actions>
            <a href="{{ route('admin.products.bulk-update') }}" class="admin-btn admin-btn-secondary">Toplu güncelleme</a>
            <a href="{{ route('admin.products.create') }}" class="admin-btn admin-btn-primary">+ Yeni ürün</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="get" class="admin-card p-4 sm:p-5 mb-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            <div class="sm:col-span-2 lg:col-span-2 xl:col-span-2">
                <label class="admin-label">Arama</label>
                <input name="q" value="{{ $filters['q'] }}" class="admin-input" placeholder="Ürün adı, SKU veya slug">
            </div>
            <div>
                <label class="admin-label">Marka</label>
                <select name="brand_id" class="admin-input">
                    <option value="">Tümü</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) $filters['brand_id'] === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-label">Kategori</label>
                <select name="category_id" class="admin-input">
                    <option value="">Tümü</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-label">Stok</label>
                <select name="stock" class="admin-input">
                    <option value="">Tümü</option>
                    <option value="in_stock" @selected($filters['stock'] === 'in_stock')>Stokta var</option>
                    <option value="out_of_stock" @selected($filters['stock'] === 'out_of_stock')>Tükendi</option>
                    <option value="low" @selected($filters['stock'] === 'low')>Düşük stok (1–5)</option>
                </select>
            </div>
            <div>
                <label class="admin-label">Yayın</label>
                <select name="is_active" class="admin-input">
                    <option value="">Tümü</option>
                    <option value="yes" @selected($filters['is_active'] === 'yes')>Aktif</option>
                    <option value="no" @selected($filters['is_active'] === 'no')>Pasif</option>
                </select>
            </div>
            <div>
                <label class="admin-label">Vitrin</label>
                <select name="featured" class="admin-input">
                    <option value="">Tümü</option>
                    <option value="yes" @selected($filters['featured'] === 'yes')>Vitrinde</option>
                    <option value="no" @selected($filters['featured'] === 'no')>Vitrinde değil</option>
                </select>
            </div>
        </div>

        <details class="mt-3 group">
            <summary class="cursor-pointer text-sm font-semibold text-slate-600 hover:text-slate-900 select-none">Gelişmiş filtreler</summary>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="admin-label">SKU listesi</label>
                    <input name="sku_list" value="{{ $filters['sku_list'] }}" class="admin-input font-mono text-sm" placeholder="wnp70, abc123 (virgülle ayırın)">
                </div>
                <div>
                    <label class="admin-label">Sıralama</label>
                    <select name="sort" class="admin-input">
                        <option value="latest" @selected($filters['sort'] === 'latest')>En yeni</option>
                        <option value="name_asc" @selected($filters['sort'] === 'name_asc')>Ad (A→Z)</option>
                        <option value="name_desc" @selected($filters['sort'] === 'name_desc')>Ad (Z→A)</option>
                        <option value="price_asc" @selected($filters['sort'] === 'price_asc')>Fiyat (artan)</option>
                        <option value="price_desc" @selected($filters['sort'] === 'price_desc')>Fiyat (azalan)</option>
                        <option value="stock_asc" @selected($filters['sort'] === 'stock_asc')>Stok (artan)</option>
                        <option value="stock_desc" @selected($filters['sort'] === 'stock_desc')>Stok (azalan)</option>
                    </select>
                </div>
                <div>
                    <label class="admin-label">Sayfa başına</label>
                    <select name="per_page" class="admin-input">
                        @foreach(['20', '50', '100'] as $size)
                            <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }} ürün</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </details>

        <div class="mt-4 flex flex-wrap gap-2 items-center">
            <button type="submit" class="admin-btn admin-btn-primary px-5 py-2.5">Filtrele</button>
            <a href="{{ route('admin.products.index') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Temizle</a>
            @if($products->total() > 0)
                <span class="text-sm text-slate-500 ml-auto">{{ number_format($products->total(), 0, ',', '.') }} ürün eşleşti</span>
            @endif
        </div>
    </form>

    <div class="admin-card overflow-hidden">
        @if($products->isEmpty())
            <p class="p-8 text-center text-slate-500">Filtrelere uygun ürün bulunamadı.</p>
        @else
            <div class="admin-table-toolbar px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50">
                <p class="text-sm text-slate-600">
                    Sayfa {{ $products->currentPage() }}/{{ $products->lastPage() }} ·
                    {{ $products->firstItem() }}–{{ $products->lastItem() }} arası gösteriliyor
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.products.export', request()->query()) }}" class="admin-btn admin-btn-secondary text-sm px-4 py-2">
                        Filtrelenmiş listeyi indir
                    </a>
                    <button type="button" id="export-selected-products" data-export-base="{{ route('admin.products.export', request()->except('ids')) }}" class="admin-btn admin-btn-secondary text-sm px-4 py-2">
                        Seçilenleri indir
                    </button>
                </div>
            </div>

            <table class="admin-table admin-table--stack">
                <thead>
                    <tr>
                        <th><input type="checkbox" data-product-check-all aria-label="Tümünü seç"></th>
                        <th>Ürün</th>
                        <th>SKU</th>
                        <th>Marka</th>
                        <th>Fiyat</th>
                        <th>Stok</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $p)
                        <tr>
                            <td data-label="Seç">
                                <input type="checkbox" value="{{ $p->id }}" data-product-check aria-label="{{ $p->sku }} seç">
                            </td>
                            <td data-label="Ürün" class="max-w-[280px]">
                                <p class="font-semibold text-slate-900 line-clamp-2">{{ $p->name }}</p>
                                @if($p->categories->isNotEmpty())
                                    <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $p->categories->pluck('name')->implode(', ') }}</p>
                                @endif
                            </td>
                            <td data-label="SKU" class="font-mono text-xs text-slate-500">{{ $p->sku ?: '—' }}</td>
                            <td data-label="Marka" class="text-sm text-slate-600">{{ $p->brand?->name ?: '—' }}</td>
                            <td data-label="Fiyat" class="font-semibold whitespace-nowrap">{{ number_format($p->price, 2, ',', '.') }} ₺</td>
                            <td data-label="Stok">
                                @if($p->stock <= 0)
                                    <span class="text-red-600 font-bold">0</span>
                                @elseif($p->stock <= 5)
                                    <span class="text-amber-600 font-bold">{{ $p->stock }}</span>
                                @else
                                    {{ $p->stock }}
                                @endif
                            </td>
                            <td data-label="Durum">
                                <div class="flex flex-wrap gap-1">
                                    @if($p->is_active)
                                        <span class="rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 text-slate-600 px-2.5 py-0.5 text-xs font-semibold">Pasif</span>
                                    @endif
                                    @if($p->featured)
                                        <span class="rounded-full bg-blue-50 text-blue-700 px-2.5 py-0.5 text-xs font-semibold">Vitrin</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="İşlemler" class="text-right">
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.products.export', array_merge(request()->query(), ['ids' => [$p->id]])) }}" class="admin-btn admin-btn-secondary text-xs py-1.5" title="CSV indir">CSV</a>
                                    <a href="{{ route('admin.products.edit', $p) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($products->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $products->links() }}</div>
            @endif
        @endif
    </div>

    @push('scripts')
        <script>
            document.querySelector('[data-product-check-all]')?.addEventListener('change', function () {
                document.querySelectorAll('[data-product-check]').forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });
            });

            document.getElementById('export-selected-products')?.addEventListener('click', function () {
                const ids = [...document.querySelectorAll('[data-product-check]:checked')].map((checkbox) => checkbox.value);
                if (ids.length === 0) {
                    alert('İndirmek için en az bir ürün seçin.');
                    return;
                }

                const url = new URL(this.dataset.exportBase, window.location.origin);
                ids.forEach((id) => url.searchParams.append('ids[]', id));
                window.location.href = url.toString();
            });
        </script>
    @endpush
@endsection
