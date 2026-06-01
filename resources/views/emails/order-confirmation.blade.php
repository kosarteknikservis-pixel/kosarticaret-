<!DOCTYPE html>
<html lang="tr">
<head><meta charset="utf-8"><title>Sipariş Onayı</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#334155">
    <h2>Siparişiniz alındı</h2>
    <p>Merhaba {{ $order->customer_name }},</p>
    <p><strong>{{ $order->order_number }}</strong> numaralı siparişiniz kaydedildi.</p>
    <p>Toplam: <strong>{{ number_format($order->total, 2, ',', '.') }} ₺</strong></p>
    <p>Ödeme: {{ $order->payment_method }} — Durum: {{ $order->status }}</p>
    @php $kurumsalFatura = $order->shipping_address['teslimat']['kurumsalFatura'] ?? null; @endphp
    @if($kurumsalFatura)
        <p><strong>Kurumsal fatura:</strong> {{ $kurumsalFatura['firmaAdi'] ?? '' }} · Vergi No: {{ $kurumsalFatura['vergiNumarasi'] ?? '' }} · Vergi Dairesi: {{ $kurumsalFatura['vergiDairesi'] ?? '' }}</p>
    @endif
    <ul>
        @foreach($order->items as $item)
            <li>{{ $item->product_name }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2, ',', '.') }} ₺</li>
        @endforeach
    </ul>
    <p><a href="{{ route('tracking.show') }}">Sipariş takip</a></p>
    <p style="font-size:12px;color:#94a3b8">{{ config('kosar.name') }} — {{ config('kosar.contact.email') }}</p>
</body>
</html>
