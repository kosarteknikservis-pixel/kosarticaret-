<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\Telegram\OrderTelegramNotifier;
use App\Support\TelegramConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelegramIntegrationController extends Controller
{
    public function edit(): View
    {
        return view('admin.integrations.notifications.telegram', [
            'values' => $this->values(),
            'configured' => TelegramConfig::isConfigured(),
            'enabled' => TelegramConfig::isEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'telegram_enabled' => ['sometimes', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:120'],
            'telegram_chat_id' => ['nullable', 'string', 'max:32'],
        ]);

        SiteSetting::set('telegram_enabled', $request->boolean('telegram_enabled') ? '1' : '0');

        if ($request->filled('telegram_bot_token')) {
            SiteSetting::set('telegram_bot_token', trim((string) $data['telegram_bot_token']));
        }

        if ($request->filled('telegram_chat_id')) {
            SiteSetting::set('telegram_chat_id', trim((string) $data['telegram_chat_id']));
        }

        return redirect()
            ->route('admin.integrations.notifications.telegram')
            ->with('success', 'Telegram bildirim ayarları kaydedildi.');
    }

    public function test(OrderTelegramNotifier $notifier): RedirectResponse
    {
        if (! TelegramConfig::isConfigured()) {
            return back()->with('error', 'Önce bot token ve chat ID girin.');
        }

        $result = $notifier->sendTestMessage();

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'Test bildirimi Telegram\'a gönderildi.'
                : ($result['error'] ?? 'Test bildirimi gönderilemedi.'),
        );
    }

    /** @return array<string, string|null> */
    private function values(): array
    {
        return [
            'telegram_enabled' => SiteSetting::get('telegram_enabled'),
            'telegram_bot_token' => SiteSetting::get('telegram_bot_token'),
            'telegram_chat_id' => SiteSetting::get('telegram_chat_id'),
        ];
    }
}
