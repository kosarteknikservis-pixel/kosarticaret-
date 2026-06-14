@extends('layouts.admin')
@section('title', 'Özellik eşleştirme')

@section('content')
    <x-admin.page-header title="Özellik eşleştirme" subtitle="Ürün specs alanlarını platform attribute’larına map edin" />

    <x-admin.integrations-nav active="marketplace-mappings-attributes" />

    <form method="get" class="admin-card p-4 mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-[180px]">
            <label class="admin-label">Kanal</label>
            <select name="channel" class="admin-input" onchange="this.form.submit()">
                @foreach($channels as $channel)
                    <option value="{{ $channel->key }}" @selected($channelKey === $channel->key)>{{ $channel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[220px] flex-1">
            <label class="admin-label">Eşleşmiş kategori</label>
            <select name="category_id" class="admin-input" onchange="this.form.submit()">
                <option value="">Kategori seçin…</option>
                @foreach($mappedCategories as $mapped)
                    <option value="{{ $mapped->category_id }}" @selected($categoryId === $mapped->category_id)>
                        {{ $mapped->category?->name ?? 'Kategori #'.$mapped->category_id }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    @if($categoryId <= 0)
        <div class="admin-card p-8 text-center text-slate-500">
            Özellik eşleştirmesi için önce bir kategori seçin. Kategori eşleştirmesi yapılmamışsa
            <a href="{{ route('admin.integrations.marketplace.mappings.categories', ['channel' => $channelKey]) }}" class="text-[var(--admin-primary)] underline">kategori eşleştirme</a>
            sayfasına gidin.
        </div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            <section class="admin-card p-5">
                <h2 class="font-semibold text-slate-900 mb-3">Yeni eşleştirme</h2>
                @if(empty($specKeys))
                    <p class="text-sm text-slate-600">Bu kategoride specs tanımlı ürün bulunamadı. Manuel anahtar girebilirsiniz.</p>
                @endif
                <form method="post" action="{{ route('admin.integrations.marketplace.mappings.attributes.store') }}" class="space-y-3 mt-3">
                    @csrf
                    <input type="hidden" name="channel_key" value="{{ $channelKey }}">
                    <input type="hidden" name="category_id" value="{{ $categoryId }}">
                    <div>
                        <label class="admin-label">Yerel spec anahtarı</label>
                        @if(!empty($specKeys))
                            <select name="local_spec_key" required class="admin-input">
                                <option value="">Seçin…</option>
                                @foreach($specKeys as $key)
                                    <option value="{{ $key }}">{{ $key }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="local_spec_key" required placeholder="ör. guc_kw" class="admin-input">
                        @endif
                    </div>
                    <div>
                        <label class="admin-label">Platform attribute ID</label>
                        <input type="text" name="external_attribute_id" required class="admin-input" placeholder="Platform alan kimliği">
                    </div>
                    <div>
                        <label class="admin-label">Platform attribute adı (opsiyonel)</label>
                        <input type="text" name="external_attribute_name" class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Değer map (JSON, opsiyonel)</label>
                        <textarea name="value_map" rows="3" class="admin-input font-mono text-xs" placeholder='{"220V":"220 Volt"}'></textarea>
                    </div>
                    <button class="admin-btn admin-btn-primary px-5 py-2.5">Kaydet</button>
                </form>
            </section>

            <section class="admin-card overflow-hidden">
                <div class="admin-panel-head px-5 py-4 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-900">Mevcut eşleştirmeler</h2>
                </div>
                @if($mappings->isEmpty())
                    <p class="p-6 text-sm text-slate-500">Henüz özellik eşleştirmesi yok.</p>
                @else
                    <table class="admin-table admin-table--stack">
                        <thead>
                            <tr>
                                <th>Spec</th>
                                <th>Platform</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappings as $mapping)
                                <tr>
                                    <td data-label="Spec" class="font-mono text-sm">{{ $mapping->local_spec_key }}</td>
                                    <td data-label="Platform">
                                        <p class="text-sm">{{ $mapping->external_attribute_name ?: $mapping->external_attribute_id }}</p>
                                        <p class="text-xs font-mono text-slate-500">{{ $mapping->external_attribute_id }}</p>
                                    </td>
                                    <td data-label="">
                                        <form method="post" action="{{ route('admin.integrations.marketplace.mappings.attributes.destroy', $mapping) }}" onsubmit="return confirm('Silinsin mi?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>
    @endif
@endsection
