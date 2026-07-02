<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    /** @var list<string> */
    private const DISPOSABLE_EMAIL_DOMAINS = [
        'mailinator.com',
        'guerrillamail.com',
        'guerrillamail.net',
        'tempmail.com',
        'temp-mail.org',
        'yopmail.com',
        '10minutemail.com',
        'throwaway.email',
        'getnada.com',
        'sharklasers.com',
        'dispostable.com',
        'maildrop.cc',
        'trashmail.com',
    ];

    /** @var array<string, array{0: int, 1: int}> max attempts, window seconds */
    private const RATE_LIMITS = [
        'contact' => [3, 3600],
        'review' => [5, 86400],
        'quote' => [3, 3600],
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
        $siteKey = self::siteKey();
        $secretKey = self::secretKey();

        if ($siteKey === '' || $secretKey === '') {
            return false;
        }

        return self::looksLikeTurnstileSiteKey($siteKey) && self::looksLikeTurnstileSecretKey($secretKey);
    }

    public static function turnstileMisconfigured(): bool
    {
        $siteKey = self::siteKey();
        $secretKey = self::secretKey();

        if ($siteKey === '' && $secretKey === '') {
            return false;
        }

        return ! self::turnstileEnabled();
    }

    public static function looksLikeTurnstileSiteKey(string $key): bool
    {
        $key = trim($key);

        // Cloudflare Turnstile site keys: 0x4AAA... veya test anahtarları 1x000...
        return (bool) preg_match('/^(0x|1x|2x|3x)[A-Za-z0-9_-]{10,}$/', $key);
    }

    public static function looksLikeTurnstileSecretKey(string $key): bool
    {
        $key = trim($key);

        return (bool) preg_match('/^(0x|1x|2x|3x)[A-Za-z0-9_-]{10,}$/', $key);
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

        $rateLimit = self::assessRateLimit($request, $context);
        if ($rateLimit !== null) {
            return $rateLimit;
        }

        if (self::turnstileEnabled()) {
            $turnstile = self::turnstileValid($request);
            if (! $turnstile['valid']) {
                return self::block('turnstile', false, $turnstile['message']);
            }
        }

        return ['blocked' => false, 'reason' => null, 'silent' => false, 'message' => null];
    }

    /**
     * İçerik doğrulaması — geçersiz gönderiler veritabanına yazılmaz (panelde birikmez).
     *
     * @param  array<string, mixed>  $data
     * @return array{blocked: bool, reason: string|null, silent: bool, message: string|null}
     */
    public static function assessContent(string $context, array $data): array
    {
        $email = Str::lower(trim((string) ($data['email'] ?? $data['eposta'] ?? '')));
        if ($email !== '' && self::isDisposableEmail($email)) {
            return self::block('disposable_email', true);
        }

        if ($context === 'review') {
            return self::assessReviewContent($data);
        }

        if ($context === 'contact') {
            return self::assessContactContent($data);
        }

        if ($context === 'quote') {
            return self::assessQuoteContent($data);
        }

        return ['blocked' => false, 'reason' => null, 'silent' => false, 'message' => null];
    }

    /** @param  array<string, mixed>  $data */
    private static function assessReviewContent(array $data): array
    {
        $body = trim((string) ($data['body'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $name = trim((string) ($data['author_name'] ?? ''));

        if (mb_strlen($body) < 20) {
            return self::block('review_short', true);
        }

        if (self::meaningfulWordCount($body) < 4) {
            return self::block('review_words', true);
        }

        if (self::looksLikeGibberish($body) || ($title !== '' && self::looksLikeGibberish($title)) || self::looksLikeGibberish($name)) {
            return self::block('gibberish', true);
        }

        if (self::containsSpamPattern(Str::lower($body.' '.$title.' '.$name))) {
            return self::block('keyword', true);
        }

        return ['blocked' => false, 'reason' => null, 'silent' => false, 'message' => null];
    }

    /** @param  array<string, mixed>  $data */
    private static function assessContactContent(array $data): array
    {
        $message = trim((string) ($data['mesaj'] ?? ''));
        $subject = trim((string) ($data['konu'] ?? ''));
        $name = trim((string) ($data['ad_soyad'] ?? ''));

        if (mb_strlen($message) < 10) {
            return self::block('contact_short', true);
        }

        if (self::looksLikeGibberish($message) || self::looksLikeGibberish($subject) || self::looksLikeGibberish($name)) {
            return self::block('gibberish', true);
        }

        return ['blocked' => false, 'reason' => null, 'silent' => false, 'message' => null];
    }

    /** @param  array<string, mixed>  $data */
    private static function assessQuoteContent(array $data): array
    {
        $note = trim((string) ($data['note'] ?? ''));
        if ($note !== '' && (self::looksLikeGibberish($note) || self::containsSpamPattern(Str::lower($note)))) {
            return self::block('gibberish', true);
        }

        return ['blocked' => false, 'reason' => null, 'silent' => false, 'message' => null];
    }

    /** @return array{blocked: bool, reason: string, silent: bool, message: string|null}|null */
    private static function assessRateLimit(Request $request, string $context): ?array
    {
        $config = self::RATE_LIMITS[$context] ?? null;
        if ($config === null) {
            return null;
        }

        [$maxAttempts, $windowSeconds] = $config;
        $ip = (string) $request->ip();
        if ($ip === '') {
            return null;
        }

        $key = "public_form_rate:{$context}:{$ip}";
        $count = (int) Cache::get($key, 0);
        if ($count >= $maxAttempts) {
            return self::block('rate_limit', true);
        }

        Cache::put($key, $count + 1, now()->addSeconds($windowSeconds));

        return null;
    }

    private static function isDisposableEmail(string $email): bool
    {
        $domain = Str::lower(Str::after($email, '@'));
        if ($domain === '') {
            return false;
        }

        if (in_array($domain, self::DISPOSABLE_EMAIL_DOMAINS, true)) {
            return true;
        }

        return str_contains($domain, 'tempmail')
            || str_contains($domain, 'throwaway')
            || str_contains($domain, 'fakeinbox')
            || str_contains($domain, 'mailinator');
    }

    private static function looksLikeGibberish(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        $lower = Str::lower($text);

        if (preg_match('/(.{2,6})\1{2,}/u', $lower)) {
            return true;
        }

        if (preg_match('/^(asdf|qwer|zxcv|asd{3,}|sdf{3,}|test{3,}|deneme{3,})\b/u', $lower)) {
            return true;
        }

        if (preg_match('/^[a-z]{1,2}(\s+[a-z]{1,2}){4,}$/u', $lower)) {
            return true;
        }

        $chars = mb_str_split(preg_replace('/\s+/u', '', $lower) ?? '');
        $length = count($chars);
        if ($length >= 12) {
            $uniqueRatio = count(array_unique($chars)) / $length;
            if ($uniqueRatio < 0.35) {
                return true;
            }
        }

        return false;
    }

    private static function meaningfulWordCount(string $text): int
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count(array_filter($words, static fn (string $word): bool => mb_strlen($word) >= 2));
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
