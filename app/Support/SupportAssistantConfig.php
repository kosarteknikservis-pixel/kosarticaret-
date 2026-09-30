<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\OpenAiService;
use Illuminate\Support\Str;

class SupportAssistantConfig
{
    public const MAX_USER_MESSAGES_PER_CONVERSATION = 30;

    public const MAX_MESSAGE_LENGTH = 600;

    public static function isEnabled(): bool
    {
        return SiteSetting::get('ai_assistant_enabled', '1') !== '0' && OpenAiService::isConfigured();
    }

    public static function dailyLimit(): int
    {
        $limit = (int) SiteSetting::get('ai_assistant_daily_limit', '400');

        return $limit > 0 ? $limit : 400;
    }

    public static function whatsappDigits(): string
    {
        $raw = (string) SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp'));

        return preg_replace('/\D/', '', $raw) ?? '';
    }

    public static function whatsappUrl(?string $text = null): ?string
    {
        $digits = self::whatsappDigits();
        if ($digits === '') {
            return null;
        }

        $url = 'https://wa.me/'.$digits;
        $text = trim((string) $text);

        return $text !== '' ? $url.'?text='.rawurlencode(Str::limit($text, 900, '…')) : $url;
    }

    public static function contactPhone(): string
    {
        return trim((string) SiteSetting::get('contact_phone', config('kosar.contact.phone')));
    }
}
