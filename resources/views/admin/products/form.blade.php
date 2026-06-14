@extends('layouts.admin')

@section('title', $product->exists ? 'Ürün düzenle' : 'Yeni ürün')



@section('content')

    <x-admin.page-header :title="$product->exists ? 'Ürün düzenle' : 'Yeni ürün'" subtitle="Katalog, fiyat, stok, SEO skoru ve zengin açıklama" />



    @php

        $seoScoreData = [

            'name' => old('name', $product->name),

            'slug' => old('slug', $product->slug),

            'sku' => old('sku', $product->sku),

            'meta_title' => old('meta_title', $product->meta_title),

            'meta_description' => old('meta_description', $product->meta_description),

            'short_description' => old('short_description', $product->short_description),

            'description' => old('description', $product->description),

            'tags' => old('tags', is_array($product->tags) ? implode(', ', $product->tags) : ''),

            'has_image' => (bool) $product->imageUrl(),

        ];

    @endphp



    <form method="post" enctype="multipart/form-data"

          action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"

          class="admin-form-with-seo lg:grid lg:grid-cols-[1fr_min(18rem,28%)] lg:gap-8 max-w-5xl"

          data-seo-has-image="{{ $product->imageUrl() ? '1' : '0' }}"

          data-ai-type="product" data-ai-entity="products" data-ai-id="{{ $product->id }}">

        @csrf

        @if($product->exists) @method('PUT') @endif



        <div class="admin-card p-6 sm:p-8 space-y-4">

            <h3 class="admin-section-title" style="margin-top:0">Temel bilgiler</h3>

            <div><label class="admin-label">Ad</label><input name="name" value="{{ old('name', $product->name) }}" required class="admin-input" data-seo-score-field="name"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div><label class="admin-label">SKU</label><input name="sku" value="{{ old('sku', $product->sku) }}" class="admin-input font-mono text-sm" data-seo-score-field="sku"></div>

                <x-admin.slug-field :slug="old('slug', $product->slug)" entity="products" :entity-id="$product->id" />

            </div>

            <div><label class="admin-label">Marka</label>

                <select name="brand_id" class="admin-input">

                    <option value="">— Seçin —</option>

                    @foreach($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id)==$b->id)>{{ $b->name }}</option>@endforeach

                </select>

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <div><label class="admin-label">Fiyat (₺)</label><input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="admin-input"></div>

                <div><label class="admin-label">İndirimli (₺)</label><input type="number" step="0.01" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}" class="admin-input"></div>

                <div><label class="admin-label">Stok</label><input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required class="admin-input"></div>

            </div>

            <h3 class="admin-section-title">Pazaryeri & lojistik</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="admin-label">Barkod (EAN/GTIN)</label><input name="barcode" value="{{ old('barcode', $product->barcode) }}" class="admin-input font-mono text-sm" placeholder="8690000000000"></div>
                <div><label class="admin-label">KDV (%)</label><input type="number" step="0.01" min="0" max="100" name="vat_rate" value="{{ old('vat_rate', $product->vat_rate ?? config('marketplace.default_vat_rate', 20)) }}" class="admin-input"></div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div><label class="admin-label">Ağırlık (kg)</label><input type="number" step="0.001" min="0" name="weight_kg" value="{{ old('weight_kg', $product->weight_kg) }}" class="admin-input"></div>
                <div><label class="admin-label">En (cm)</label><input type="number" step="0.01" min="0" name="width_cm" value="{{ old('width_cm', $product->width_cm) }}" class="admin-input"></div>
                <div><label class="admin-label">Boy (cm)</label><input type="number" step="0.01" min="0" name="height_cm" value="{{ old('height_cm', $product->height_cm) }}" class="admin-input"></div>
                <div><label class="admin-label">Yükseklik (cm)</label><input type="number" step="0.01" min="0" name="depth_cm" value="{{ old('depth_cm', $product->depth_cm) }}" class="admin-input"></div>
            </div>
            @if($product->desi())
                <p class="text-xs text-slate-500">Hesaplanan desi: <strong>{{ number_format($product->desi(), 2, ',', '.') }}</strong></p>
            @endif
            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                <input type="checkbox" name="marketplace_enabled" value="1" @checked(old('marketplace_enabled', $product->marketplace_enabled ?? true)) class="rounded border-slate-300">
                Pazaryerlerine gönderime açık
            </label>



            <h3 class="admin-section-title">Görseller</h3>

            <div>

                <label class="admin-label">Kapak görseli</label>

                <x-admin.image-spec key="product_cover" />

                @if($product->imageUrl())

                    <img src="{{ $product->imageUrl() }}" alt="" class="mt-2 h-28 rounded-xl border object-cover">

                @endif

                <input type="file" name="image_file" accept="image/*" class="mt-2 w-full text-sm text-slate-600">

                <p class="text-xs text-slate-500 mt-2">veya harici URL:</p>

                <input name="image" value="{{ old('image', str_starts_with($product->image ?? '', 'http') ? $product->image : '') }}" placeholder="https://..." class="admin-input mt-1">

                <div class="mt-3"><label class="admin-label">Görsel alt metni (SEO)</label><input name="image_alt" value="{{ old('image_alt', $product->image_alt) }}" class="admin-input" placeholder="Google görsel arama için kısa açıklama"></div>

            </div>

            <div>

                <label class="admin-label">Galeri</label>

                <x-admin.image-spec key="product_gallery" />

                <input type="file" name="gallery_files[]" accept="image/*" multiple class="mt-2 w-full text-sm text-slate-600">

                @if($product->exists && $product->images->isNotEmpty())

                    <div class="mt-3 flex flex-wrap gap-3">

                        @foreach($product->images as $img)

                            <div class="admin-card p-2">

                                <img src="{{ $img->url() }}" alt="" class="h-16 w-16 object-contain rounded-lg bg-slate-50">

                            </div>

                        @endforeach

                    </div>

                    @push('admin-form-delete')

                        <div class="flex flex-wrap gap-3 max-w-2xl mt-2">

                            @foreach($product->images as $img)

                                <form method="post" action="{{ route('admin.products.gallery.destroy', [$product, $img]) }}" class="admin-card p-2 inline-flex flex-col items-start gap-2" onsubmit="return confirm('Galeri görseli silinsin mi?')">

                                    @csrf

                                    @method('DELETE')

                                    <span class="text-xs text-slate-500">Galeri #{{ $loop->iteration }}</span>

                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Görseli sil</button>

                                </form>

                            @endforeach

                        </div>

                    @endpush

                @endif

            </div>



            <h3 class="admin-section-title">Kategori & açıklama</h3>

            <div><label class="admin-label">Kategoriler</label>

                <div class="mt-2 flex flex-wrap gap-3">

                    @foreach($categories as $c)

                        <label class="admin-checkbox"><input type="checkbox" name="category_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, old('category_ids', $product->categories->pluck('id')->all())))> {{ $c->name }}</label>

                    @endforeach

                </div>

            </div>

            <div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="admin-label mb-0">Kısa açıklama (düz metin, liste kartları)</label>
                    <x-admin.ai-btn field="short_description" label="AI kısa açıklama" variant="ghost" />
                </div>
                <input name="short_description" value="{{ old('short_description', $product->short_description) }}" class="admin-input" data-seo-score-field="short_description" maxlength="300">

            </div>

            <x-admin.rich-editor

                name="description"

                label="Detaylı açıklama"

                :value="old('description', $product->description)"

                hint="SEO için en az 200 kelime. HTML modunda H2 ile bölüm başlıkları ekleyin."

            />



            <h3 class="admin-section-title">SEO & vitrin</h3>

            <x-admin.seo-fields

                :meta-title="old('meta_title', $product->meta_title)"

                :meta-description="old('meta_description', $product->meta_description)"

                hint="Boş bırakırsanız ürün adı ve kısa açıklama kullanılır."

            >

                <div>

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="admin-label mb-0">Anahtar kelimeler (virgülle)</label>
                        <x-admin.ai-btn field="tags" label="AI etiket" variant="ghost" />
                    </div>
                    <input name="tags" value="{{ old('tags', is_array($product->tags) ? implode(', ', $product->tags) : '') }}" class="admin-input" data-seo-score-field="tags" placeholder="dalgiç pompa, hidrofor, 3 inç pompa">

                </div>

                <label class="admin-checkbox"><input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured))> Öne çıkan ürün</label>

                <label class="admin-checkbox block mt-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Yayında (sitede ve Google’da görünür)</label>

            </x-admin.seo-fields>



            <h3 class="admin-section-title">İngilizce (EN)</h3>

            <div><label class="admin-label">Name (EN)</label><input name="translations[en][name]" value="{{ old('translations.en.name', $product->translations['en']['name'] ?? '') }}" class="admin-input"></div>

            <div><label class="admin-label">Short description (EN)</label><input name="translations[en][short_description]" value="{{ old('translations.en.short_description', $product->translations['en']['short_description'] ?? '') }}" class="admin-input"></div>

            <x-admin.rich-editor name="translations[en][description]" label="Description (EN)" :value="old('translations.en.description', $product->translations['en']['description'] ?? '')" :rows="6" />



            <x-admin.form-footer :delete-action="$product->exists ? route('admin.products.destroy', $product) : null" />

        </div>



        <div class="admin-form-with-seo__side mt-6 lg:mt-0">

            <x-admin.seo-score type="product" :data="$seoScoreData" />

        </div>

    </form>

@endsection


