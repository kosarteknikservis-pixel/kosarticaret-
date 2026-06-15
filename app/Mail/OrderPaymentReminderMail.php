<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Order;
use App\Support\EmailTemplateParams;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function template(): EmailTemplate
    {
        return EmailTemplate::forKey('payment_reminder');
    }

    /** @return array<string, string> */
    public function params(): array
    {
        return EmailTemplateParams::order($this->order);
    }

    public function envelope(): Envelope
    {
        $template = $this->template();

        return new Envelope(
            subject: $template->render('subject', $this->params()),
        );
    }

    public function content(): Content
    {
        $order = $this->order->loadMissing('items');

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'order' => $order,
                'template' => $this->template(),
                'params' => $this->params(),
            ],
        );
    }
}
