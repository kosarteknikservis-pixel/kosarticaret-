<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderShippingLabel
{
    public function __construct(public Order $order) {}

    public static function for(Order $order): self
    {
        $order->loadMissing('items');

        return new self($order);
    }

    public function barcodeValue(): string
    {
        return (string) $this->order->order_number;
    }

    public function orderNumber(): string
    {
        return (string) $this->order->order_number;
    }

    public function orderDate(): string
    {
        return $this->order->created_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') ?? '—';
    }

    public function senderName(): string
    {
        return SiteName::get();
    }

    public function senderPhone(): string
    {
        return (string) config('kosar.contact.phone', '');
    }

    public function logoUrl(): ?string
    {
        $url = SiteLogo::url('site-logo');
        if ($url === null) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }

    public function recipientName(): string
    {
        $teslimat = $this->order->shipping_address['teslimat'] ?? [];
        $name = trim(((string) ($teslimat['ad'] ?? '')).' '.((string) ($teslimat['soyad'] ?? '')));

        if ($name === '') {
            $name = trim((string) $this->order->customer_name);
        }

        if ($name === '') {
            $name = trim((string) data_get($this->order->shipping_address, 'shipment.fullName', ''));
        }

        return $name !== '' ? $name : 'Alıcı';
    }

    public function recipientPhone(): string
    {
        $phone = trim((string) ($this->order->phone ?? ''));
        if ($phone === '') {
            $phone = trim((string) data_get($this->order->shipping_address, 'teslimat.telefon', ''));
        }
        if ($phone === '') {
            $phone = trim((string) data_get($this->order->shipping_address, 'shipment.phone', ''));
        }

        return $phone !== '' ? $phone : '—';
    }

    public function addressLine(): string
    {
        $address = trim((string) data_get($this->order->shipping_address, 'teslimat.adres', ''));
        if ($address === '') {
            $address = trim((string) data_get($this->order->shipping_address, 'shipment.address', ''));
        }

        return self::truncate($address, 88);
    }

    public function cityDistrict(): string
    {
        $il = data_get($this->order->shipping_address, 'teslimat.il');
        $ilce = data_get($this->order->shipping_address, 'teslimat.ilce');

        if (filled($il) || filled($ilce)) {
            return self::truncate(trim(implode(' / ', array_filter([(string) $ilce, (string) $il], fn ($v) => $v !== ''))), 42);
        }

        $city = data_get($this->order->shipping_address, 'shipment.city');
        $district = data_get($this->order->shipping_address, 'shipment.district');

        return self::truncate(trim(implode(' / ', array_filter([(string) $district, (string) $city], fn ($v) => $v !== ''))), 42);
    }

    public function postalCode(): ?string
    {
        $code = trim((string) (
            data_get($this->order->shipping_address, 'teslimat.postaKodu')
            ?? data_get($this->order->shipping_address, 'teslimat.posta_kodu')
            ?? data_get($this->order->shipping_address, 'shipment.postalCode')
            ?? ''
        ));

        return $code !== '' ? $code : null;
    }

    public function cargoCompany(): string
    {
        $address = $this->order->shipping_address ?? [];

        $company = trim((string) (
            data_get($address, 'kargo_firma.name')
            ?? data_get($address, 'kargo_yontemi')
            ?? data_get($address, 'cargo_provider')
            ?? ''
        ));

        return $company !== '' ? $company : '—';
    }

    public function trackingNumber(): ?string
    {
        $tracking = trim((string) ($this->order->shipping_tracking ?? ''));

        return $tracking !== '' ? $tracking : null;
    }

    public function productSummary(): string
    {
        $items = $this->order->items;
        if ($items->isEmpty()) {
            return '—';
        }

        $first = $items->first();
        $summary = (int) $first->quantity.'× '.self::truncate((string) $first->product_name, 34);
        $extra = $items->count() - 1;

        if ($extra > 0) {
            $summary .= ' (+ '.$extra.' ürün)';
        }

        return self::truncate($summary, 58);
    }

    public function itemCount(): int
    {
        return (int) $this->order->items->sum('quantity');
    }

    public function isCashOnDelivery(): bool
    {
        return $this->order->payment_method === 'kapida_odeme';
    }

    public function orderPaymentSummary(): string
    {
        $method = match ($this->order->payment_method) {
            'kredi_karti' => 'Kredi kartı',
            'havale' => 'Havale / EFT',
            'kapida_odeme' => 'Kapıda ödeme',
            'pazaryeri' => 'Pazaryeri',
            default => Str::headline((string) ($this->order->payment_method ?? '—')),
        };

        if ($this->isCashOnDelivery()) {
            return $method.' · '.number_format((float) $this->order->total, 2, ',', '.').' ₺ tahsil';
        }

        return $method.' · '.PaymentStatus::label($this->order->payment_status);
    }

    public function shippingPaymentSummary(): string
    {
        $cost = (float) $this->order->shipping_cost;
        $costLabel = $cost <= 0
            ? 'Ücretsiz'
            : number_format($cost, 2, ',', '.').' ₺';

        if ($this->isCashOnDelivery()) {
            return 'Gönderici ödemeli · '.$costLabel;
        }

        if ($this->order->payment_status === 'basarili' || $this->order->payment_method === 'kredi_karti') {
            return 'Gönderici ödemeli · '.$costLabel.' (ödendi)';
        }

        return 'Gönderici ödemeli · '.$costLabel;
    }

    public function salesChannelLabel(): ?string
    {
        $channels = config('marketplace.sales_channels', []);
        $key = (string) ($this->order->sales_channel ?? 'website');

        if ($key === '' || $key === 'website') {
            return null;
        }

        return (string) ($channels[$key] ?? Str::headline($key));
    }

    private static function truncate(string $value, int $max): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($value === '') {
            return '—';
        }

        return mb_strwidth($value, 'UTF-8') <= $max
            ? $value
            : rtrim(mb_strimwidth($value, 0, $max - 1, '', 'UTF-8')).'…';
    }
}
