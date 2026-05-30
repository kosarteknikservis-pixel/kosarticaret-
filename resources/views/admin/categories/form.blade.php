@extends('layouts.admin')

@section('title', $category->exists ? 'Kategori düzenle' : 'Yeni kategori')



@section('content')

    <x-admin.page-header :title="$category->exists ? 'Kategori düzenle' : 'Yeni kategori'" />

    @php

        $seoScoreData = [

            'name' => old('name', $category->name),

            'slug' => old('slug', $category->slug),

            'meta_title' => old('meta_title', $category->meta_title),

            'meta_description' => old('meta_description', $category->meta_description),

            'description' => old('description', $category->description),

            'has_image' => (bool) $category->imageUrl(),

        ];

    @endphp

    <form method="post"

          action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"

          enctype="multipart/form-data"

          class="admin-form-with-seo lg:grid lg:grid-cols-[1fr_min(18rem,28%)] lg:gap-8 max-w-4xl"

          data-seo-has-image="{{ $category->imageUrl() ? '1' : '0' }}"

          data-ai-type="category" data-ai-entity="categories" data-ai-id="{{ $category->id }}">

        @csrf @if($category->exists) @method('PUT') @endif

        <div class="admin-card p-6 sm:p-8 space-y-4">

            <div><label class="admin-label">Ad</label><input name="name" value="{{ old('name', $category->name) }}" required class="admin-input" data-seo-score-field="name"></div>

            <x-admin.slug-field :slug="old('slug', $category->slug)" entity="categories" :entity-id="$category->id" />

            <div><label class="admin-label">Üst kategori</label>

                <select name="parent_id" class="admin-input"><option value="">— Kök —</option>

                    @foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id)==$p->id)>{{ $p->name }}</option>@endforeach

                </select>

            </div>

            <x-admin.rich-editor

                name="description"

                label="Kategori açıklaması"

                :value="old('description', $category->description)"

                hint="Kategori sayfasında H1 altında gösterilir. En az 120 kelime önerilir."

            />

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">

                <label class="admin-label">Kategori görseli (vitrin kartları)</label>

                <x-admin.image-spec key="category" />

                @if($category->imageUrl())

                    <img src="{{ $category->imageUrl() }}" alt="" class="h-28 w-full max-w-xs object-cover rounded-lg border border-slate-200">

                    <label class="admin-checkbox text-sm"><input type="checkbox" name="remove_image" value="1"> Görseli kaldır</label>

                @endif

                <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800">

            </div>

            <div><label class="admin-label">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="admin-input max-w-xs"></div>

            <div class="flex flex-wrap gap-4">

                <label class="admin-checkbox"><input type="checkbox" name="featured" value="1" @checked(old('featured', $category->featured))> Öne çıkan</label>

                <label class="admin-checkbox"><input type="checkbox" name="show_in_menu" value="1" @checked(old('show_in_menu', $category->show_in_menu ?? true))> Menüde göster</label>

                <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $category->active ?? true))> Aktif</label>

            </div>

            <h3 class="admin-section-title">SEO</h3>

            <x-admin.seo-fields :meta-title="old('meta_title', $category->meta_title)" :meta-description="old('meta_description', $category->meta_description)" />

            <x-admin.form-footer :delete-action="$category->exists ? route('admin.categories.destroy', $category) : null" />

        </div>

        <div class="admin-form-with-seo__side mt-6 lg:mt-0">

            <x-admin.seo-score type="category" :data="$seoScoreData" />

        </div>

    </form>

@endsection


