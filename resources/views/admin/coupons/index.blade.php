@extends('layouts.admin')
@section('title', 'Kuponlar')

@section('content')
    <x-admin.page-header title="Kuponlar" subtitle="Müşterinin girdiği indirim kodları">
        <x-slot:actions>
            <x-admin.button variant="primary" :href="route('admin.coupons.create')">+ Yeni kupon</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Kod</th><th>İndirim</th><th>Min. tutar</th><th>Bitiş</th><th>Durum</th><th></th></tr></thead>
            <tbody>
                @foreach($coupons as $c)
                    <tr>
                        <td class="font-mono font-bold text-teal-800">{{ $c->code }}</td>
                        <td>%{{ $c->percent }}</td>
                        <td>{{ $c->min_amount ? number_format($c->min_amount, 0, ',', '.').' ₺' : '—' }}</td>
                        <td class="text-slate-500 text-xs">{{ $c->expires_at?->format('d.m.Y') ?? '—' }}</td>
                        <td>
                            <x-admin.badge :tone="$c->active ? 'success' : 'muted'">{{ $c->active ? 'Aktif' : 'Pasif' }}</x-admin.badge>
                        </td>
                        <td class="text-right">
                            <x-admin.button variant="secondary" size="sm" :href="route('admin.coupons.edit', $c)">Düzenle</x-admin.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
