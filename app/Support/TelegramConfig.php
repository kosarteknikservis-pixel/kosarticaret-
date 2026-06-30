<?php

namespace App\Support;

use App\Models\SiteSetting;

class TelegramConfig
{
    public static function isEnabled(): bool
    {
        return SiteSetting::get('telegram_enabled', config('services.telegram.enabled') ? '1' : '0') === '1';
    }

    public static function isConfigured(): bool
    {
        return self::botToken() !== null && self::chatId() !== null;
    }

    public static function botToken(): ?string
    {
        $token = trim((string) (SiteSetting::get('telegram_bot_token') ?: config('services.telegram.bot_token', '')));

        return $token !== '' ? $token : null;
    }

    public static function chatId(): ?string
    {
        $chatId = trim((string) (SiteSetting::get('telegram_chat_id') ?: config('services.telegram.chat_id', '')));

        return $chatId !== '' ? $chatId : null;
    }
}
