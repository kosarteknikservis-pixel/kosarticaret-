@extends('layouts.admin')

@section('title', $page->exists ? 'Sayfa düzenle' : 'Yeni sayfa')

@section('content')
    @php
        $seoScoreData = [
            'title' => old('title', $page->title),
            'slug' => old('slug', $page->slug),
            'meta_title' => old('meta_title', $page->meta_title),
            'meta_description' => old('meta_description', $page->meta_description),
            'content' => old('content', $page->content),
        ];
    @endphp

    <x-admin.page-header :title="$page->exists ? 'Sayfa düzenle' : 'Yeni sayfa'" />

    <form method="post"
          action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
          class="admin-form-with-seo lg:grid lg:grid-cols-[1fr_min(18rem,28%)] lg:gap-8 max-w-4xl"
          data-ai-type="page" data-ai-entity="pages" data-ai-id="{{ $page->id }}">
        @csrf @if($page->exists) @method('PUT') @endif

        <div class="admin-card p-6 sm:p-8 space-y-4">
            <div><label class="admin-label">Başlık</label><input name="title" value="{{ old('title', $page->title) }}" required class="admin-input" data-seo-score-field="title"></div>
            <x-admin.slug-field :slug="old('slug', $page->slug)" source="title" entity="pages" :entity-id="$page->id" />
            <x-admin.rich-editor name="content" label="İçerik" :value="old('content', $page->content)" hint="Kurumsal sayfa metni; düz metin veya HTML." />
            <h3 class="admin-section-title">SEO</h3>
            <x-admin.seo-fields :meta-title="old('meta_title', $page->meta_title)" :meta-description="old('meta_description', $page->meta_description)" />
            <label class="admin-checkbox"><input type="checkbox" name="published" value="1" @checked(old('published', $page->published ?? true))> Yayında</label>
            <x-admin.form-footer :delete-action="$page->exists ? route('admin.pages.destroy', $page) : null" />
        </div>

        <div class="admin-form-with-seo__side mt-6 lg:mt-0">
            <x-admin.seo-score type="page" :data="$seoScoreData" />
        </div>
    </form>
@endsection
