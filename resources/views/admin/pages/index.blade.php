@extends('layouts.admin')
@section('title', 'Sayfalar')

@section('content')
    <x-admin.page-header title="Sayfalar (CMS)" subtitle="Hakkımızda, sözleşmeler, SSS">
        <x-slot:actions><a href="{{ route('admin.pages.create') }}" class="admin-btn admin-btn-primary">+ Yeni sayfa</a></x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Başlık</th><th>URL</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @foreach($pages as $p)
                    <tr>
                        <td class="font-semibold">{{ $p->title }}</td>
                        <td class="font-mono text-xs text-slate-500">/sayfa/{{ $p->slug }}</td>
                        <td><span class="admin-badge {{ $p->published ? 'admin-badge-success' : 'admin-badge-warning' }}">{{ $p->published ? 'Yayında' : 'Taslak' }}</span></td>
                        <td class="text-right space-x-2">
                            <a href="{{ route('pages.show', $p) }}" target="_blank" class="admin-btn admin-btn-secondary text-xs py-1.5">Gör</a>
                            <a href="{{ route('admin.pages.edit', $p) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
