<?php

namespace App\Services\Sms;

use App\Models\SiteSetting;

class SmsService
{
    public function __construct(
        private NetgsmSmsProvider $netgsm,
        private LogSmsProvider $logProvider,
    ) {}

    public function isEnabled(): bool
    {
        return SiteSetting::get('sms_enabled', config('carriers.sms.enabled') ? '1' : '0') === '1';
    }

    /** @return array{ok: bool, error?: string} */
    public function send(string $phone, string $message): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => false, 'error' => 'SMS gönderimi kapalı.'];
        }

        $provider = SiteSetting::get('sms_provider', config('carriers.sms.provider', 'log'));

        return match ($provider) {
            'netgsm' => $this->netgsm->send($phone, $message),
            default => $this->logProvider->send($phone, $message),
        };
    }

    public function trackingMessage(string $customerName, string $orderNumber, string $tracking): string
    {
        $template = (string) SiteSetting::get(
            'sms_tracking_template',
            config('carriers.sms.tracking_template'),
        );

        return strtr($template, [
            '{customer}' => $customerName,
            '{order_number}' => $orderNumber,
            '{tracking}' => $tracking,
            '{site}' => parse_url(config('kosar.url', config('app.url')), PHP_URL_HOST) ?: 'kosarticaret.com',
        ]);
    }
}
