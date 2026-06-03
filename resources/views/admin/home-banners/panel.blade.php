@php
    $panelType = old('type', $banner->type ?? 'slider');
    $panelSource = old('product_source', $banner->product_source ?? 'latest');
    $currentRowId = (int) old('home_row_id', $banner->home_row_id);
    $currentColIndex = (int) old('col_index', $banner->col_index ?? 0);
    $currentRow = $rows->firstWhere('id', $currentRowId);
    $currentSpan = (int) ($currentRow?->columns[$currentColIndex] ?? $banner->columnSpan());
    $bannerImageSpecKey = $currentSpan >= 8 ? 'home_banner_slider' : 'home_banner_tile';
@endphp
<div class="hp-panel-form">
    <div class="flex items-center justify-between gap-2 mb-4">
        <h3 class="font-bold text-slate-900">{{ $isNew ? 'Yeni blok' : 'Blok düzenle' }}</h3>
        <button type="button" class="text-slate-400 hover:text-slate-700 text-xl leading-none" id="panel-close" aria-label="Kapat">&times;</button>
    </div>

    <form method="post"
          action="{{ $isNew ? route('admin.home-banners.store') : route('admin.home-banners.update', $banner) }}"
          enctype="multipart/form-data"
          class="space-y-3 text-sm">
        @csrf
        @if(!$isNew) @method('PUT') @endif
        <input type="hidden" name="from_builder" value="1">
        <input type="hidden" name="home_row_id" value="{{ old('home_row_id', $banner->home_row_id) }}">
        <input type="hidden" name="col_index" value="{{ old('col_index', $banner->col_index ?? 0) }}">

        <div>
            <label class="admin-label">Tür</label>
            <select name="type" id="panel-banner-type" class="admin-input">
                @foreach(\App\Models\HomeBanner::TYPES as $t)
                    <option value="{{ $t }}" @selected(old('type', $banner->type) === $t)>@lang('shop.banner_type_'.$t)</option>
                @endforeach
            </select>
        </div>

        <div id="panel-product" class="space-y-1" @if($panelType !== 'product') hidden @endif>
            <label class="admin-label">Tek ürün (kutu)</label>
            <select name="product_id" class="admin-input">
                <option value="">—</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" @selected(old('product_id', $banner->product_id) == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500">Tek ürün vitrin kutusu — çoklu liste için “Ürün listesi” türünü seçin.</p>
        </div>

        <div id="panel-product-list" class="space-y-3 rounded-xl border-2 border-teal-200 bg-teal-50 p-3" @if($panelType !== 'product_list') hidden @endif>
            <p class="text-sm font-bold text-teal-900">Ürün kaynağı</p>
            <p class="text-xs text-teal-800/80 -mt-1 mb-2">Kategori, marka veya tek tek ürün seçimi buradan yapılır.</p>

            <div>
                <label class="admin-label">Kaynak</label>
                <select name="product_source" id="panel-product-source" class="admin-input">
                    @foreach($productSources as $src)
                        <option value="{{ $src }}" @selected(old('product_source', $banner->product_source ?? 'latest') === $src)>@lang('shop.product_list_source_'.$src)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-label">Gösterilecek adet</label>
                <input type="number" name="product_limit" min="1" max="24" value="{{ old('product_limit', $banner->product_limit ?? 4) }}" class="admin-input max-w-[6rem]">
                @if($listPreviewCount !== null)
                    <p class="text-xs text-teal-800 mt-1">
                        Bu ayarlarla vitrinde şu an <strong>{{ $listPreviewCount }}</strong> ürün görünür.
                        @if($listPreviewCount < (int) old('product_limit', $banner->product_limit ?? 4))
                            (Kategoride/markada yeterli ürün yoksa limit kadar doldurulamaz.)
                        @endif
                    </p>
                @endif
            </div>
            <div id="panel-list-brand" class="space-y-1" @if($panelType !== 'product_list' || $panelSource !== 'brand') hidden @endif>
                <label class="admin-label">Marka</label>
                <select name="brand_id" class="admin-input">
                    <option value="">—</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" @selected(old('brand_id', $banner->brand_id) == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="panel-list-manual" class="space-y-2" @if($panelType !== 'product_list' || $panelSource !== 'manual') hidden @endif>
                <label class="admin-label">Ürünleri seç</label>
                @php
                    $selectedIds = collect(old('product_ids', $banner->product_ids ?? []))
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->values();
                    $selectedProducts = $selectedIds
                        ->map(fn ($id) => $products->firstWhere('id', $id))
                        ->filter();
                @endphp
                <select class="admin-input font-mono text-xs" multiple size="8" data-product-picker-select>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected($selectedIds->contains((int) $p->id))>{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-teal-800/80">Seçilen ürünler aşağıda görünür. Vitrindeki sıralamayı değiştirmek için ürün kartlarını sürükleyin.</p>
                <div class="hp-product-sort-list" data-product-sort-list>
                    @foreach($selectedProducts as $selectedProduct)
                        <div class="hp-product-sort-item" data-product-id="{{ $selectedProduct->id }}">
                            <span class="hp-product-sort-item__handle" aria-hidden="true">⋮⋮</span>
                            <span class="hp-product-sort-item__name">{{ $selectedProduct->name }}</span>
                            <button type="button" class="hp-product-sort-item__remove" data-product-remove aria-label="Ürünü listeden çıkar">×</button>
                            <input type="hidden" name="product_ids[]" value="{{ $selectedProduct->id }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="panel-category" class="space-y-1" @if(! in_array($panelType, ['category', 'product_list'], true) || ($panelType === 'product_list' && $panelSource !== 'category')) hidden @endif>
            <label class="admin-label" id="panel-category-label">{{ $panelType === 'product_list' ? 'Hangi kategori?' : 'Kategori (kutu)' }}</label>

            <select name="category_id" class="admin-input" id="panel-category-select">
                <option value="">—</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $banner->category_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div id="panel-image-block" @if($panelType === 'product_list') hidden @endif>
            <label class="admin-label">Görsel @if($isNew && ! in_array($panelType, ['product_list'], true))<span class="text-red-600">*</span>@endif</label>

            @foreach(\App\Models\HomeBanner::TYPES as $t)
                <template id="admin-spec-tpl-{{ $t }}">
                    @if($t === \App\Models\HomeBanner::TYPE_BANNER)
                        <x-admin.image-spec :key="$bannerImageSpecKey" />
                    @else
                        <x-admin.image-spec :bannerType="$t" />
                    @endif
                </template>
            @endforeach
            <div id="panel-image-spec-slot">
                @if(old('type', $banner->type ?? 'slider') === \App\Models\HomeBanner::TYPE_BANNER)
                    <x-admin.image-spec :key="$bannerImageSpecKey" />
                @else
                    <x-admin.image-spec :bannerType="old('type', $banner->type ?? 'slider')" />
                @endif
            </div>
            @if($banner->imageUrl())
                <div class="rounded-xl border border-slate-200 bg-white p-2 space-y-2 mb-2">
                    <img src="{{ $banner->imageUrl() }}" alt="" class="w-full max-h-28 object-contain rounded-lg bg-slate-50">
                    @if($banner->image)
                        <button type="submit"
                                name="remove_image"
                                value="1"
                                class="admin-btn admin-btn-danger w-full text-xs py-1.5"
                                onclick="return confirm('Bu bloğun görseli silinsin mi?');">
                            Görseli sil
                        </button>
                    @endif
                </div>
            @endif
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="admin-input text-xs">
        </div>

        <div>
            <label class="admin-label">Başlık</label>
            <input name="title" value="{{ old('title', $banner->title) }}" class="admin-input">
        </div>
        <div>
            <label class="admin-label">Alt başlık</label>
            <textarea name="subtitle" rows="2" class="admin-input">{{ old('subtitle', $banner->subtitle) }}</textarea>
        </div>
        <div>
            <label class="admin-label">Alt metin (SEO)</label>
            <input name="image_alt" value="{{ old('image_alt', $banner->image_alt) }}" class="admin-input">
        </div>

        <div id="panel-link-fields" class="grid grid-cols-1 gap-2" @if(in_array($panelType, ['product', 'category', 'product_list'], true)) hidden @endif>
            <div>
                <label class="admin-label">Buton</label>
                <input name="cta_text" value="{{ old('cta_text', $banner->cta_text) }}" class="admin-input">
            </div>
            <div>
                <label class="admin-label">Link</label>
                <input name="link_url" value="{{ old('link_url', $banner->link_url) }}" class="admin-input" placeholder="/urunler">
            </div>
        </div>

        <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $banner->active ?? true))> Yayında</label>

        <button type="submit" class="admin-btn admin-btn-primary w-full py-2.5">Kaydet</button>
    </form>

    @if(!$isNew)
        <form method="post" action="{{ route('admin.home-banners.destroy', $banner) }}" class="mt-4 pt-4 border-t border-red-100" onsubmit="return confirm('Bu blok silinsin mi?');">
            @csrf @method('DELETE')
            <input type="hidden" name="from_builder" value="1">
            <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">Bloğu sil</button>
        </form>
    @endif
</div>
