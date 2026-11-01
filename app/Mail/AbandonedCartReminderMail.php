<?php

namespace App\Mail;

use App\Models\AbandonedCart;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AbandonedCart $cart) {}

    public function template(): EmailTemplate
    {
        return EmailTemplate::forKey('abandoned_cart');
    }

    /** @return array<string, string> */
    public function params(): array
    {
        $name = $this->cart->customer_name ?: 'Değerli müşterimiz';
        $total = number_format((float) $this->cart->subtotal, 2, ',', '.').' ₺';
        $items = collect($this->cart->items ?? [])
            ->map(fn (array $item) => ($item['name'] ?? 'Ürün').' × '.($item['quantity'] ?? 1))
            ->implode("\n");

        return [
            'customer_name' => $name,
            'cart_total' => $total,
            'cart_items' => $items !== '' ? $items : 'Sepetinizdeki ürünler',
            'cart_url' => route('cart.index', absolute: true),
            'checkout_url' => route('checkout.show', absolute: true),
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template()->render('subject', $this->params()),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abandoned-cart',
            with: [
                'cart' => $this->cart,
                'template' => $this->template(),
                'params' => $this->params(),
            ],
        );
    }
}
