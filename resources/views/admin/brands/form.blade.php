@extends('layouts.admin')

@section('title', $brand->exists ? 'Marka düzenle' : 'Yeni marka')



@section('content')

    <x-admin.page-header :title="$brand->exists ? 'Marka düzenle' : 'Yeni marka'" />

    @php

        $seoScoreData = [

            'name' => old('name', $brand->name),

            'slug' => old('slug', $brand->slug),

            'meta_title' => old('meta_title', $brand->meta_title),

            'meta_description' => old('meta_description', $brand->meta_description),

            'description' => old('description', $brand->description),

            'has_image' => (bool) $brand->logoUrl(),

        ];

    @endphp

    <form method="post"

          action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}"

          enctype="multipart/form-data"

          class="admin-form-with-seo lg:grid lg:grid-cols-[1fr_min(18rem,28%)] lg:gap-8 max-w-4xl"

          data-seo-has-image="{{ $brand->logoUrl() ? '1' : '0' }}"

          data-ai-type="brand" data-ai-entity="brands" data-ai-id="{{ $brand->id }}">

        @csrf @if($brand->exists) @method('PUT') @endif

        <div class="admin-card p-6 sm:p-8 space-y-4">

            <div><label class="admin-label">Ad</label><input name="name" value="{{ old('name', $brand->name) }}" required class="admin-input" data-seo-score-field="name"></div>

            <x-admin.slug-field :slug="old('slug', $brand->slug)" entity="brands" :entity-id="$brand->id" />

            <x-admin.rich-editor

                name="description"

                label="Marka açıklaması"

                :value="old('description', $brand->description)"

                hint="Marka sayfasında gösterilir. Distribütörlük, ürün grupları ve güven mesajları ekleyin."

            />

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">

                <label class="admin-label">Marka logosu</label>

                <x-admin.image-spec key="brand_logo" />

                @if($brand->logoUrl())

                    <img src="{{ $brand->logoUrl() }}" alt="" class="h-14 max-w-[200px] object-contain rounded-lg bg-white border border-slate-200 p-2">

                    <label class="admin-checkbox text-sm"><input type="checkbox" name="remove_logo" value="1"> Logoyu kaldır</label>

                @endif

                <input type="file" name="logo_file" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800">

                <div><label class="admin-label text-slate-500 font-normal">veya harici URL</label><input name="logo_url" value="{{ old('logo_url', str_starts_with((string) $brand->logo_url, 'http') ? $brand->logo_url : '') }}" class="admin-input" placeholder="https://..."></div>

            </div>

            <div class="rounded-lg border border-teal-100 bg-teal-50/50 p-3 text-sm text-slate-700 space-y-1">

                <p><strong>Ana sayfa şeridi:</strong> İşaretlerseniz logo ana sayfada görünür.</p>

                <p><strong>Mağaza linki:</strong> Ziyaretçi logoya tıklayınca <code class="text-xs bg-white px-1 rounded">/marka/{{ $brand->slug ?: 'slug' }}</code> sayfasında yalnızca bu markanın ürünleri listelenir.</p>

            </div>

            <label class="admin-checkbox"><input type="checkbox" name="featured" value="1" @checked(old('featured', $brand->featured))> Ana sayfada göster (marka şeridi)</label>

            <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $brand->active ?? true))> Aktif (mağazada listelenir)</label>

            <h3 class="admin-section-title">SEO</h3>

            <x-admin.seo-fields :meta-title="old('meta_title', $brand->meta_title)" :meta-description="old('meta_description', $brand->meta_description)" />

            <x-admin.form-footer :delete-action="$brand->exists ? route('admin.brands.destroy', $brand) : null" />

        </div>

        <div class="admin-form-with-seo__side mt-6 lg:mt-0">

            <x-admin.seo-score type="brand" :data="$seoScoreData" />

        </div>

    </form>

@endsection


