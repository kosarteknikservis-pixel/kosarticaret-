@extends('layouts.admin')
@section('title', 'Siparişler')

@section('content')
    <x-admin.page-header title="Siparişler" subtitle="Mağaza sipariş geçmişi" />

    <form method="get" class="admin-card p-4 sm:p-5 mb-5">
        <div class="grid gap-3 md:grid-cols-6">
            <div class="md:col-span-2">
                <label class="admin-label">Arama</label>
                <input name="q" value="{{ $filters['q'] ?? '' }}" class="admin-input" placeholder="Sipariş no, müşteri, e-posta, telefon">
            </div>
            <div>
                <label class="admin-label">Durum</label>
                <select name="status" class="admin-input">
                    <option value="">Tümü</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-label">Ödeme</label>
                <select name="payment_status" class="admin-input">
                    <option value="">Tümü</option>
                    @foreach($paymentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-label">Başlangıç</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="admin-input">
            </div>
            <div>
                <label class="admin-label">Bitiş</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="admin-input">
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 items-end">
            <div>
                <label class="admin-label">Takip no</label>
                <select name="tracking" class="admin-input min-w-[160px]">
                    <option value="">Tümü</option>
                    <option value="var" @selected(($filters['tracking'] ?? '') === 'var')>Var</option>
                    <option value="yok" @selected(($filters['tracking'] ?? '') === 'yok')>Yok</option>
                </select>
            </div>
            <button class="admin-btn admin-btn-primary px-5 py-2.5">Filtrele</button>
            <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Temizle</a>
        </div>
    </form>

    <div class="admin-card overflow-hidden">
        @if($orders->isEmpty())
            <p class="p-8 text-center text-slate-500">Sipariş bulunamadı.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sipariş no</th>
                        <th>Müşteri</th>
                        <th>Tarih</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Ödeme</th>
                        <th>Kargo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $o)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $o) }}" class="link font-mono text-xs">{{ $o->order_number }}</a></td>
                            <td class="max-w-[220px]">
                                <p class="font-semibold truncate">{{ $o->customer_name ?: '—' }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $o->email }}</p>
                            </td>
                            <td class="text-slate-500 text-xs">{{ $o->created_at->format('d.m.Y H:i') }}</td>
                            <td class="font-semibold">{{ number_format($o->total, 2, ',', '.') }} ₺</td>
                            <td><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold">{{ \App\Support\OrderStatus::label($o->status) }}</span></td>
                            <td><span class="rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold">{{ \App\Support\PaymentStatus::label($o->payment_status) }}</span></td>
                            <td class="text-xs text-slate-500">{{ $o->shipping_tracking ? 'Takip var' : 'Takip yok' }}</td>
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
