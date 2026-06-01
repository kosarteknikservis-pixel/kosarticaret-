<?php

namespace App\Mail;

use App\Models\EmailCampaign;
use App\Support\EmailTemplateParams;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EmailCampaign $campaign) {}

    /** @return array<string, string> */
    public function params(): array
    {
        return EmailTemplateParams::campaign($this->campaign->title);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->render($this->campaign->subject));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
            with: ['params' => $this->params()],
        );
    }

    public function render(?string $value): string
    {
        $value = (string) $value;

        foreach ($this->params() as $key => $param) {
            $value = str_replace('{{'.$key.'}}', $param, $value);
        }

        return Str::of($value)->replaceMatches('/\{\{[a-zA-Z0-9_]+\}\}/', '')->toString();
    }
}
