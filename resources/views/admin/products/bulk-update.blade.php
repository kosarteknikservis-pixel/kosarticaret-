@extends('layouts.admin')
@section('title', 'Toplu ürün güncelleme')

@section('content')
    @php $activeTab = request('tab', 'panel'); @endphp

    <x-admin.page-header
        title="Toplu ürün güncelleme"
        subtitle="Kategori, marka veya SKU ile filtreleyin; fiyat, stok, kategori ve SEO alanlarını tek seferde güncelleyin"
    />

    <nav class="bulk-tabs" role="tablist">
        <a href="{{ route('admin.products.bulk-update', ['tab' => 'panel']) }}"
           class="bulk-tabs__tab {{ $activeTab === 'panel' ? 'is-active' : '' }}"
           role="tab">Panel ile güncelle</a>
        <a href="{{ route('admin.products.bulk-update', ['tab' => 'csv']) }}"
           class="bulk-tabs__tab {{ $activeTab === 'csv' ? 'is-active' : '' }}"
           role="tab">CSV (SKU satırı)</a>
    </nav>

    @if($activeTab === 'csv')
        <div class="admin-card p-6 sm:p-8 max-w-2xl">
            <h3 class="admin-section-title" style="margin-top:0">SKU listesi ile güncelle</h3>
            <p class="text-sm text-slate-600 mb-4">Her satır bir ürün (SKU zorunlu). Sütun adları Türkçe veya İngilizce olabilir.</p>
            <div class="bulk-csv-hint text-xs text-slate-600 mb-4 font-mono bg-slate-50 border border-slate-200 rounded-lg p-3">
                SKU,Fiyat,Stok,İndirimli,Marka,Kategoriler,Yayında,Öne çıkan,Etiketler<br>
                ABC-01,1250.00,50,1499.00,Sumak,Pompalar|Dalgıç,1,0,pompa,sulama
            </div>
            <form method="post" action="{{ route('admin.products.bulk-update.csv') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label">CSV dosyası</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800">
                </div>
                <label class="admin-checkbox font-medium text-slate-800">
                    <input type="checkbox" name="confirm" value="1" required>
                    CSV’deki SKU’lara ait ürünleri güncellemek istiyorum
                </label>
                <button type="submit" class="admin-btn admin-btn-primary">CSV’yi uygula</button>
            </form>
        </div>
    @else
        <form method="post" action="{{ route('admin.products.bulk-update.apply') }}" id="bulk-update-form" class="bulk-update-layout">
            @csrf

            <aside class="bulk-update-layout__filters admin-card p-5 sm:p-6">
                <h3 class="admin-section-title" style="margin-top:0">1. Hangi ürünler?</h3>
                <p class="text-xs text-slate-500 mb-4">Boş bırakırsanız tüm katalog. Alt kategoriler otomatik dahil edilir.</p>

                <div class="space-y-4">
                    <div>
                        <label class="admin-label">Kategori (çoklu)</label>
                        <select name="filter_category_ids[]" multiple size="6" class="admin-input" data-bulk-filter>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(collect(old('filter_category_ids', []))->contains($cat->id))>
                                    {{ $cat->parent ? $cat->parent->name.' → ' : '' }}{{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Marka</label>
                        <select name="filter_brand_id" class="admin-input" data-bulk-filter>
                            <option value="">Tüm markalar</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" @selected(old('filter_brand_id') == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="admin-label">Stok</label>
                            <select name="filter_stock" class="admin-input" data-bulk-filter>
                                <option value="any">Tümü</option>
                                <option value="in_stock">Stokta var</option>
                                <option value="out_of_stock">Tükendi (0)</option>
                                <option value="low">Düşük stok</option>
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">Düşük ≤</label>
                            <input type="number" name="filter_stock_low_max" value="{{ old('filter_stock_low_max', 5) }}" min="1" class="admin-input" data-bulk-filter>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="admin-label">Yayında</label>
                            <select name="filter_is_active" class="admin-input" data-bulk-filter>
                                <option value="any">Tümü</option>
                                <option value="yes">Yayında</option>
                                <option value="no">Kapalı</option>
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">Öne çıkan</label>
                            <select name="filter_featured" class="admin-input" data-bulk-filter>
                                <option value="any">Tümü</option>
                                <option value="yes">Evet</option>
                                <option value="no">Hayır</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="admin-label">Ad veya SKU ara</label>
                        <input type="search" name="filter_search" value="{{ old('filter_search') }}" class="admin-input" placeholder="Örn. pedrollo" data-bulk-filter>
                    </div>
                    <div>
                        <label class="admin-label">SKU listesi</label>
                        <textarea name="filter_sku_list" rows="3" class="admin-input font-mono text-xs" placeholder="SKU1, SKU2 veya satır satır" data-bulk-filter>{{ old('filter_sku_list') }}</textarea>
                    </div>
                </div>

                <div class="bulk-match-box" id="bulk-match-box" aria-live="polite">
                    <span class="bulk-match-box__count" id="bulk-match-count">—</span>
                    <span class="bulk-match-box__label">ürün eşleşecek</span>
                </div>
            </aside>

            <div class="bulk-update-layout__actions space-y-4">
                <div class="admin-card p-5 sm:p-6">
                    <h3 class="admin-section-title" style="margin-top:0">2. Ne değişecek?</h3>
                    <p class="text-xs text-slate-500 mb-4">Sadece işaretlediğiniz bölümler uygulanır.</p>

                    @include('admin.products.partials.bulk-action-price', ['prefix' => 'price', 'label' => 'Satış fiyatı (₺)'])
                    @include('admin.products.partials.bulk-action-price', ['prefix' => 'compare', 'label' => 'İndirimli / eski fiyat (₺)', 'allowClear' => true])

                    <details class="bulk-action-block" open>
                        <summary class="bulk-action-block__head">
                            <label class="admin-checkbox bulk-action-block__check" onclick="event.stopPropagation()">
                                <input type="checkbox" name="act_stock" value="1">
                            </label>
                            <span>Stok</span>
                        </summary>
                        <div class="bulk-action-block__body grid sm:grid-cols-2 gap-3">
                            <select name="stock_mode" class="admin-input">
                                <option value="set">Değere ayarla</option>
                                <option value="add">Ekle (+)</option>
                                <option value="subtract">Çıkar (−)</option>
                            </select>
                            <input type="number" name="stock_value" min="0" value="0" class="admin-input">
                        </div>
                    </details>

                    <details class="bulk-action-block">
                        <summary class="bulk-action-block__head">
                            <label class="admin-checkbox bulk-action-block__check" onclick="event.stopPropagation()">
                                <input type="checkbox" name="act_brand" value="1">
                            </label>
                            <span>Marka</span>
                        </summary>
                        <div class="bulk-action-block__body grid sm:grid-cols-2 gap-3">
                            <select name="brand_mode" class="admin-input">
                                <option value="set">Marka ata</option>
                                <option value="clear">Markayı kaldır</option>
                            </select>
                            <select name="brand_id" class="admin-input">
                                <option value="">— Seçin —</option>
                                @foreach($brands as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </details>

                    <details class="bulk-action-block">
                        <summary class="bulk-action-block__head">
                            <label class="admin-checkbox bulk-action-block__check" onclick="event.stopPropagation()">
                                <input type="checkbox" name="act_categories" value="1">
                            </label>
                            <span>Kategoriler</span>
                        </summary>
                        <div class="bulk-action-block__body space-y-3">
                            <select name="category_mode" class="admin-input">
                                <option value="add">Mevcutlara ekle</option>
                                <option value="remove">Seçilenleri çıkar</option>
                                <option value="sync">Tamamen değiştir (sadece seçilenler)</option>
                            </select>
                            <select name="category_ids[]" multiple size="5" class="admin-input">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->parent ? $cat->parent->name.' → ' : '' }}{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </details>

                    <details class="bulk-action-block">
                        <summary class="bulk-action-block__head">
                            <label class="admin-checkbox bulk-action-block__check" onclick="event.stopPropagation()">
                                <input type="checkbox" name="act_status" value="1">
                            </label>
                            <span>Durum</span>
                        </summary>
                        <div class="bulk-action-block__body grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="admin-label">Yayında</label>
                                <select name="status_is_active" class="admin-input">
                                    <option value="no_change">Değiştirme</option>
                                    <option value="enable">Yayına al</option>
                                    <option value="disable">Yayından kaldır</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label">Öne çıkan</label>
                                <select name="status_featured" class="admin-input">
                                    <option value="no_change">Değiştirme</option>
                                    <option value="enable">Öne çıkar</option>
                                    <option value="disable">Kaldır</option>
                                </select>
                            </div>
                        </div>
                    </details>

                    <details class="bulk-action-block">
                        <summary class="bulk-action-block__head">
                            <label class="admin-checkbox bulk-action-block__check" onclick="event.stopPropagation()">
                                <input type="checkbox" name="act_tags" value="1">
                            </label>
                            <span>Etiketler</span>
                        </summary>
                        <div class="bulk-action-block__body grid sm:grid-cols-2 gap-3">
                            <select name="tags_mode" class="admin-input">
                                <option value="set">Değiştir (virgülle)</option>
                                <option value="append">Sonuna ekle</option>
                                <option value="clear">Temizle</option>
                            </select>
                            <input type="text" name="tags_value" class="admin-input" placeholder="pompa, sulama">
                        </div>
                    </details>

                    @foreach([
                        'meta_title' => 'SEO başlık',
                        'meta_description' => 'SEO açıklama',
                        'image_alt' => 'Görsel alt metni',
                        'short_description' => 'Kısa açıklama',
                    ] as $field => $label)
                        <details class="bulk-action-block">
                            <summary class="bulk-action-block__head">
                                <label class="admin-checkbox bulk-action-block__check" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="act_{{ $field }}" value="1">
                                </label>
                                <span>{{ $label }}</span>
                            </summary>
                            <div class="bulk-action-block__body grid sm:grid-cols-2 gap-3">
                                <select name="{{ $field }}_mode" class="admin-input">
                                    <option value="set">Değiştir</option>
                                    <option value="append">Sonuna ekle</option>
                                    <option value="prepend">Başına ekle</option>
                                    <option value="clear">Temizle</option>
                                </select>
                                <input type="text" name="{{ $field }}_value" class="admin-input">
                            </div>
                        </details>
                    @endforeach
                </div>

                <div class="admin-card p-5 sm:p-6 bulk-confirm-card">
                    <h3 class="admin-section-title" style="margin-top:0">3. Uygula</h3>
                    <label class="admin-checkbox font-semibold text-slate-900 block mb-4">
                        <input type="checkbox" name="confirm" value="1" required>
                        Eşleşen tüm ürünlere yukarıdaki değişiklikleri uygulamak istiyorum
                    </label>
                    <button type="submit" class="admin-btn admin-btn-primary px-8">Toplu güncelle</button>
                </div>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        window.AdminBulkUpdate = {
            previewUrl: @json(route('admin.products.bulk-update.preview')),
        };
    </script>
    <script src="{{ asset('js/admin-bulk-products.js') }}" defer></script>
@endpush
