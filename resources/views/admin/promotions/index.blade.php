@extends('layouts.admin')
@section('title', 'Kampanyalar')

@section('content')
    <x-admin.page-header title="Otomatik kampanyalar" subtitle="Kupon kodundan bağımsız; sepette otomatik uygulanır">
        <x-slot:actions><a href="{{ route('admin.promotions.create') }}" class="admin-btn admin-btn-primary">+ Yeni</a></x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Ad</th><th>Tip</th><th>Uygulama</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @foreach($promotions as $p)
                    <tr>
                        <td class="font-semibold">{{ $p->name }}</td>
                        <td class="text-slate-600">{{ $p->type }}</td>
                        <td>{{ $p->auto_apply ? 'Otomatik' : 'Manuel' }}</td>
                        <td><span class="admin-badge {{ $p->active ? 'admin-badge-success' : 'admin-badge-muted' }}">{{ $p->active ? 'Aktif' : 'Pasif' }}</span></td>
                        <td class="text-right"><a href="{{ route('admin.promotions.edit', $p) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
