@extends('layouts.admin')
@section('title', 'Blog')

@section('content')
    <x-admin.page-header title="Blog yazıları" subtitle="Mağaza blog / rehber içerikleri">
        <x-slot:actions>
            <x-admin.button variant="primary" :href="route('admin.blog.create')">+ Yeni yazı</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Başlık</th><th>URL</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @forelse($posts as $p)
                    <tr>
                        <td class="font-semibold">{{ $p->title }}</td>
                        <td class="font-mono text-xs text-slate-500">/blog/{{ $p->slug }}</td>
                        <td>
                            <x-admin.badge :tone="$p->published ? 'success' : 'warning'">{{ $p->published ? 'Yayında' : 'Taslak' }}</x-admin.badge>
                        </td>
                        <td class="text-right space-x-2">
                            <x-admin.button variant="secondary" size="sm" :href="route('blog.show', $p)" target="_blank">Gör</x-admin.button>
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.blog.edit', $p)">Düzenle</x-admin.button>
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
