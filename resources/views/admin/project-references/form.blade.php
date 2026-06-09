@extends('layouts.admin')
@section('title', $reference->exists ? 'Referans düzenle' : 'Yeni referans')

@section('content')
    <x-admin.page-header :title="$reference->exists ? 'Referans düzenle' : 'Yeni referans'" />

    <form method="post"
          action="{{ $reference->exists ? route('admin.project-references.update', $reference) : route('admin.project-references.store') }}"
          enctype="multipart/form-data"
          class="max-w-3xl admin-card p-6 sm:p-8 space-y-4">
        @csrf @if($reference->exists) @method('PUT') @endif

        <div><label class="admin-label">Proje başlığı</label><input name="title" value="{{ old('title', $reference->title) }}" required class="admin-input"></div>
        <x-admin.slug-field :slug="old('slug', $reference->slug)" source="title" entity="project_references" :entity-id="$reference->id" />
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="admin-label">Müşteri / firma</label><input name="client" value="{{ old('client', $reference->client) }}" class="admin-input"></div>
            <div><label class="admin-label">Sektör</label><input name="sector" value="{{ old('sector', $reference->sector) }}" class="admin-input" placeholder="Hidrofor, sulama, HVAC…"></div>
        </div>
        <div><label class="admin-label">Konum</label><input name="location" value="{{ old('location', $reference->location) }}" class="admin-input"></div>
        <div><label class="admin-label">Kısa özet (vitrin)</label><textarea name="summary" rows="3" class="admin-input">{{ old('summary', $reference->summary) }}</textarea></div>
        <x-admin.rich-editor name="body" label="Detay içerik" :value="old('body', $reference->body)" hint="İsteğe bağlı; ileride detay sayfası için saklanır." />

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
            <label class="admin-label">Proje görseli</label>
            @if($reference->imageUrl())
                <img src="{{ $reference->imageUrl() }}" alt="" class="h-32 w-full max-w-sm object-cover rounded-lg border border-slate-200">
                <label class="admin-checkbox text-sm"><input type="checkbox" name="remove_image" value="1"> Görseli kaldır</label>
            @endif
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800">
        </div>

        <div><label class="admin-label">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $reference->sort_order ?? 0) }}" class="admin-input max-w-xs"></div>
        <div class="flex flex-wrap gap-4">
            <label class="admin-checkbox"><input type="checkbox" name="featured" value="1" @checked(old('featured', $reference->featured))> Ana sayfa vitrininde göster</label>
            <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $reference->active ?? true))> Aktif</label>
        </div>

        <x-admin.form-footer :delete-action="$reference->exists ? route('admin.project-references.destroy', $reference) : null" />
    </form>
@endsection
