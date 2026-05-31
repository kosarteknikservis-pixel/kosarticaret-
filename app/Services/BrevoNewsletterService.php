<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoNewsletterService
{
    public function isConfigured(): bool
    {
        return SiteSetting::get('brevo_enabled', '0') === '1'
            && filled(SiteSetting::get('brevo_api_key'))
            && filled(SiteSetting::get('brevo_list_id'));
    }

    public function subscribe(string $email): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $apiKey = (string) SiteSetting::get('brevo_api_key');
        $listId = (int) SiteSetting::get('brevo_list_id');

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])
                ->timeout(10)
                ->post('https://api.brevo.com/v3/contacts', [
                    'email' => $email,
                    'listIds' => [$listId],
                    'updateEnabled' => true,
                ]);

            if ($response->failed()) {
                Log::warning('Brevo newsletter subscribe failed.', [
                    'email' => $email,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Brevo newsletter subscribe exception.', [
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
