@extends('layouts.admin')
@section('title', 'E-posta şablonları')

@section('content')
    <x-admin.page-header title="E-posta şablonları" subtitle="Sipariş ve kampanya maillerinin metinlerini panelden yönetin">
        <x-slot:actions>
            <x-admin.button variant="primary" :href="route('admin.email-templates.create')">Yeni şablon</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Şablon</th><th>Konu</th><th>Tür</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @foreach($templates as $template)
                    <tr>
                        <td>
                            <p class="font-semibold">{{ $template->name }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $template->key }}</p>
                        </td>
                        <td class="text-slate-600">{{ $template->subject }}</td>
                        <td>
                            <span class="rounded-full {{ str_starts_with($template->key, 'custom_') ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 text-xs font-semibold">
                                {{ str_starts_with($template->key, 'custom_') ? 'Özel' : 'Sistem' }}
                            </span>
                        </td>
                        <td><span class="rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold">{{ $template->active ? 'Aktif' : 'Pasif' }}</span></td>
                        <td class="text-right"><x-admin.button variant="secondary" size="sm" :href="route('admin.email-templates.edit', $template)">Düzenle</x-admin.button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
