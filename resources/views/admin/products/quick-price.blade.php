@extends('layouts.admin')
@section('title', 'Hızlı fiyat')

@section('content')
    <x-admin.page-header
        title="Hızlı fiyat"
        subtitle="Ürünü bul → indirimli fiyatı yaz → Kaydet. Normal fiyat otomatik %10 üste eklenir."
    >
        <x-slot:actions>
            <a href="{{ route('admin.products.index') }}" class="admin-btn admin-btn-secondary">Ürün listesi</a>
            <a href="{{ route('admin.products.bulk-update') }}" class="admin-btn admin-btn-secondary">Toplu / CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="get" class="admin-card p-4 sm:p-5 mb-5" data-quick-price-search>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-2">
                <label class="admin-label" for="quick-price-q">Ürün adı / model / SKU</label>
                <input
                    id="quick-price-q"
                    name="q"
                    value="{{ $q }}"
                    class="admin-input text-base"
                    placeholder="Örn. SDF15, Pedrollo TOP, WNP-10H"
                    autocomplete="off"
                    autofocus
                >
            </div>
            <div>
                <label class="admin-label" for="quick-price-brand">Marka</label>
                <select id="quick-price-brand" name="brand_id" class="admin-input">
                    <option value="">Tümü</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) $brandId === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="admin-btn admin-btn-primary px-5 py-2.5 w-full sm:w-auto">Bul</button>
                <a href="{{ route('admin.products.quick-price') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Temizle</a>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">Enter ile ara · İndirimli fiyat kutusunda Enter ile kaydet · Normal fiyat = indirimli × {{ number_format(($listMarkup - 1) * 100, 0) }}%</p>
    </form>

    <div
        class="admin-card overflow-hidden"
        data-quick-price-root
        data-update-template="{{ $updateUrlTemplate }}"
        data-list-markup="{{ $listMarkup }}"
    >
        @if(! $searched)
            <p class="p-8 text-center text-slate-500">Ürün adı veya model kodunu yazıp <strong>Bul</strong> deyin.</p>
        @elseif($products->isEmpty())
            <p class="p-8 text-center text-slate-500">“{{ $q }}” için ürün bulunamadı. Model / SKU’yu kontrol edin.</p>
        @else
            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-600">{{ $products->count() }} ürün</p>
                <p class="text-xs text-slate-500" data-quick-price-status aria-live="polite"></p>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table admin-table--stack min-w-[640px]">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>SKU</th>
                            <th>İndirimli fiyat (₺)</th>
                            <th>Normal (+%10)</th>
                            <th>Maliyet (₺)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $sale = (float) $product->price;
                                $listPreview = round($sale * $listMarkup, 2);
                            @endphp
                            <tr data-product-row data-product-id="{{ $product->id }}">
                                <td data-label="Ürün" class="max-w-[280px]">
                                    <p class="font-semibold text-slate-900 leading-snug">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ $product->brand?->name ?: 'Markasız' }}
                                        ·
                                        @if($product->is_active)
                                            <span class="text-emerald-700 font-semibold">Aktif</span>
                                        @else
                                            <span class="text-slate-500">Pasif</span>
                                        @endif
                                    </p>
                                </td>
                                <td data-label="SKU" class="font-mono text-xs text-slate-500 whitespace-nowrap">{{ $product->sku ?: '—' }}</td>
                                <td data-label="İndirimli">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        inputmode="decimal"
                                        class="admin-input font-semibold tabular-nums text-base"
                                        data-field="price"
                                        value="{{ number_format($sale, 2, '.', '') }}"
                                        aria-label="İndirimli satış fiyatı"
                                    >
                                </td>
                                <td data-label="Normal">
                                    <p class="font-semibold tabular-nums text-slate-700 whitespace-nowrap" data-list-preview>
                                        {{ number_format($listPreview, 2, ',', '.') }} ₺
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5">üstü çizili fiyat</p>
                                </td>
                                <td data-label="Maliyet">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        class="admin-input tabular-nums"
                                        data-field="cost_price"
                                        value="{{ $product->cost_price !== null ? number_format((float) $product->cost_price, 2, '.', '') : '' }}"
                                        placeholder="opsiyonel"
                                        aria-label="Maliyet"
                                    >
                                </td>
                                <td data-label="İşlem" class="text-right">
                                    <button type="button" class="admin-btn admin-btn-primary text-sm py-2 px-4" data-save-price>Kaydet</button>
                                    <p class="text-xs mt-1 min-h-[1rem]" data-row-msg></p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            (function () {
                const root = document.querySelector('[data-quick-price-root]');
                if (!root) return;

                const template = root.getAttribute('data-update-template') || '';
                const markup = parseFloat(root.getAttribute('data-list-markup') || '1.1') || 1.1;
                const statusEl = root.querySelector('[data-quick-price-status]');
                const searchInput = document.getElementById('quick-price-q');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                function formatTry(value) {
                    return new Intl.NumberFormat('tr-TR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(value) + ' ₺';
                }

                function updateListPreview(row) {
                    const raw = row.querySelector('[data-field="price"]')?.value ?? '';
                    const preview = row.querySelector('[data-list-preview]');
                    if (!preview) return;
                    const num = parseFloat(String(raw).replace(',', '.'));
                    if (!Number.isFinite(num) || num <= 0) {
                        preview.textContent = '—';
                        return;
                    }
                    preview.textContent = formatTry(Math.round(num * markup * 100) / 100);
                }

                function setStatus(text, isError) {
                    if (!statusEl) return;
                    statusEl.textContent = text || '';
                    statusEl.className = 'text-xs ' + (isError ? 'text-red-600 font-semibold' : 'text-emerald-700 font-semibold');
                }

                async function saveRow(row) {
                    const id = row.getAttribute('data-product-id');
                    const msg = row.querySelector('[data-row-msg]');
                    const btn = row.querySelector('[data-save-price]');
                    const priceInput = row.querySelector('[data-field="price"]');
                    const costInput = row.querySelector('[data-field="cost_price"]');
                    const url = template.replace('__ID__', id);
                    const price = priceInput?.value ?? '';
                    const cost = costInput?.value ?? '';

                    if (price === '') {
                        if (msg) {
                            msg.textContent = 'İndirimli fiyat yazın.';
                            msg.className = 'text-xs mt-1 min-h-[1rem] text-red-600';
                        }
                        priceInput?.focus();
                        return;
                    }

                    if (btn) btn.disabled = true;
                    if (msg) {
                        msg.textContent = 'Kaydediliyor…';
                        msg.className = 'text-xs mt-1 min-h-[1rem] text-slate-500';
                    }

                    try {
                        const res = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                price: price,
                                cost_price: cost === '' ? null : cost,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            throw new Error(data.message || 'Kayıt başarısız.');
                        }

                        if (data.compare_at_price != null) {
                            const preview = row.querySelector('[data-list-preview]');
                            if (preview) preview.textContent = formatTry(data.compare_at_price);
                        }
                        if (msg) {
                            msg.textContent = data.message || 'Kaydedildi.';
                            msg.className = 'text-xs mt-1 min-h-[1rem] text-emerald-700 font-semibold';
                        }
                        setStatus(
                            (data.price_formatted || '') +
                            (data.compare_formatted ? ' → normal ' + data.compare_formatted : '') +
                            ' · kaydedildi',
                            false
                        );
                        row.classList.add('bg-emerald-50/60');
                        window.setTimeout(() => row.classList.remove('bg-emerald-50/60'), 900);
                        if (searchInput) {
                            searchInput.value = '';
                            searchInput.focus();
                        }
                    } catch (err) {
                        const text = err?.message || 'Kayıt başarısız.';
                        if (msg) {
                            msg.textContent = text;
                            msg.className = 'text-xs mt-1 min-h-[1rem] text-red-600';
                        }
                        setStatus(text, true);
                    } finally {
                        if (btn) btn.disabled = false;
                    }
                }

                root.addEventListener('input', function (event) {
                    const input = event.target.closest('[data-field="price"]');
                    if (!input) return;
                    const row = input.closest('[data-product-row]');
                    if (row) updateListPreview(row);
                });

                root.addEventListener('click', function (event) {
                    const btn = event.target.closest('[data-save-price]');
                    if (!btn) return;
                    const row = btn.closest('[data-product-row]');
                    if (row) saveRow(row);
                });

                root.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') return;
                    const input = event.target.closest('input[data-field]');
                    if (!input) return;
                    event.preventDefault();
                    const row = input.closest('[data-product-row]');
                    if (row) saveRow(row);
                });
            })();
        </script>
    @endpush
@endsection
