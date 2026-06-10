<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class ContactFormSpamGuard
{
    private const MIN_SECONDS = 3;

    /** @var list<string> */
    private const SPAM_PATTERNS = [
        'video promotion',
        'seo service',
        'search engine optimization',
        'backlink',
        'guest post',
        'link building',
        'rank your site',
        'google ranking',
        'increase traffic',
        'web design offer',
        'digital marketing agency',
        'cryptocurrency',
        'bitcoin',
        'casino',
        'viagra',
        'buy followers',
    ];

    public static function beginForm(): string
    {
        $token = Str::random(40);
        session([
            'contact_form_token' => $token,
            'contact_form_started_at' => now()->timestamp,
        ]);

        return $token;
    }

    public static function turnstileEnabled(): bool
    {
        $siteKey = (string) config('kosar.turnstile.site_key', '');
        $secretKey = (string) config('kosar.turnstile.secret_key', '');

        return $siteKey !== '' && $secretKey !== '';
    }

    public static function siteKey(): string
    {
        return (string) config('kosar.turnstile.site_key', '');
    }

    /**
     * @return array{blocked: bool, reason: string|null, silent: bool}
     */
    public static function assess(Request $request): array
    {
        if (filled($request->input('website_url'))) {
            return ['blocked' => true, 'reason' => 'honeypot', 'silent' => true];
        }

        if (! self::timingValid($request)) {
            return ['blocked' => true, 'reason' => 'timing', 'silent' => true];
        }

        if (self::containsSpamPattern(self::combinedText($request))) {
            return ['blocked' => true, 'reason' => 'keyword', 'silent' => true];
        }

        if (self::turnstileEnabled() && ! self::turnstileValid($request)) {
            return ['blocked' => true, 'reason' => 'turnstile', 'silent' => false];
        }

        return ['blocked' => false, 'reason' => null, 'silent' => false];
    }

    public static function clearFormSession(): void
    {
        session()->forget(['contact_form_token', 'contact_form_started_at']);
    }

    private static function timingValid(Request $request): bool
    {
        $token = (string) session('contact_form_token', '');
        $startedAt = (int) session('contact_form_started_at', 0);
        $submittedToken = (string) $request->input('_form_token', '');

        if ($token === '' || $startedAt <= 0 || ! hash_equals($token, $submittedToken)) {
            return false;
        }

        return (now()->timestamp - $startedAt) >= self::MIN_SECONDS;
    }

    private static function combinedText(Request $request): string
    {
        return Str::lower(implode(' ', array_filter([
            (string) $request->input('ad_soyad', ''),
            (string) $request->input('eposta', ''),
            (string) $request->input('konu', ''),
            (string) $request->input('mesaj', ''),
        ])));
    }

    private static function containsSpamPattern(string $text): bool
    {
        foreach (self::SPAM_PATTERNS as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        if (preg_match('/\b(promotion|marketing)\b.+\b(for|your)\b.+\b(site|website|domain)\b/i', $text)) {
            return true;
        }

        return false;
    }

    private static function turnstileValid(Request $request): bool
    {
        $response = (string) $request->input('cf-turnstile-response', '');
        if ($response === '') {
            return false;
        }

        try {
            $verify = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => (string) config('kosar.turnstile.secret_key', ''),
                    'response' => $response,
                    'remoteip' => $request->ip(),
                ]);

            if (! $verify->successful()) {
                return false;
            }

            return (bool) $verify->json('success');
        } catch (\Throwable) {
            return false;
        }
    }
}
