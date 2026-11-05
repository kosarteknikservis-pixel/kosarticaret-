<?php

namespace App\Services\Checkout;

use App\Models\SiteSetting;
use App\Services\CartService;
use App\Services\Sms\MutlucellSmsProvider;

class CodSmsVerification
{
    public const SETTING = 'cod_sms_verify_enabled';

    public const SESSION_KEY = 'checkout_cod_otp';

    private const TTL = 300;

    private const MAX_SENDS = 3;

    private const MAX_ATTEMPTS = 5;

    private const RESEND_WAIT = 60;

    public function __construct(private MutlucellSmsProvider $sms) {}

    public function enabled(): bool
    {
        return SiteSetting::get(self::SETTING, '0') === '1';
    }

    public function requiredFor(string $paymentMethod): bool
    {
        return $paymentMethod === 'kapida_odeme' && $this->enabled();
    }

    public function gatewayReady(): bool
    {
        return $this->sms->isConfigured();
    }

    public function normalizePhone(string $phone): string
    {
        $gsm = $this->sms->normalizePhone($phone);

        return strlen($gsm) === 12 ? substr($gsm, 2) : '';
    }

    public function mask(string $phone): string
    {
        $digits = $this->normalizePhone($phone);
        if ($digits === '') {
            return '***';
        }

        return substr($digits, 0, 3).'***'.substr($digits, -3);
    }

    /** @return array{ok: bool, error?: string} */
    public function deliver(string $phone, string $code): array
    {
        return $this->sms->send(
            $phone,
            'Siparisinizi tamamlamak icin kodunuz: '.$code.' (5 dk gecerli).',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function payloadHash(array $data, CartService $cart): string
    {
        $lines = collect($cart->lines())
            ->map(fn (array $line) => $line['product']->id.':'.$line['quantity'])
            ->sort()
            ->values()
            ->all();

        return hash('sha256', json_encode([
            $this->normalizePhone((string) ($data['telefon'] ?? '')),
            (string) ($data['odeme_yontemi'] ?? ''),
            (string) ($data['kargo_yontemi'] ?? ''),
            (string) ($data['ad'] ?? ''),
            (string) ($data['soyad'] ?? ''),
            (string) ($data['eposta'] ?? ''),
            (string) ($data['il'] ?? ''),
            (string) ($data['ilce'] ?? ''),
            (string) ($data['adres'] ?? ''),
            $lines,
        ], JSON_UNESCAPED_UNICODE));
    }

    /** @return array{masked: string}|null */
    public function pendingForView(): ?array
    {
        $pending = session(self::SESSION_KEY);
        if (! is_array($pending) || (int) ($pending['expires_at'] ?? 0) < time()) {
            return null;
        }

        return ['masked' => $this->mask((string) ($pending['phone'] ?? ''))];
    }

    /** @return array{ok: bool, message: string} */
    public function begin(string $phone, string $payloadHash): array
    {
        $pending = session(self::SESSION_KEY);
        if (is_array($pending)
            && ($pending['phone'] ?? '') === $phone
            && ($pending['payload_hash'] ?? '') === $payloadHash
            && (int) ($pending['expires_at'] ?? 0) >= time()
        ) {
            return [
                'ok' => true,
                'message' => 'Doğrulama kodu '.$this->mask($phone).' numarasına gönderildi.',
            ];
        }

        return $this->issue($phone, $payloadHash, false);
    }

    /** @return array{ok: bool, message: string} */
    public function resend(string $phone, string $payloadHash): array
    {
        $pending = session(self::SESSION_KEY);
        if (! is_array($pending) || ($pending['payload_hash'] ?? '') !== $payloadHash || ($pending['phone'] ?? '') !== $phone) {
            return $this->issue($phone, $payloadHash, false);
        }

        if (time() - (int) ($pending['sent_at'] ?? 0) < self::RESEND_WAIT) {
            return ['ok' => false, 'message' => 'Yeni kod için 60 saniye bekleyin.'];
        }

        if ((int) ($pending['sends'] ?? 0) >= self::MAX_SENDS) {
            return ['ok' => false, 'message' => 'Kod gönderim sınırına ulaşıldı. Bir süre sonra tekrar deneyin.'];
        }

        return $this->issue($phone, $payloadHash, true);
    }

    /** @return array{ok: bool, message: string} */
    public function verify(string $code, string $phone, string $payloadHash): array
    {
        $pending = session(self::SESSION_KEY);
        if (! is_array($pending)) {
            return ['ok' => false, 'message' => 'Önce telefonunuza gelen kodu isteyin.'];
        }

        if ((int) ($pending['expires_at'] ?? 0) < time()) {
            session()->forget(self::SESSION_KEY);

            return ['ok' => false, 'message' => 'Kodun süresi doldu. Yeni kod gönderin.'];
        }

        if (($pending['phone'] ?? '') !== $phone || ($pending['payload_hash'] ?? '') !== $payloadHash) {
            session()->forget(self::SESSION_KEY);

            return ['ok' => false, 'message' => 'Bilgiler değişti. Yeni kod gönderin.'];
        }

        if ((int) ($pending['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            session()->forget(self::SESSION_KEY);

            return ['ok' => false, 'message' => 'Çok fazla hatalı deneme. Bir süre sonra tekrar deneyin.'];
        }

        $digits = preg_replace('/\D+/', '', $code) ?? '';
        if (! hash_equals((string) ($pending['hash'] ?? ''), hash('sha256', $digits))) {
            $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            session()->put(self::SESSION_KEY, $pending);

            return ['ok' => false, 'message' => 'Kod hatalı. Telefonunuza gelen 6 haneli kodu tekrar yazın.'];
        }

        session()->forget(self::SESSION_KEY);

        return ['ok' => true, 'message' => ''];
    }

    /** @return array{ok: bool, message: string} */
    private function issue(string $phone, string $payloadHash, bool $isResend): array
    {
        if (! $this->gatewayReady()) {
            return ['ok' => false, 'message' => 'SMS doğrulama şu an kullanılamıyor. Lütfen başka bir ödeme yöntemi seçin veya bizimle iletişime geçin.'];
        }

        $pending = (array) session(self::SESSION_KEY, []);
        $sends = $isResend ? (int) ($pending['sends'] ?? 0) : 0;
        if ($sends >= self::MAX_SENDS) {
            return ['ok' => false, 'message' => 'Kod gönderim sınırına ulaşıldı. Bir süre sonra tekrar deneyin.'];
        }

        $code = (string) random_int(100000, 999999);
        $sent = $this->deliver($phone, $code);
        if (! ($sent['ok'] ?? false)) {
            return ['ok' => false, 'message' => 'Doğrulama SMS\'i gönderilemedi. Lütfen tekrar deneyin.'];
        }

        session()->put(self::SESSION_KEY, [
            'hash' => hash('sha256', $code),
            'phone' => $phone,
            'payload_hash' => $payloadHash,
            'expires_at' => time() + self::TTL,
            'sent_at' => time(),
            'sends' => $sends + 1,
            'attempts' => 0,
        ]);

        return [
            'ok' => true,
            'message' => 'Doğrulama kodu '.$this->mask($phone).' numarasına gönderildi. Kod 5 dakika geçerlidir.',
        ];
    }
}
