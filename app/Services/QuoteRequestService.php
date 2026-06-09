<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteRequestService
{
    public function __construct(private CartService $cart) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Request $request, array $data): ContactMessage
    {
        $lines = $this->cart->lines();
        $subtotal = $this->cart->subtotal();

        $items = array_map(static function (array $line): array {
            $product = $line['product'];

            return [
                'slug' => $product->slug,
                'sku' => $product->sku,
                'name' => $product->name,
                'brand' => $product->brand?->name,
                'quantity' => (int) $line['quantity'],
                'unit_price' => (float) $product->price,
                'line_total' => (float) $line['line_total'],
            ];
        }, $lines);

        $body = $this->formatBody($data, $items, $subtotal);

        $message = ContactMessage::query()->create([
            'type' => 'quote',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? 'Sepet teklif / proforma talebi',
            'body' => $body,
            'meta' => [
                'company' => $data['company'] ?? null,
                'tax_no' => $data['tax_no'] ?? null,
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'items' => $items,
            ],
        ]);

        $this->notifyAdmin($message, $data, $body);

        return $message;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $data
     */
    private function formatBody(array $data, array $items, float $subtotal): string
    {
        $lines = [
            'Teklif / proforma talebi',
            '',
            'Firma: '.($data['company'] ?? '-'),
            'Vergi no: '.($data['tax_no'] ?? '-'),
            '',
            'Ürünler:',
        ];

        foreach ($items as $item) {
            $lines[] = sprintf(
                '- %s x %d = %s ₺ (%s)',
                $item['name'],
                $item['quantity'],
                number_format($item['line_total'], 2, ',', '.'),
                $item['sku'] ?? $item['slug'],
            );
        }

        $lines[] = '';
        $lines[] = 'Ara toplam (KDV hariç tahmini): '.number_format($subtotal, 2, ',', '.').' ₺';
        $lines[] = '';
        $lines[] = 'Not:';
        $lines[] = (string) ($data['note'] ?? '-');

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notifyAdmin(ContactMessage $message, array $data, string $body): void
    {
        $to = SiteSetting::get('contact_email', config('kosar.contact.email'));

        try {
            Mail::raw($body, fn ($m) => $m
                ->to($to)
                ->subject('[Koşar Teklif] '.$message->subject)
                ->replyTo($data['email'], $data['name']));
        } catch (\Throwable $e) {
            Log::info('quote request', ['id' => $message->id, 'email' => $data['email']]);
        }
    }
}
