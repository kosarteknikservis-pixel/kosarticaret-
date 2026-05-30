@extends('layouts.admin')
@section('title', 'Siparişler')

@section('content')
    <x-admin.page-header title="Siparişler" subtitle="Mağaza sipariş geçmişi" />

    <div class="admin-card overflow-hidden">
        @if($orders->isEmpty())
            <p class="p-8 text-center text-slate-500">Henüz sipariş yok.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sipariş no</th>
                        <th>Müşteri</th>
                        <th>Tarih</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $o)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $o) }}" class="link font-mono text-xs">{{ $o->order_number }}</a></td>
                            <td class="max-w-[180px] truncate">{{ $o->email }}</td>
                            <td class="text-slate-500 text-xs">{{ $o->created_at->format('d.m.Y H:i') }}</td>
                            <td class="font-semibold">{{ number_format($o->total, 2, ',', '.') }} ₺</td>
                            <td><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold">{{ \App\Support\OrderStatus::label($o->status) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('admin.orders.show', $o) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Detay</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 border-t border-slate-100">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
