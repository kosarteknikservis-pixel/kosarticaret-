<?php

namespace App\Services\Sms;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetgsmSmsProvider
{
    /** @return array{ok: bool, error?: string} */
    public function send(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            return ['ok' => false, 'error' => 'Geçersiz telefon numarası.'];
        }

        $usercode = (string) SiteSetting::get('netgsm_usercode', config('carriers.sms.netgsm.usercode'));
        $password = (string) SiteSetting::get('netgsm_password', config('carriers.sms.netgsm.password'));
        $header = (string) SiteSetting::get('netgsm_header', config('carriers.sms.netgsm.header'));

        if ($usercode === '' || $password === '') {
            return ['ok' => false, 'error' => 'Netgsm ayarları eksik.'];
        }

        try {
            $response = Http::timeout(20)->get('https://api.netgsm.com.tr/sms/send/get', [
                'usercode' => $usercode,
                'password' => $password,
                'gsmno' => $phone,
                'message' => $message,
                'msgheader' => $header,
                'dil' => 'TR',
            ]);

            $body = trim($response->body());
            if (str_starts_with($body, '00')) {
                return ['ok' => true];
            }

            return ['ok' => false, 'error' => 'Netgsm yanıtı: '.$body];
        } catch (\Throwable $e) {
            Log::warning('Netgsm SMS failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '90')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '9'.$digits;
        }

        if (strlen($digits) === 10) {
            return '90'.$digits;
        }

        return $digits;
    }
}
