@extends('layouts.admin')
@section('title', 'Kategoriler')

@section('content')
    <x-admin.page-header title="Kategoriler" subtitle="Ürün grupları ve menü">
        <x-slot:actions>
            <x-admin.button variant="primary" :href="route('admin.categories.create')">+ Ekle</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Ad</th><th>Slug</th><th>Üst</th><th></th></tr></thead>
            <tbody>
                @foreach($categories as $c)
                    <tr>
                        <td class="font-semibold">{{ $c->name }}</td>
                        <td class="font-mono text-xs text-slate-500">{{ $c->slug }}</td>
                        <td class="text-slate-500">{{ $c->parent?->name ?? '—' }}</td>
                        <td class="text-right">
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.categories.edit', $c)">Düzenle</x-admin.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
