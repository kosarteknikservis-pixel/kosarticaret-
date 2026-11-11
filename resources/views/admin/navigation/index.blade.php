@extends('layouts.admin')
@section('title', 'Menü')

@section('content')
    <x-admin.page-header title="Menü & footer" subtitle="Üst menü ve alt bilgi linkleri">
        <x-slot:actions><x-admin.button variant="primary" :href="route('admin.menu.create')">+ Yeni link</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Etiket</th><th>URL</th><th>Konum</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="font-semibold">{{ $item->label }}</td>
                        <td class="font-mono text-xs text-slate-500 max-w-[200px] truncate">{{ $item->url }}</td>
                        <td>{{ $item->location === 'header' ? 'Üst menü' : 'Footer' }}</td>
                        <td><span class="admin-badge {{ $item->active ? 'admin-badge-success' : 'admin-badge-muted' }}">{{ $item->active ? 'Aktif' : 'Pasif' }}</span></td>
                        <td class="text-right"><x-admin.button variant="secondary" size="sm" :href="route('admin.menu.edit', $item)">Düzenle</x-admin.button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
