<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ContactFormSpamGuard
{
    private const MIN_SECONDS = 3;

    /** @var array<string, list<string>> */
    private const TEXT_FIELDS = [
        'contact' => ['ad_soyad', 'eposta', 'konu', 'mesaj'],
        'review' => ['author_name', 'email', 'title', 'body'],
        'quote' => ['name', 'email', 'company', 'note'],
    ];

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
        'whatsapp marketing',
        'telegram channel',
        'forex signal',
        'loan offer',
        'click here to',
        'make money online',
        'work from home',
        'bahis sit',
        'canlı bahis',
        'escort',
        'kumar',
        'viagra',
        'cialis',
    ];

    /** @var list<string> */
    private const BOT_USER_AGENTS = [
        'python-requests',
        'python-urllib',
        'curl/',
        'wget/',
        'scrapy',
        'httpclient',
        'libwww-perl',
        'go-http-client',
        'java/',
        'apache-httpclient',
        'semrush',
        'ahrefs',
        'mj12bot',
        'petalbot',
    ];

    public static function beginForm(string $context = 'contact'): string
    {
        $token = Str::random(40);
        session([
            self::sessionKey($context, 'token') => $token,
            self::sessionKey($context, 'started_at') => now()->timestamp,
        ]);

        return $token;
    }

    public static function turnstileEnabled(): bool
    {
        return self::siteKey() !== '' && self::secretKey() !== '';
    }

    public static function siteKey(): string
    {
        $fromDb = trim((string) SiteSetting::get('turnstile_site_key', ''));

        return $fromDb !== '' ? $fromDb : trim((string) config('kosar.turnstile.site_key', ''));
    }

    public static function secretKey(): string
    {
        $fromDb = trim((string) SiteSetting::get('turnstile_secret_key', ''));

        return $fromDb !== '' ? $fromDb : trim((string) config('kosar.turnstile.secret_key', ''));
    }

    /**
     * @return array{blocked: bool, reason: string|null, silent: bool, message: string|null}
     */
    public static function assess(Request $request, string $context = 'contact'): array
    {
        if (filled($request->input('website_url'))) {
            return self::block('honeypot', true);
        }

        if (self::suspiciousClient($request)) {
            return self::block('client', true);
        }

        if (! self::timingValid($request, $context)) {
            return self::block('timing', true);
        }

        $text = self::combinedText($request, $context);

        if (self::containsSpamPattern($text)) {
            return self::block('keyword', true);
        }

        if (self::hasTooManyLinks($text)) {
            return self::block('links', true);
        }

        if (self::turnstileEnabled()) {
            $turnstile = self::turnstileValid($request);
            if (! $turnstile['valid']) {
                return self::block('turnstile', false, $turnstile['message']);
            }
        }

        return ['blocked' => false, 'reason' => null, 'silent' => false, 'message' => null];
    }

    /** @return array{blocked: bool, reason: string, silent: bool, message: string|null} */
    private static function block(string $reason, bool $silent, ?string $message = null): array
    {
        return [
            'blocked' => true,
            'reason' => $reason,
            'silent' => $silent,
            'message' => $message,
        ];
    }

    public static function clearFormSession(string $context = 'contact'): void
    {
        session()->forget([
            self::sessionKey($context, 'token'),
            self::sessionKey($context, 'started_at'),
        ]);
    }

    private static function sessionKey(string $context, string $suffix): string
    {
        return "public_form.{$context}.{$suffix}";
    }

    private static function timingValid(Request $request, string $context): bool
    {
        $token = (string) session(self::sessionKey($context, 'token'), '');
        $startedAt = (int) session(self::sessionKey($context, 'started_at'), 0);
        $submittedToken = (string) $request->input('_form_token', '');

        if ($token === '' || $startedAt <= 0 || ! hash_equals($token, $submittedToken)) {
            return false;
        }

        return (now()->timestamp - $startedAt) >= self::MIN_SECONDS;
    }

    private static function combinedText(Request $request, string $context): string
    {
        $fields = self::TEXT_FIELDS[$context] ?? [];

        $parts = array_map(
            static fn (string $field): string => (string) $request->input($field, ''),
            $fields,
        );

        return Str::lower(implode(' ', array_filter($parts)));
    }

    private static function suspiciousClient(Request $request): bool
    {
        $ua = Str::lower(trim((string) $request->userAgent()));

        if ($ua === '') {
            return true;
        }

        foreach (self::BOT_USER_AGENTS as $signature) {
            if (str_contains($ua, $signature)) {
                return true;
            }
        }

        return false;
    }

    private static function containsSpamPattern(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        foreach (self::SPAM_PATTERNS as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        if (preg_match('/\b(promotion|marketing)\b.+\b(for|your)\b.+\b(site|website|domain)\b/i', $text)) {
            return true;
        }

        if (preg_match('/\b(https?:\/\/|www\.)\S+/i', $text) && preg_match_all('/\b(https?:\/\/|www\.)\S+/i', $text) >= 3) {
            return true;
        }

        return false;
    }

    private static function hasTooManyLinks(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        return preg_match_all('/https?:\/\/[^\s]+/i', $text) >= 2;
    }

    /** @return array{valid: bool, message: string|null} */
    private static function turnstileValid(Request $request): array
    {
        $response = trim((string) $request->input('cf-turnstile-response', ''));
        if ($response === '') {
            return [
                'valid' => false,
                'message' => 'Güvenlik doğrulaması tamamlanmadı. Lütfen kutucuğu işaretleyip tekrar deneyin.',
            ];
        }

        try {
            $verify = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => self::secretKey(),
                    'response' => $response,
                    'remoteip' => $request->ip(),
                ]);

            if (! $verify->successful()) {
                Log::warning('turnstile http failed', ['status' => $verify->status()]);

                return [
                    'valid' => false,
                    'message' => 'Güvenlik doğrulaması şu an yapılamıyor. Lütfen biraz sonra tekrar deneyin.',
                ];
            }

            $payload = $verify->json();
            if ((bool) ($payload['success'] ?? false)) {
                return ['valid' => true, 'message' => null];
            }

            Log::warning('turnstile verify rejected', [
                'errors' => $payload['error-codes'] ?? [],
                'ip' => $request->ip(),
            ]);

            return [
                'valid' => false,
                'message' => 'Güvenlik doğrulaması geçersiz. Sayfayı yenileyip tekrar deneyin. Cloudflare anahtarlarınızın kosarticaret.com için tanımlı olduğundan emin olun.',
            ];
        } catch (\Throwable $e) {
            Log::warning('turnstile verify exception', ['error' => $e->getMessage()]);

            return [
                'valid' => false,
                'message' => 'Güvenlik doğrulaması şu an yapılamıyor. Lütfen biraz sonra tekrar deneyin.',
            ];
        }
    }
}
