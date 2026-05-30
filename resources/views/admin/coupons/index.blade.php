@extends('layouts.admin')
@section('title', 'Kuponlar')

@section('content')
    <x-admin.page-header title="Kuponlar" subtitle="Müşterinin girdiği indirim kodları">
        <x-slot:actions><a href="{{ route('admin.coupons.create') }}" class="admin-btn admin-btn-primary">+ Yeni kupon</a></x-slot:actions>
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
                        <td><span class="admin-badge {{ $c->active ? 'admin-badge-success' : 'admin-badge-muted' }}">{{ $c->active ? 'Aktif' : 'Pasif' }}</span></td>
                        <td class="text-right"><a href="{{ route('admin.coupons.edit', $c) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
