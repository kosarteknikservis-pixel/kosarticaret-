@extends('layouts.admin')
@section('title', 'Blog')

@section('content')
    <x-admin.page-header title="Blog yazıları" subtitle="Mağaza blog / rehber içerikleri">
        <x-slot:actions><a href="{{ route('admin.blog.create') }}" class="admin-btn admin-btn-primary">+ Yeni yazı</a></x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Başlık</th><th>URL</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @forelse($posts as $p)
                    <tr>
                        <td class="font-semibold">{{ $p->title }}</td>
                        <td class="font-mono text-xs text-slate-500">/blog/{{ $p->slug }}</td>
                        <td><span class="admin-badge {{ $p->published ? 'admin-badge-success' : 'admin-badge-warning' }}">{{ $p->published ? 'Yayında' : 'Taslak' }}</span></td>
                        <td class="text-right space-x-2">
                            <a href="{{ route('blog.show', $p) }}" target="_blank" class="admin-btn admin-btn-secondary text-xs py-1.5">Gör</a>
                            <a href="{{ route('admin.blog.edit', $p) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-8 text-slate-500">Henüz yazı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($posts->hasPages())<div class="p-4 border-t">{{ $posts->links() }}</div>@endif
    </div>
@endsection
