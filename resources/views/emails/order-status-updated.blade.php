<!DOCTYPE html>
<html lang="tr">
<head><meta charset="utf-8"><title>Sipariş Durumu</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#334155">
    <h2>Sipariş durumunuz güncellendi</h2>
    <p>Merhaba {{ $order->customer_name }},</p>
    <p><strong>{{ $order->order_number }}</strong> numaralı siparişinizin güncel durumu: <strong>{{ \App\Support\OrderStatus::label($order->status) }}</strong></p>
    @if($order->shipping_tracking)
        <p>Kargo takip no: <strong>{{ $order->shipping_tracking }}</strong></p>
    @endif
    <p>Toplam: <strong>{{ number_format($order->total, 2, ',', '.') }} ₺</strong></p>
    <ul>
        @foreach($order->items as $item)
            <li>{{ $item->product_name }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2, ',', '.') }} ₺</li>
        @endforeach
    </ul>
    <p><a href="{{ route('tracking.show') }}">Sipariş takip sayfası</a></p>
    <p style="font-size:12px;color:#94a3b8">{{ config('kosar.name') }} — {{ config('kosar.contact.email') }}</p>
</body>
</html>
