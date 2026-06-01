@extends('layouts.admin')
@section('title', 'Sipariş '.$order->order_number)

@section('content')
    <x-admin.page-header :title="'Sipariş '.$order->order_number" :subtitle="$order->email.' · '.$order->created_at->format('d.m.Y H:i')" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <section class="admin-card overflow-hidden">
                <h2 class="px-5 py-4 font-bold border-b border-slate-100 bg-slate-50">Ürünler</h2>
                <table class="admin-table">
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }} <span class="text-slate-500">× {{ $item->quantity }}</span></td>
                                <td class="text-right font-semibold">{{ number_format($item->line_total, 2, ',', '.') }} ₺</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-5 py-4 border-t flex justify-between items-center">
                    <span class="font-bold text-slate-900">Toplam</span>
                    <span class="text-xl font-extrabold text-teal-700">{{ number_format($order->total, 2, ',', '.') }} ₺</span>
                </div>
            </section>

            @php
                $teslimat = $order->shipping_address['teslimat'] ?? [];
                $kurumsalFatura = $teslimat['kurumsalFatura'] ?? null;
                $kargoFirma = $order->shipping_address['kargo_firma']['name'] ?? $order->shipping_address['kargo_yontemi'] ?? '—';
            @endphp
            <section class="admin-card p-5 sm:p-6 text-sm space-y-2">
                <h2 class="font-bold text-slate-900 mb-3">Teslimat</h2>
                <p class="font-medium">{{ $order->customer_name }}</p>
                <p class="text-slate-600">{{ $order->phone ?? ($teslimat['telefon'] ?? '') }}</p>
                <p class="text-slate-600">{{ $teslimat['adres'] ?? '' }}</p>
                <p class="text-slate-600">{{ ($teslimat['ilce'] ?? '').' / '.($teslimat['il'] ?? '') }} {{ $teslimat['postaKodu'] ?? $teslimat['posta_kodu'] ?? '' }}</p>
                <p class="text-xs text-slate-500 mt-2">Kargo: {{ $kargoFirma }} · Ödeme: {{ $order->payment_method }}</p>
            </section>

            @if($kurumsalFatura)
                <section class="admin-card p-5 sm:p-6 text-sm space-y-2">
                    <h2 class="font-bold text-slate-900 mb-3">Kurumsal fatura</h2>
                    <p class="font-medium">{{ $kurumsalFatura['firmaAdi'] ?? '—' }}</p>
                    <p class="text-slate-600">Vergi no: {{ $kurumsalFatura['vergiNumarasi'] ?? '—' }}</p>
                    <p class="text-slate-600">Vergi dairesi: {{ $kurumsalFatura['vergiDairesi'] ?? '—' }}</p>
                    <p class="text-slate-600">{{ $kurumsalFatura['faturaAdresi'] ?? '—' }}</p>
                </section>
            @endif
        </div>

        <aside>
            <form method="post" action="{{ route('admin.orders.update', $order) }}" class="admin-card p-5 sm:p-6 space-y-4 sticky top-24">
                @csrf @method('PATCH')
                <h2 class="font-bold text-slate-900">Sipariş yönetimi</h2>
                <div>
                    <label class="admin-label">Durum</label>
                    <select name="status" class="admin-input">
                        @foreach(\App\Support\OrderStatus::labels() as $value => $label)
                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="admin-label">Ödeme durumu</label><input name="payment_status" value="{{ $order->payment_status }}" class="admin-input"></div>
                <div><label class="admin-label">Kargo takip no</label><input name="shipping_tracking" value="{{ $order->shipping_tracking }}" class="admin-input font-mono"></div>
                <div><label class="admin-label">Admin notu</label><textarea name="admin_note" rows="3" class="admin-input">{{ $order->admin_note }}</textarea></div>
                <button type="submit" class="admin-btn admin-btn-primary w-full py-2.5">Güncelle</button>
            </form>
            <a href="{{ route('admin.orders.index') }}" class="mt-4 block text-center text-sm font-semibold text-teal-700 hover:underline">← Sipariş listesi</a>
        </aside>
    </div>
@endsection
