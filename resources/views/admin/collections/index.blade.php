@extends('layouts.admin')

@section('title', 'Etiketler')

@section('content')
    <x-admin.page-header title="Etiketler" subtitle="Taslak toplama sayfaları. Google’a açık değiller.">
        <x-slot:actions>
            <a href="{{ route('admin.collections.create') }}" class="admin-btn admin-btn-primary">+ Etiket</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ad</th>
                    <th>Durum</th>
                    <th>Ürün</th>
                    <th>Kategori</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($collections as $collection)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $collection->name }}</p>
                            <p class="text-xs text-slate-500">/koleksiyon/{{ $collection->slug }}</p>
                        </td>
                        <td>
                            @if($collection->status === 'index')
                                <span class="admin-badge admin-badge-success">Index</span>
                            @elseif($collection->status === 'noindex')
                                <span class="admin-badge">Noindex</span>
                            @else
                                <span class="admin-badge">Taslak</span>
                            @endif
                            @if($collection->status === 'index' && $collection->visible_products_count < \App\Models\Collection::MIN_PRODUCTS)
                                <p class="text-xs text-amber-700 mt-1">6 ürünün altında</p>
                            @endif
                        </td>
                        <td>{{ $collection->visible_products_count }}</td>
                        <td class="text-sm text-slate-600">{{ $collection->category?->name }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.collections.preview', $collection) }}" class="admin-btn admin-btn-secondary">Önizle</a>
                            <a href="{{ route('admin.collections.edit', $collection) }}" class="admin-btn admin-btn-secondary">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-slate-500">Etiket yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
