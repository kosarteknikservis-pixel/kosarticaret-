<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\SupportChatConversation;
use App\Models\SupportChatMessage;
use App\Services\SupportAssistant\SupportAssistantService;
use App\Services\SupportAssistant\SupportAssistantOrderFlow;
use App\Services\SupportAssistant\SupportChatPushService;
use Illuminate\Http\RedirectResponse;
use App\Support\SupportAssistantConfig;
use App\Support\Utf8Mojibake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class SupportChatController extends Controller
{
    private const SESSION_KEY = 'support_chat_uuid';

    private const SESSION_ORDER_LOOKUPS = 'support_chat_order_lookups';

    public const VISITOR_COOKIE = 'kc_chat_vid';

    private const VISITOR_COOKIE_MINUTES = 60 * 24 * 30;

    private const REPLY_WINDOW_DAYS = 30;

    public function message(Request $request, SupportAssistantService $assistant, SupportChatPushService $push): JsonResponse
    {
        abort_unless(SupportAssistantConfig::isEnabled(), 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.SupportAssistantConfig::MAX_MESSAGE_LENGTH],
            'page' => ['nullable', 'string', 'max:500'],
        ]);

        $text = $this->maskCardNumbers(trim((string) Utf8Mojibake::repair(strip_tags($data['message']))));
        if ($text === '') {
            return $this->json(['message' => 'Mesaj boş olamaz.'], 422);
        }

        $conversation = $this->conversation($request, $data['page'] ?? null);
        $result = $assistant->reply(
            $conversation,
            $text,
            $data['page'] ?? null,
            (int) $request->session()->get(self::SESSION_ORDER_LOOKUPS, 0),
        );
        $request->session()->put(self::SESSION_ORDER_LOOKUPS, $result['order_lookups']);
        unset($result['order_lookups']);
        $result['notify_offer'] = $result['handoff']
            && $push->publicKey() !== null
            && ! $push->hasSubscription($conversation->visitor_token);

        return $this->json($result);
    }

    /**
     * Mağaza temsilcisinin bu ziyaretçiye yazdığı, henüz görülmemiş yanıtlar.
     */
    public function replies(Request $request): JsonResponse
    {
        [$token, $uuid] = $this->replyAudience($request);
        if ($token === null && $uuid === null) {
            return $this->json(['unread' => 0, 'messages' => []]);
        }

        $messages = $this->unseenReplies($token, $uuid)
            ->map(fn (SupportChatMessage $message) => [
                'id' => $message->id,
                'text' => $message->content,
                'at' => $message->created_at?->format('d.m.Y H:i'),
                'question' => Str::limit((string) SupportChatMessage::query()
                    ->where('conversation_id', $message->conversation_id)
                    ->where('role', 'user')
                    ->where('id', '<', $message->id)
                    ->latest('id')
                    ->value('content'), 140, '…'),
            ])
            ->values();

        return $this->json(['unread' => $messages->count(), 'messages' => $messages]);
    }

    public function markRepliesSeen(Request $request): JsonResponse
    {
        $data = $request->validate(['last_id' => ['required', 'integer', 'min:1']]);
        [$token, $uuid] = $this->replyAudience($request);
        if ($token === null && $uuid === null) {
            return $this->json(['ok' => true]);
        }

        $latest = null;
        foreach ($this->unseenReplies($token, $uuid)->where('id', '<=', (int) $data['last_id'])->groupBy('conversation_id') as $conversationId => $messages) {
            $lastId = (int) $messages->max('id');
            SupportChatConversation::query()->whereKey($conversationId)
                ->where('agent_seen_message_id', '<', $lastId)
                ->update(['agent_seen_message_id' => $lastId]);
            if ($latest === null || $lastId > $latest['id']) {
                $latest = ['id' => $lastId, 'conversation_id' => (int) $conversationId];
            }
        }

        if ($latest !== null) {
            $uuid = SupportChatConversation::query()->whereKey($latest['conversation_id'])->value('uuid');
            if ($uuid !== null && $request->session()->get(self::SESSION_KEY) !== $uuid) {
                $request->session()->put(self::SESSION_KEY, $uuid);
                $request->session()->forget(self::SESSION_ORDER_LOOKUPS);
                SupportAssistantOrderFlow::forgetSession();
            }
        }

        return $this->json(['ok' => true]);
    }

    public function subscribe(Request $request, SupportChatPushService $push): JsonResponse
    {
        abort_unless($push->publicKey() !== null, 404);

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000', 'url'],
            'keys.p256dh' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-=+\/]+$/'],
            'keys.auth' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-=+\/]+$/'],
            'encoding' => ['nullable', 'string', 'in:aes128gcm,aesgcm'],
        ]);
        if (! SupportChatPushService::isAllowedEndpoint($data['endpoint'])) {
            return $this->json(['message' => 'Bu tarayıcının bildirim servisi desteklenmiyor.'], 422);
        }

        $token = (string) $this->visitorToken($request, true);
        $push->subscribe($token, [
            'endpoint' => $data['endpoint'],
            'p256dh' => $data['keys']['p256dh'],
            'auth' => $data['keys']['auth'],
            'encoding' => $data['encoding'] ?? 'aes128gcm',
        ]);

        $uuid = $request->session()->get(self::SESSION_KEY);
        if (is_string($uuid) && $uuid !== '') {
            SupportChatConversation::query()->where('uuid', $uuid)->whereNull('visitor_token')->update(['visitor_token' => $token]);
        }

        return $this->json(['ok' => true]);
    }

    /**
     * Oturumdaki sohbeti 30 günlük çereze bağlar. Böylece bağlantıdan önce açılmış sohbet de yanıt alabilir.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function replyAudience(Request $request): array
    {
        $uuid = $request->session()->get(self::SESSION_KEY);
        $uuid = is_string($uuid) && $uuid !== '' ? $uuid : null;
        $token = $this->visitorToken($request, $uuid !== null);

        if ($uuid !== null && $token !== null) {
            SupportChatConversation::query()
                ->where('uuid', $uuid)
                ->whereNull('visitor_token')
                ->update(['visitor_token' => $token]);
        }

        return [$token, $uuid];
    }

    /**
     * @return Collection<int, SupportChatMessage>
     */
    private function unseenReplies(?string $token, ?string $uuid): Collection
    {
        return SupportChatMessage::query()
            ->select('support_chat_messages.*')
            ->join('support_chat_conversations as c', 'c.id', '=', 'support_chat_messages.conversation_id')
            ->where(function ($query) use ($token, $uuid) {
                if ($token !== null) {
                    $query->orWhere('c.visitor_token', $token);
                }
                if ($uuid !== null) {
                    $query->orWhere('c.uuid', $uuid);
                }
            })
            ->whereNotNull('c.last_agent_reply_at')
            ->where('c.last_agent_reply_at', '>=', now()->subDays(self::REPLY_WINDOW_DAYS))
            ->where('support_chat_messages.role', 'agent')
            ->whereColumn('support_chat_messages.id', '>', 'c.agent_seen_message_id')
            ->orderBy('support_chat_messages.id')
            ->limit(10)
            ->get();
    }

    private function visitorToken(Request $request, bool $create): ?string
    {
        $token = $request->cookie(self::VISITOR_COOKIE);
        if (is_string($token) && preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            if ($create) {
                $this->queueVisitorCookie($token);
            }

            return $token;
        }
        if (! $create) {
            return null;
        }

        $token = Str::random(40);
        $this->queueVisitorCookie($token);

        return $token;
    }

    private function queueVisitorCookie(string $token): void
    {
        Cookie::queue(cookie(self::VISITOR_COOKIE, $token, self::VISITOR_COOKIE_MINUTES, '/', null, null, true, false, 'lax'));
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget([self::SESSION_KEY, self::SESSION_ORDER_LOOKUPS]);
        SupportAssistantOrderFlow::forgetSession();

        return $this->json(['ok' => true]);
    }

    /**
     * Asistanın topladığı teslimat bilgileriyle ödeme formunu doldurup ödeme sayfasına yönlendirir.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $prefill = $request->session()->get(SupportAssistantOrderFlow::PREFILL_KEY);

        return is_array($prefill) && $prefill !== []
            ? redirect()->route('checkout.show')->withInput($prefill)
            : redirect()->route('checkout.show');
    }

    private function maskCardNumbers(string $text): string
    {
        return (string) preg_replace_callback('/\b(?:\d[ -]?){12,18}\d\b/', function (array $match) {
            $digits = preg_replace('/\D/', '', $match[0]);
            $sum = 0;
            foreach (str_split(strrev($digits)) as $i => $digit) {
                $n = (int) $digit * ($i % 2 === 1 ? 2 : 1);
                $sum += $n > 9 ? $n - 9 : $n;
            }

            return $sum % 10 === 0 ? '[kart numarası gizlendi]' : $match[0];
        }, $text);
    }

    private function conversation(Request $request, ?string $page): SupportChatConversation
    {
        $token = $this->visitorToken($request, true);
        $uuid = $request->session()->get(self::SESSION_KEY);
        if (is_string($uuid) && $uuid !== '') {
            $existing = SupportChatConversation::query()->where('uuid', $uuid)->first();
            if ($existing) {
                if ($existing->visitor_token === null) {
                    $existing->update(['visitor_token' => $token]);
                }

                return $existing;
            }
        }

        $conversation = SupportChatConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'ip_hash' => hash('sha256', (string) $request->ip().'|'.config('app.key')),
            'visitor_token' => $token,
            'page_url' => $page ? Str::limit($page, 490, '') : null,
            'last_message_at' => now(),
        ]);
        $request->session()->put(self::SESSION_KEY, $conversation->uuid);

        return $conversation;
    }

    /** @param  array<string, mixed>  $data */
    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, [
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
