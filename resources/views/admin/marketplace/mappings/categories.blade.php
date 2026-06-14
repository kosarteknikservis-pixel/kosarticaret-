@extends('layouts.admin')
@section('title', 'Kategori eşleştirme')

@section('content')
    <x-admin.page-header title="Kategori eşleştirme" subtitle="Mağaza kategorilerini {{ $channelKey }} platform kategorilerine bağlayın" />

    <x-admin.integrations-nav active="marketplace-mappings-categories" />

    @include('admin.marketplace.mappings.partials.channel-select', ['channels' => $channels, 'channelKey' => $channelKey, 'search' => $search])

    <div class="grid gap-5 lg:grid-cols-3 mb-6">
        <section class="admin-card p-5 lg:col-span-1">
            <h2 class="font-semibold text-slate-900 mb-3">Harici kategori import</h2>
            <p class="text-sm text-slate-600 mb-4">Platform kategori ağacını JSON dizi olarak yükleyin. Alanlar: <code>id</code>, <code>name</code>, isteğe bağlı <code>path</code>, <code>parent_id</code>.</p>
            <form method="post" enctype="multipart/form-data" action="{{ route('admin.integrations.marketplace.mappings.categories.import-external') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="channel_key" value="{{ $channelKey }}">
                <div>
                    <label class="admin-label">JSON dosyası</label>
                    <input type="file" name="json_file" accept=".json,application/json" required class="admin-input">
                    @error('json_file')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="replace" value="1" class="rounded border-slate-300">
                    Mevcut harici kategorileri sil ve yeniden yükle
                </label>
                <button class="admin-btn admin-btn-secondary w-full justify-center">Import et</button>
            </form>
            <form method="post" action="{{ route('admin.integrations.marketplace.mappings.categories.suggest') }}" class="mt-4 pt-4 border-t border-slate-100">
                @csrf
                <input type="hidden" name="channel_key" value="{{ $channelKey }}">
                <button class="admin-btn admin-btn-primary w-full justify-center" @disabled($externalCategories->isEmpty())>Otomatik öneri uygula</button>
                @if($externalCategories->isEmpty())
                    <p class="text-xs text-slate-500 mt-2 text-center">Önce harici kategori import edin.</p>
                @endif
            </form>
            <p class="text-xs text-slate-500 mt-3">{{ number_format($externalCategories->count()) }} harici kategori kayıtlı</p>
        </section>

        <section class="admin-card overflow-hidden lg:col-span-2">
            <div class="admin-panel-head px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-slate-900">Kategoriler</h2>
                <span class="text-xs text-slate-500">{{ $mappings->count() }} / {{ $categories->count() }} eşleşti</span>
            </div>
            @if($categories->isEmpty())
                <p class="p-8 text-center text-slate-500">Kategori bulunamadı.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table admin-table--stack min-w-[640px]">
                        <thead>
                            <tr>
                                <th>Mağaza kategorisi</th>
                                <th>Platform kategorisi</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                @php $mapping = $mappings->get($category->id); @endphp
                                <tr>
                                    <td data-label="Mağaza">
                                        <p class="font-medium text-slate-900">{{ $category->name }}</p>
                                        @if($category->parent)
                                            <p class="text-xs text-slate-500">{{ $category->parent->name }}</p>
                                        @endif
                                    </td>
                                    <td data-label="Platform">
                                        @if($mapping)
                                            <p class="text-sm text-slate-900">{{ $mapping->external_category_name ?: $mapping->external_category_id }}</p>
                                            @if($mapping->external_category_path)
                                                <p class="text-xs text-slate-500 truncate max-w-xs">{{ $mapping->external_category_path }}</p>
                                            @endif
                                        @else
                                            <form method="post" action="{{ route('admin.integrations.marketplace.mappings.categories.store') }}" class="flex flex-col sm:flex-row gap-2">
                                                @csrf
                                                <input type="hidden" name="channel_key" value="{{ $channelKey }}">
                                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                                <select name="external_category_id" required class="admin-input text-sm min-w-0 flex-1" @disabled($externalCategories->isEmpty())>
                                                    <option value="">Seçin…</option>
                                                    @foreach($externalCategories as $ext)
                                                        <option value="{{ $ext->external_id }}">{{ $ext->path ?: $ext->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="admin-btn admin-btn-primary px-3 py-2 text-sm shrink-0" @disabled($externalCategories->isEmpty())>Kaydet</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td data-label="" class="text-right">
                                        @if($mapping)
                                            <form method="post" action="{{ route('admin.integrations.marketplace.mappings.categories.destroy', $mapping) }}" onsubmit="return confirm('Eşleştirme silinsin mi?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-700">Sil</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
