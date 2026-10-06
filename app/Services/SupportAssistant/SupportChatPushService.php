<?php

namespace App\Services\SupportAssistant;

use App\Models\SiteSetting;
use App\Models\SupportChatConversation;
use App\Models\SupportChatMessage;
use App\Models\SupportChatPushSubscription;
use App\Support\SiteFavicon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Throwable;

class SupportChatPushService
{
    public const PUBLIC_KEY_SETTING = 'support_push_public_key';

    public const PRIVATE_KEY_SETTING = 'support_push_private_key';

    /** Tarayıcıların push servisleri; sunucu yalnızca bu adreslere istek atar. */
    private const ALLOWED_HOSTS = [
        'fcm.googleapis.com',
        'android.googleapis.com',
        'push.services.mozilla.com',
        'notify.windows.com',
        'push.apple.com',
    ];

    public function publicKey(): ?string
    {
        try {
            return $this->ensureKeys();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function ensureKeys(): string
    {
        $public = SiteSetting::get(self::PUBLIC_KEY_SETTING);
        $private = SiteSetting::get(self::PRIVATE_KEY_SETTING);
        if (filled($public) && filled($private)) {
            return $public;
        }

        $keys = VAPID::createVapidKeys();
        SiteSetting::set(self::PRIVATE_KEY_SETTING, $keys['privateKey']);
        SiteSetting::set(self::PUBLIC_KEY_SETTING, $keys['publicKey']);

        return $keys['publicKey'];
    }

    public static function isAllowedEndpoint(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['port'])) {
            return false;
        }

        $host = strtolower($parts['host']);
        foreach (self::ALLOWED_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{endpoint: string, p256dh: string, auth: string, encoding: string}  $data
     */
    public function subscribe(string $visitorToken, array $data): SupportChatPushSubscription
    {
        return SupportChatPushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'visitor_token' => $visitorToken,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['p256dh'],
                'auth_token' => $data['auth'],
                'content_encoding' => $data['encoding'],
            ],
        );
    }

    public function hasSubscription(?string $visitorToken): bool
    {
        return filled($visitorToken)
            && SupportChatPushSubscription::query()->where('visitor_token', $visitorToken)->exists();
    }

    /**
     * @return array{subscribers: int, sent: int, failed: int}
     */
    public function notify(SupportChatConversation $conversation, SupportChatMessage $message): array
    {
        $result = ['subscribers' => 0, 'sent' => 0, 'failed' => 0];
        if (! $conversation->canReceiveAgentReply()) {
            return $result;
        }

        $subscriptions = $conversation->pushSubscriptions()->limit(10)->get();
        $result['subscribers'] = $subscriptions->count();
        $publicKey = $this->publicKey();
        if ($subscriptions->isEmpty() || $publicKey === null) {
            return $result;
        }

        $payload = json_encode([
            'title' => 'Koşar Ticaret yanıt verdi',
            'body' => Str::limit(preg_replace('/\s+/u', ' ', $message->content) ?? '', 140, '…'),
            'url' => url('/').'#destek-asistani',
            'icon' => url(SiteFavicon::appleTouchUrl()),
            'tag' => 'kc-support-'.$conversation->id,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $webPush = $this->client($publicKey);
            $byEndpoint = [];
            foreach ($subscriptions as $subscription) {
                if (! self::isAllowedEndpoint($subscription->endpoint)) {
                    $subscription->delete();
                    $result['failed']++;

                    continue;
                }
                $byEndpoint[$subscription->endpoint] = $subscription;
                $webPush->queueNotification(new Subscription(
                    $subscription->endpoint,
                    $subscription->public_key,
                    $subscription->auth_token,
                    $subscription->content_encoding,
                ), $payload);
            }

            foreach ($webPush->flush() as $report) {
                $subscription = $byEndpoint[$report->getEndpoint()] ?? null;
                if ($report->isSuccess()) {
                    $result['sent']++;
                    $subscription?->forceFill(['last_used_at' => now()])->save();
                } else {
                    $result['failed']++;
                    if ($report->isSubscriptionExpired()) {
                        $subscription?->delete();
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
            $result['failed'] = $result['subscribers'] - $result['sent'];
        }

        return $result;
    }

    protected function client(string $publicKey): WebPush
    {
        $factory = new HttpFactory;
        $subject = rtrim((string) config('app.url'), '/');

        return new WebPush(
            ['VAPID' => [
                'subject' => str_starts_with($subject, 'https://') ? $subject : 'https://kosarticaret.com',
                'publicKey' => $publicKey,
                'privateKey' => (string) SiteSetting::get(self::PRIVATE_KEY_SETTING),
            ]],
            ['TTL' => 172800, 'urgency' => 'normal'],
            new Client(['timeout' => 8, 'connect_timeout' => 4]),
            $factory,
            $factory,
        );
    }
}
