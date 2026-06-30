<?php

namespace App\Services\Telegram;

use App\Support\TelegramConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    /** @return array{ok: bool, error?: string, message_id?: int} */
    public function sendMessage(string $text): array
    {
        if (! TelegramConfig::isEnabled()) {
            return ['ok' => false, 'error' => 'Telegram bildirimi kapalı.'];
        }

        $token = TelegramConfig::botToken();
        $chatId = TelegramConfig::chatId();

        if (! $token || ! $chatId) {
            return ['ok' => false, 'error' => 'Telegram bot token veya chat ID eksik.'];
        }

        try {
            $response = Http::timeout(12)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => mb_substr($text, 0, 4096),
                    'disable_web_page_preview' => false,
                ]);

            if (! $response->successful()) {
                $error = data_get($response->json(), 'description') ?: $response->body();

                return ['ok' => false, 'error' => is_string($error) ? $error : 'Telegram API hatası.'];
            }

            return [
                'ok' => true,
                'message_id' => (int) data_get($response->json(), 'result.message_id', 0),
            ];
        } catch (\Throwable $e) {
            Log::warning('Telegram mesajı gönderilemedi', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
