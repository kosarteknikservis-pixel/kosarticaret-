@extends('layouts.admin')
@section('title', 'Referans projeler')

@section('content')
    <x-admin.page-header title="Referans / proje vitrini" subtitle="Ana sayfada öne çıkan projeler">
        <x-slot:actions><a href="{{ route('admin.project-references.create') }}" class="admin-btn admin-btn-primary">+ Yeni referans</a></x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Başlık</th>
                    <th>Sektör</th>
                    <th>Durum</th>
                    <th>Sıra</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($references as $ref)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $ref->title }}</p>
                            @if($ref->client)<p class="text-xs text-slate-500">{{ $ref->client }}</p>@endif
                        </td>
                        <td class="text-slate-600">{{ $ref->sector ?: '—' }}</td>
                        <td>
                            @if($ref->active)
                                <span class="admin-badge admin-badge-success">Aktif</span>
                            @else
                                <span class="admin-badge admin-badge-warning">Pasif</span>
                            @endif
                            @if($ref->featured)
                                <span class="admin-badge ml-1">Vitrin</span>
                            @endif
                        </td>
                        <td class="text-slate-500">{{ $ref->sort_order }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.project-references.edit', $ref) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-10 text-slate-500">Henüz referans eklenmedi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
