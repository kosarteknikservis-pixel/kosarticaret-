<?php

namespace App\Services\Telegram;

use App\Models\Order;
use App\Services\StoreConfig;
use Illuminate\Support\Str;

class OrderTelegramMessageBuilder
{
    public function __construct(private StoreConfig $store) {}

    public function build(Order $order): string
    {
        $order->loadMissing('items');

        $lines = [
            'Yeni Sipariş ✅',
            sprintf(
                '#%s - %s',
                $order->order_number,
                $order->created_at?->timezone(config('kosar.report_timezone', 'Europe/Istanbul'))->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            ),
            '',
            'Ad: '.$this->customerName($order),
            'Tel: '.($order->phone ?: '—'),
        ];

        $lines = array_merge($lines, $this->productLines($order));
        $lines[] = 'Ödeme: '.$this->paymentLabel($order);
        $lines[] = 'Toplam: '.number_format((float) $order->total, 2, '.', '').' ₺';

        if ($channel = $this->salesChannelLabel($order)) {
            $lines[] = 'Kanal: '.$channel;
        }

        $cityDistrict = $this->cityDistrict($order);
        if ($cityDistrict !== '') {
            $lines[] = 'İl/İlçe: '.$cityDistrict;
        }

        $address = $this->addressLine($order);
        if ($address !== '') {
            $lines[] = 'Adres: '.$address;
        }

        $invoiceLines = $this->corporateInvoiceLines($order);
        if ($invoiceLines !== []) {
            $lines[] = '';
            $lines = array_merge($lines, $invoiceLines);
        }

        $siteUrl = rtrim((string) config('kosar.url', config('app.url')), '/');
        $panelUrl = route('admin.orders.show', $order);

        $lines[] = '';
        $lines[] = '🔗 Panel: '.$panelUrl;
        $lines[] = '🔗 Site: '.$siteUrl;

        return implode("\n", $lines);
    }

    private function customerName(Order $order): string
    {
        $name = trim((string) $order->customer_name);

        if ($name !== '') {
            return $name;
        }

        $teslimat = $order->shipping_address['teslimat'] ?? [];

        return trim(((string) ($teslimat['ad'] ?? '')).' '.((string) ($teslimat['soyad'] ?? ''))) ?: '—';
    }

    /** @return list<string> */
    private function productLines(Order $order): array
    {
        $items = $order->items;

        if ($items->isEmpty()) {
            return ['Ürün: —'];
        }

        if ($items->count() === 1) {
            $item = $items->first();

            return ['Ürün: '.trim((string) $item->product_name)];
        }

        $lines = ['Ürünler:'];
        foreach ($items as $item) {
            $lines[] = sprintf(
                '• %s × %d — %s ₺',
                trim((string) $item->product_name),
                (int) $item->quantity,
                number_format((float) $item->line_total, 2, '.', ''),
            );
        }

        return $lines;
    }

    private function paymentLabel(Order $order): string
    {
        if ($order->payment_method === 'pazaryeri') {
            return 'Pazaryeri';
        }

        $methods = collect($this->store->paymentMethods());
        $match = $methods->firstWhere('id', $order->payment_method);

        if ($match && filled($match['name'] ?? null)) {
            return (string) $match['name'];
        }

        return match ($order->payment_method) {
            'kredi_karti' => 'Kredi / Banka Kartı',
            'havale' => 'Havale / EFT',
            'kapida_odeme' => 'Kapıda Ödeme',
            default => Str::headline((string) ($order->payment_method ?? '—')),
        };
    }

    private function salesChannelLabel(Order $order): ?string
    {
        $channel = trim((string) ($order->sales_channel ?? ''));

        if ($channel === '' || $channel === 'website') {
            return null;
        }

        return match ($channel) {
            'trendyol' => 'Trendyol',
            default => Str::headline($channel),
        };
    }

    private function cityDistrict(Order $order): string
    {
        $teslimat = $order->shipping_address['teslimat'] ?? null;
        if (is_array($teslimat)) {
            return trim(((string) ($teslimat['il'] ?? '')).' / '.((string) ($teslimat['ilce'] ?? '')), ' /');
        }

        $shipment = $order->shipping_address['shipment'] ?? null;
        if (is_array($shipment)) {
            $city = (string) ($shipment['city'] ?? $shipment['cityName'] ?? '');
            $district = (string) ($shipment['district'] ?? $shipment['districtName'] ?? '');

            return trim($city.' / '.$district, ' /');
        }

        return '';
    }

    private function addressLine(Order $order): string
    {
        $teslimat = $order->shipping_address['teslimat'] ?? null;
        if (is_array($teslimat) && filled($teslimat['adres'] ?? null)) {
            return trim((string) $teslimat['adres']);
        }

        $shipment = $order->shipping_address['shipment'] ?? null;
        if (is_array($shipment)) {
            return trim((string) (
                $shipment['fullAddress']
                ?? $shipment['address']
                ?? $shipment['address1']
                ?? ''
            ));
        }

        return '';
    }

    /** @return list<string> */
    private function corporateInvoiceLines(Order $order): array
    {
        $kurumsal = $order->shipping_address['teslimat']['kurumsalFatura'] ?? null;
        if (! is_array($kurumsal)) {
            return [];
        }

        $lines = ['--- Kurumsal fatura ---'];

        if (filled($kurumsal['vergiNumarasi'] ?? null)) {
            $lines[] = 'VKN: '.$kurumsal['vergiNumarasi'];
        }
        if (filled($kurumsal['vergiDairesi'] ?? null)) {
            $lines[] = 'Vergi dairesi: '.$kurumsal['vergiDairesi'];
        }
        if (filled($kurumsal['firmaAdi'] ?? null)) {
            $lines[] = 'Ünvan: '.$kurumsal['firmaAdi'];
        }
        if (filled($kurumsal['faturaAdresi'] ?? null)) {
            $lines[] = 'Fatura adresi: '.$kurumsal['faturaAdresi'];
        }

        return count($lines) > 1 ? $lines : [];
    }
}
