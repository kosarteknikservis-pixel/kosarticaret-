<?php

namespace App\Services\Parasut;

use App\Models\Order;
use App\Services\StoreConfig;

class ParasutOrderInvoiceService
{
    public function __construct(
        private readonly ParasutClient $client,
        private readonly StoreConfig $storeConfig,
    ) {}

    /** @return array<string, mixed> */
    public function createDraftInvoice(Order $order): array
    {
        $order->loadMissing('items');

        $contact = $this->createContact($order);
        $details = [];

        foreach ($order->items as $item) {
            $product = $this->createProduct($item->product_name, $item->sku ?: 'SKU-'.$item->id, (float) $item->unit_price);
            $details[] = $this->invoiceDetail((int) $item->quantity, (float) $item->unit_price, $item->product_name, $product['data']['id'] ?? null);
        }

        if ((float) $order->shipping_cost > 0) {
            $product = $this->createProduct('Kargo Bedeli', 'KARGO', (float) $order->shipping_cost);
            $details[] = $this->invoiceDetail(1, (float) $order->shipping_cost, 'Kargo Bedeli', $product['data']['id'] ?? null);
        }

        $invoice = $this->client->post('sales_invoices', [
            'data' => [
                'type' => 'sales_invoices',
                'attributes' => [
                    'item_type' => 'invoice',
                    'description' => 'Koşar Ticaret Siparişi - '.$order->order_number,
                    'issue_date' => now()->toDateString(),
                    'currency' => 'TRL',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'id' => $contact['data']['id'] ?? null,
                            'type' => 'contacts',
                        ],
                    ],
                    'details' => [
                        'data' => $details,
                    ],
                ],
            ],
        ]);

        return [
            'invoice_id' => $invoice['data']['id'] ?? null,
            'response' => $invoice,
        ];
    }

    /** @return array<string, mixed> */
    private function createContact(Order $order): array
    {
        $teslimat = $order->shipping_address['teslimat'] ?? [];
        $kurumsal = $teslimat['kurumsalFatura'] ?? null;
        $isCompany = is_array($kurumsal);
        $name = $isCompany
            ? (string) ($kurumsal['firmaAdi'] ?? $order->customer_name)
            : (string) ($order->customer_name ?: (($teslimat['ad'] ?? '').' '.($teslimat['soyad'] ?? '')));
        $taxNumber = $isCompany
            ? (string) ($kurumsal['vergiNumarasi'] ?? '')
            : '11111111111';

        return $this->client->post('contacts', [
            'data' => [
                'type' => 'contacts',
                'attributes' => array_filter([
                    'email' => $order->email,
                    'name' => trim($name) ?: 'Web müşterisi',
                    'short_name' => trim($name) ?: 'Web müşterisi',
                    'contact_type' => $isCompany ? 'company' : 'person',
                    'account_type' => 'customer',
                    'phone' => $order->phone ?: ($teslimat['telefon'] ?? null),
                    'city' => $teslimat['il'] ?? null,
                    'district' => $teslimat['ilce'] ?? null,
                    'address' => $kurumsal['faturaAdresi'] ?? ($teslimat['adres'] ?? null),
                    'tax_number' => $taxNumber,
                    'tax_office' => $kurumsal['vergiDairesi'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function createProduct(string $name, string $code, float $price): array
    {
        $vatRate = $this->vatRate();

        return $this->client->post('products', [
            'data' => [
                'type' => 'products',
                'attributes' => [
                    'name' => $name,
                    'code' => $code,
                    'unit' => 'Adet',
                    'vat_rate' => $this->parasutVatRate($vatRate),
                    'sales_excise_duty' => 0,
                    'list_price' => $this->priceExcludingVat($price, $vatRate),
                ],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function invoiceDetail(int $quantity, float $unitPrice, string $description, ?string $productId): array
    {
        $vatRate = $this->vatRate();

        $detail = [
            'type' => 'sales_invoice_details',
            'attributes' => [
                'quantity' => $quantity,
                'unit_price' => $this->priceExcludingVat($unitPrice, $vatRate),
                'vat_rate' => $this->parasutVatRate($vatRate),
                'description' => $description,
            ],
        ];

        if ($productId) {
            $detail['relationships'] = [
                'product' => [
                    'data' => [
                        'id' => $productId,
                        'type' => 'products',
                    ],
                ],
            ];
        }

        return $detail;
    }

    private function vatRate(): float
    {
        $rate = max(0, (float) $this->storeConfig->vatRate());

        return $rate > 0 && $rate <= 1 ? $rate * 100 : $rate;
    }

    private function priceExcludingVat(float $priceIncludingVat, float $vatRate): float
    {
        if ($vatRate <= 0) {
            return round($priceIncludingVat, 2);
        }

        return round($priceIncludingVat / (1 + ($vatRate / 100)), 2);
    }

    private function parasutVatRate(float $vatRate): int|float
    {
        $normalized = round($vatRate, 2);

        return floor($normalized) == $normalized ? (int) $normalized : $normalized;
    }
}
