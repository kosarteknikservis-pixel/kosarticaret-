<?php

namespace App\Services\Sms;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MutlucellSmsProvider
{
    /** @return array{ok: bool, error?: string} */
    public function send(string $phone, string $message): array
    {
        $user = trim((string) SiteSetting::get('mutlucell_username', (string) config('carriers.sms.mutlucell.username', '')));
        $pass = trim((string) SiteSetting::get('mutlucell_password', (string) config('carriers.sms.mutlucell.password', '')));
        $header = trim((string) SiteSetting::get('mutlucell_header', (string) config('carriers.sms.mutlucell.header', '')));

        if ($user === '' || $pass === '' || $header === '') {
            return ['ok' => false, 'error' => 'Mutlucell kullanıcı, API anahtarı veya başlık eksik.'];
        }

        $gsm = $this->normalizePhone($phone);
        if ($gsm === '') {
            return ['ok' => false, 'error' => 'Telefon formatı geçersiz.'];
        }

        $message = trim($message);
        if ($message === '') {
            return ['ok' => false, 'error' => 'Mesaj boş.'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<smspack ka="'.htmlspecialchars($user, ENT_QUOTES, 'UTF-8').'"'
            .' pwd="'.htmlspecialchars($pass, ENT_QUOTES, 'UTF-8').'"'
            .' org="'.htmlspecialchars($header, ENT_QUOTES, 'UTF-8').'">'
            .'<mesaj>'
            .'<metin>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</metin>'
            .'<nums>'.htmlspecialchars($gsm, ENT_QUOTES, 'UTF-8').'</nums>'
            .'</mesaj>'
            .'</smspack>';

        try {
            $response = Http::timeout(15)
                ->withBody($xml, 'text/xml; charset=UTF-8')
                ->post('https://smsgw.mutlucell.com/smsgw-ws/sndblkex');

            $body = trim(strip_tags($response->body()));
            if ($body === '') {
                return ['ok' => false, 'error' => 'Mutlucell boş cevap döndü.'];
            }

            if ($this->accepted($body)) {
                return ['ok' => true];
            }

            return ['ok' => false, 'error' => 'Mutlucell yanıtı: '.mb_substr($body, 0, 160)];
        } catch (\Throwable $e) {
            Log::warning('Mutlucell SMS failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function isConfigured(): bool
    {
        $user = trim((string) SiteSetting::get('mutlucell_username', (string) config('carriers.sms.mutlucell.username', '')));
        $pass = trim((string) SiteSetting::get('mutlucell_password', (string) config('carriers.sms.mutlucell.password', '')));
        $header = trim((string) SiteSetting::get('mutlucell_header', (string) config('carriers.sms.mutlucell.header', '')));

        return $user !== '' && $pass !== '' && $header !== '';
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '90'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '05')) {
            return '9'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '90')) {
            return $digits;
        }

        return '';
    }

    private function accepted(string $body): bool
    {
        $compact = preg_replace('/\s+/', '', $body) ?? '';

        $numeric = preg_match('/^\d+$/', $compact) === 1 && (int) $compact > 1000;
        $dollar = str_contains($compact, '$');
        $suffix = preg_match('/[;:]0$/', $compact) === 1;

        return $numeric || $dollar || $suffix;
    }
}
