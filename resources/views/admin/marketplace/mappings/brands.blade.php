@extends('layouts.admin')
@section('title', 'Marka eşleştirme')

@section('content')
    <x-admin.page-header title="Marka eşleştirme" subtitle="{{ $channelKey }} kanalı için marka kimliklerini eşleştirin" />

    <x-admin.integrations-nav active="marketplace-mappings-brands" />

    @include('admin.marketplace.mappings.partials.channel-select', ['channels' => $channels, 'channelKey' => $channelKey, 'search' => $search])

    <div class="admin-card p-4 mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">{{ $mappings->count() }} / {{ $brands->count() }} marka eşleşti</p>
        <form method="post" action="{{ route('admin.integrations.marketplace.mappings.brands.suggest') }}">
            @csrf
            <input type="hidden" name="channel_key" value="{{ $channelKey }}">
            <button class="admin-btn admin-btn-secondary">Otomatik marka eşleştir</button>
        </form>
    </div>

    <section class="admin-card overflow-hidden">
        @if($brands->isEmpty())
            <p class="p-8 text-center text-slate-500">Marka bulunamadı.</p>
        @else
            <div class="overflow-x-auto">
                <table class="admin-table admin-table--stack min-w-[560px]">
                    <thead>
                        <tr>
                            <th>Mağaza markası</th>
                            <th>Platform marka ID / adı</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($brands as $brand)
                            @php $mapping = $mappings->get($brand->id); @endphp
                            <tr>
                                <td data-label="Marka" class="font-medium text-slate-900">{{ $brand->name }}</td>
                                <td data-label="Platform">
                                    @if($mapping)
                                        <p class="text-sm">{{ $mapping->external_brand_name ?: $mapping->external_brand_id }}</p>
                                        <p class="text-xs font-mono text-slate-500">{{ $mapping->external_brand_id }}</p>
                                    @else
                                        <form method="post" action="{{ route('admin.integrations.marketplace.mappings.brands.store') }}" class="flex flex-col sm:flex-row gap-2">
                                            @csrf
                                            <input type="hidden" name="channel_key" value="{{ $channelKey }}">
                                            <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                                            <input type="text" name="external_brand_id" required placeholder="Platform marka ID" class="admin-input text-sm flex-1 min-w-0">
                                            <input type="text" name="external_brand_name" placeholder="Görünen ad (opsiyonel)" class="admin-input text-sm flex-1 min-w-0">
                                            <button class="admin-btn admin-btn-primary px-3 py-2 text-sm shrink-0">Kaydet</button>
                                        </form>
                                    @endif
                                </td>
                                <td data-label="">
                                    @if($mapping)
                                        <form method="post" action="{{ route('admin.integrations.marketplace.mappings.brands.destroy', $mapping) }}" onsubmit="return confirm('Eşleştirme silinsin mi?')">
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
@endsection
