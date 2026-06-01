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

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function template(): EmailTemplate
    {
        return EmailTemplate::forKey('order_status_updated');
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
        return new Content(
            view: 'emails.order-status-updated',
            with: [
                'template' => $this->template(),
                'params' => $this->params(),
            ],
        );
    }
}
