@extends('layouts.admin')
@section('title', 'Kategoriler')

@section('content')
    <x-admin.page-header title="Kategoriler" subtitle="Ürün grupları ve menü">
        <x-slot:actions><a href="{{ route('admin.categories.create') }}" class="admin-btn admin-btn-primary">+ Ekle</a></x-slot:actions>
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
                        <td class="text-right"><a href="{{ route('admin.categories.edit', $c) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
