@extends('layouts.admin')

@section('title', $post->exists ? 'Yazı düzenle' : 'Yeni blog yazısı')

@section('content')
    @php
        $seoScoreData = [
            'title' => old('title', $post->title),
            'slug' => old('slug', $post->slug),
            'meta_title' => old('meta_title', $post->meta_title),
            'meta_description' => old('meta_description', $post->meta_description),
            'excerpt' => old('excerpt', $post->excerpt),
            'content' => old('content', $post->content),
            'has_image' => (bool) $post->imageUrl(),
        ];
    @endphp

    <x-admin.page-header :title="$post->exists ? 'Yazı düzenle' : 'Yeni blog yazısı'" />

    <form method="post" enctype="multipart/form-data"
          action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
          class="admin-form-with-seo lg:grid lg:grid-cols-[1fr_min(18rem,28%)] lg:gap-8 max-w-4xl"
          data-ai-type="blog" data-ai-entity="blog_posts" data-ai-id="{{ $post->id }}"
          data-seo-has-image="{{ $post->imageUrl() ? '1' : '0' }}">
        @csrf @if($post->exists) @method('PUT') @endif

        <div class="admin-card p-6 sm:p-8 space-y-4">
            <div><label class="admin-label">Başlık</label><input name="title" value="{{ old('title', $post->title) }}" required class="admin-input" data-seo-score-field="title"></div>
            <x-admin.slug-field :slug="old('slug', $post->slug)" source="title" entity="blog_posts" :entity-id="$post->id" />

            <div>
                <label class="admin-label">Kapak görseli</label>
                @if($post->imageUrl())
                    <img src="{{ $post->imageUrl() }}" alt="" class="mt-2 h-32 rounded-xl border object-cover max-w-full">
                @endif
                <input type="file" name="image_file" accept="image/*" class="mt-2 w-full text-sm text-slate-600">
                <div class="mt-3"><label class="admin-label">Görsel alt metni</label><input name="image_alt" value="{{ old('image_alt', $post->image_alt) }}" class="admin-input" placeholder="Google ve sosyal paylaşım için"></div>
            </div>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="admin-label mb-0">Özet</label>
                    <x-admin.ai-btn field="excerpt" label="AI özet" variant="ghost" />
                </div>
                <input name="excerpt" value="{{ old('excerpt', $post->excerpt) }}" class="admin-input" data-seo-score-field="excerpt">
            </div>

            <x-admin.rich-editor name="content" label="İçerik" :value="old('content', $post->content)" hint="Blog yazısı; düz metin veya HTML." />

            <div>
                <label class="admin-label">Etiketler (virgülle)</label>
                <input name="tags" value="{{ old('tags', is_array($post->tags) ? implode(', ', $post->tags) : '') }}" class="admin-input" placeholder="hidrofor, rehber">
            </div>

            <div><label class="admin-label">Yayın tarihi</label><input type="date" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d')) }}" class="admin-input max-w-xs"></div>

            <h3 class="admin-section-title">SEO</h3>
            <x-admin.seo-fields :meta-title="old('meta_title', $post->meta_title)" :meta-description="old('meta_description', $post->meta_description)" />

            <label class="admin-checkbox"><input type="checkbox" name="published" value="1" @checked(old('published', $post->published ?? true))> Yayında</label>

            <x-admin.form-footer :delete-action="$post->exists ? route('admin.blog.destroy', $post) : null" />
        </div>

        <div class="admin-form-with-seo__side mt-6 lg:mt-0">
            <x-admin.seo-score type="blog" :data="$seoScoreData" />
        </div>
    </form>
@endsection
