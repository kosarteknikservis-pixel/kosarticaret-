@extends('layouts.admin')
@section('title', 'Kampanyalar')

@section('content')
    <x-admin.page-header title="Otomatik kampanyalar" subtitle="Kupon kodundan bağımsız; sepette otomatik uygulanır">
        <x-slot:actions>
            <x-admin.button variant="primary" :href="route('admin.promotions.create')">+ Yeni</x-admin.button>
        </x-slot:actions>
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
                        <td>
                            <x-admin.badge :tone="$p->active ? 'success' : 'muted'">{{ $p->active ? 'Aktif' : 'Pasif' }}</x-admin.badge>
                        </td>
                        <td class="text-right">
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.promotions.edit', $p)">Düzenle</x-admin.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
