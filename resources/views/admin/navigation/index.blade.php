@extends('layouts.admin')
@section('title', 'Menü')

@section('content')
    <x-admin.page-header title="Menü & footer" subtitle="Üst menü ve alt bilgi linkleri">
        <x-slot:actions><a href="{{ route('admin.menu.create') }}" class="admin-btn admin-btn-primary">+ Yeni link</a></x-slot:actions>
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
                        <td class="text-right"><a href="{{ route('admin.menu.edit', $item) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
