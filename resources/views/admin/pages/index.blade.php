@extends('layouts.admin')
@section('title', 'Sayfalar')

@section('content')
    <x-admin.page-header title="Sayfalar (CMS)" subtitle="Hakkımızda, sözleşmeler, SSS">
        <x-slot:actions>
            <x-admin.button variant="primary" :href="route('admin.pages.create')">+ Yeni sayfa</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Başlık</th><th>URL</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @foreach($pages as $p)
                    <tr>
                        <td class="font-semibold">{{ $p->title }}</td>
                        <td class="font-mono text-xs text-slate-500">/sayfa/{{ $p->slug }}</td>
                        <td>
                            <x-admin.badge :tone="$p->published ? 'success' : 'warning'">{{ $p->published ? 'Yayında' : 'Taslak' }}</x-admin.badge>
                        </td>
                        <td class="text-right space-x-2">
                            <x-admin.button variant="secondary" size="sm" :href="route('pages.show', $p)" target="_blank">Gör</x-admin.button>
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.pages.edit', $p)">Düzenle</x-admin.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
