@extends('layouts.admin')
@section('title', $item->exists ? 'Link düzenle' : 'Yeni menü linki')

@section('content')
    <x-admin.page-header :title="$item->exists ? 'Link düzenle' : 'Yeni menü linki'" />
    <form method="post" action="{{ $item->exists ? route('admin.menu.update', $item) : route('admin.menu.store') }}" class="admin-card p-6 sm:p-8 max-w-xl space-y-4">
        @csrf @if($item->exists) @method('PUT') @endif
        <div><label class="admin-label">Etiket</label><input name="label" value="{{ old('label', $item->label) }}" required class="admin-input"></div>
        <div><label class="admin-label">URL</label><input name="url" value="{{ old('url', $item->url) }}" required placeholder="/urunler veya https://..." class="admin-input font-mono text-sm"></div>
        <div><label class="admin-label">Konum</label>
            <select name="location" class="admin-input">
                <option value="header" @selected(old('location', $item->location)==='header')>Üst menü</option>
                <option value="footer" @selected(old('location', $item->location)==='footer')>Footer</option>
            </select>
        </div>
        <div><label class="admin-label">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="admin-input max-w-xs"></div>
        <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $item->active ?? true))> Aktif</label>
        <label class="admin-checkbox"><input type="checkbox" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab', $item->open_in_new_tab))> Yeni sekmede aç</label>
        <x-admin.form-footer :delete-action="$item->exists ? route('admin.menu.destroy', $item) : null" />
    </form>
@endsection
