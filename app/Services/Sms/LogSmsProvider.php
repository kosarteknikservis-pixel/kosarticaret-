<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsProvider
{
    /** @return array{ok: bool} */
    public function send(string $phone, string $message): array
    {
        Log::info('SMS (log provider)', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return ['ok' => true];
    }
}
