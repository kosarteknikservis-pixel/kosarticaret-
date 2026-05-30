@extends('layouts.admin')
@section('title', $customer->name)

@section('content')
    <x-admin.page-header :title="$customer->name" :subtitle="$customer->email">
        <x-slot:actions>
            <a href="{{ route('admin.customers.index') }}" class="admin-btn admin-btn-secondary">← Liste</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-4 lg:grid-cols-3 max-w-5xl">
        <div class="admin-card p-5 lg:col-span-1">
            <h3 class="admin-section-title" style="margin-top:0">Hesap</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500 text-xs font-semibold uppercase tracking-wide">E-posta</dt>
                    <dd class="font-mono mt-0.5">{{ $customer->email }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 text-xs font-semibold uppercase tracking-wide">Kayıt tarihi</dt>
                    <dd class="mt-0.5">{{ $customer->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 text-xs font-semibold uppercase tracking-wide">Toplam sipariş tutarı</dt>
                    <dd class="mt-0.5 font-bold text-slate-900">{{ number_format($ordersTotal, 2, ',', '.') }} ₺</dd>
                </div>
            </dl>
        </div>

        <div class="admin-card overflow-hidden lg:col-span-2">
            <div class="p-5 border-b border-slate-100">
                <h3 class="admin-section-title" style="margin-top:0">Siparişler</h3>
            </div>
            @if($orders->isEmpty())
                <p class="p-8 text-center text-slate-500 text-sm">Bu hesapla henüz sipariş verilmemiş (misafir ödeme veya henüz alışveriş yok).</p>
            @else
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Sipariş</th>
                            <th>Tarih</th>
                            <th>Tutar</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td class="font-mono text-xs">{{ $order->order_number }}</td>
                                <td class="text-slate-500 text-xs">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                <td class="font-semibold">{{ number_format($order->total, 2, ',', '.') }} ₺</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Sipariş</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($orders->hasPages())
                    <div class="p-4 border-t">{{ $orders->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
