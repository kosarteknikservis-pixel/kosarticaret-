<?php

namespace App\Support;

use App\Models\SiteSetting;

class MailSettings
{
    public static function isConfigured(): bool
    {
        return SiteSetting::get('smtp_enabled', '0') === '1'
            && self::setting('smtp_host') !== ''
            && self::setting('smtp_port') !== ''
            && self::setting('smtp_from_address') !== '';
    }

    public static function apply(): void
    {
        if (! self::isConfigured()) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => self::setting('smtp_host'),
            'mail.mailers.smtp.port' => (int) self::setting('smtp_port', '587'),
            'mail.mailers.smtp.username' => self::setting('smtp_username') ?: null,
            'mail.mailers.smtp.password' => self::setting('smtp_password') ?: null,
            'mail.mailers.smtp.encryption' => self::setting('smtp_encryption') ?: null,
            'mail.mailers.smtp.scheme' => null,
            'mail.mailers.smtp.url' => null,
            'mail.from.address' => self::setting('smtp_from_address', config('mail.from.address')),
            'mail.from.name' => self::setting('smtp_from_name', config('mail.from.name')),
        ]);
    }

    public static function setting(string $key, ?string $default = ''): string
    {
        return trim((string) SiteSetting::get($key, $default));
    }
}
