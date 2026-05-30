@extends('layouts.admin')
@section('title', $banner->exists ? 'Vitrin öğesi düzenle' : 'Yeni vitrin öğesi')

@section('content')
    <x-admin.page-header :title="$banner->exists ? 'Vitrin öğesi düzenle' : 'Yeni vitrin öğesi'" />
    <form method="post"
          action="{{ $banner->exists ? route('admin.home-banners.update', $banner) : route('admin.home-banners.store') }}"
          enctype="multipart/form-data"
          class="max-w-3xl space-y-6"
          id="banner-form">
        @csrf
        @if($banner->exists) @method('PUT') @endif

        <div class="admin-card p-6 space-y-4">
            <h3 class="admin-section-title" style="margin-top:0">Tür</h3>
            <div>
                <label class="admin-label">Ne ekliyorsunuz?</label>
                <select name="type" id="banner-type" class="admin-input max-w-md">
                    @foreach(\App\Models\HomeBanner::TYPES as $t)
                        <option value="{{ $t }}" @selected(old('type', $banner->type ?? 'slider') === $t)>
                            @lang('shop.banner_type_'.$t)
                        </option>
                    @endforeach
                </select>
            </div>
            <ul class="text-xs text-slate-600 space-y-1 list-disc pl-5">
                <li data-type-hint="slider"><strong>Slider:</strong> üstte dönen büyük slayt (özel görsel + isteğe link)</li>
                <li data-type-hint="banner"><strong>Banner:</strong> slider altında geniş kampanya kutusu</li>
                <li data-type-hint="category"><strong>Kategori:</strong> tıklanınca kategori sayfasına gider (görsel: kategori veya özel)</li>
                <li data-type-hint="product"><strong>Ürün:</strong> tıklanınca ürün sayfasına gider (görsel: ürün veya özel)</li>
            </ul>
        </div>

        <div class="admin-card p-6 space-y-4" id="panel-product" hidden>
            <h3 class="admin-section-title" style="margin-top:0">Ürün bağlantısı</h3>
            <div>
                <label class="admin-label">Ürün seçin</label>
                <select name="product_id" class="admin-input">
                    <option value="">— Seçin —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(old('product_id', $banner->product_id) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Vitrinde tıklanınca bu ürünün sayfası açılır. Görsel yüklemezseniz ürün görseli kullanılır.</p>
            </div>
        </div>

        <div class="admin-card p-6 space-y-4" id="panel-category" hidden>
            <h3 class="admin-section-title" style="margin-top:0">Kategori bağlantısı</h3>
            <div>
                <label class="admin-label">Kategori seçin</label>
                <select name="category_id" class="admin-input">
                    <option value="">— Seçin —</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $banner->category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Tıklanınca kategori sayfasına gider. Görsel yüklemezseniz kategori görseli kullanılır.</p>
            </div>
        </div>

        <div class="admin-card p-6 space-y-4" id="panel-image">
            <h3 class="admin-section-title" style="margin-top:0">Görsel</h3>
            @foreach(\App\Models\HomeBanner::TYPES as $t)
                <template id="admin-spec-tpl-{{ $t }}">
                    <x-admin.image-spec :bannerType="$t" />
                </template>
            @endforeach
            <div id="form-image-spec-slot">
                <x-admin.image-spec :bannerType="old('type', $banner->type ?? 'slider')" />
            </div>
            <div id="banner-dropzone"
                 class="admin-dropzone {{ $banner->imageUrl() ? 'admin-dropzone--has-preview' : '' }}">
                <input type="file" name="image_file" id="banner-image-input" accept="image/jpeg,image/png,image/webp" class="sr-only">
                <div class="admin-dropzone__inner" id="banner-dropzone-inner">
                    @if($banner->imageUrl())
                        <img src="{{ $banner->imageUrl() }}" alt="" id="banner-preview-img" class="admin-dropzone__preview">
                    @else
                        <div class="admin-dropzone__placeholder" id="banner-dropzone-placeholder">
                            <svg class="w-12 h-12 text-teal-600/60 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            <p class="mt-3 font-semibold text-slate-800">Görseli sürükleyin veya seçin</p>
                        </div>
                    @endif
                </div>
            </div>
            @if($banner->image)
                <label class="admin-checkbox text-sm"><input type="checkbox" name="remove_image" value="1"> Özel görseli kaldır (ürün/kategori görseli kullanılır)</label>
            @endif
            @error('image_file')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="admin-card p-6 sm:p-8 space-y-4" id="panel-text">
            <h3 class="admin-section-title" style="margin-top:0">Metin & SEO</h3>
            <div><label class="admin-label">Başlık</label><input name="title" value="{{ old('title', $banner->title) }}" class="admin-input" placeholder="Boş bırakırsanız ürün/kategori adı kullanılır"></div>
            <div><label class="admin-label">Alt başlık</label><textarea name="subtitle" rows="2" class="admin-input">{{ old('subtitle', $banner->subtitle) }}</textarea></div>
            <div><label class="admin-label">Görsel alt metni</label><input name="image_alt" value="{{ old('image_alt', $banner->image_alt) }}" class="admin-input"></div>
            <div class="grid sm:grid-cols-2 gap-4" id="panel-link-fields">
                <div><label class="admin-label">Buton metni</label><input name="cta_text" value="{{ old('cta_text', $banner->cta_text) }}" class="admin-input" placeholder="Keşfet"></div>
                <div><label class="admin-label">Link (slider / banner)</label><input name="link_url" value="{{ old('link_url', $banner->link_url) }}" class="admin-input" placeholder="/urunler"></div>
            </div>
            <div><label class="admin-label">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="admin-input max-w-xs"></div>
            <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $banner->active ?? true))> Yayında</label>
        </div>

        <x-admin.form-footer :delete-action="$banner->exists ? route('admin.home-banners.destroy', $banner) : null" />
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-banners.js') }}" defer></script>
@endpush
