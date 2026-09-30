<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\SupportChatConversation;
use App\Services\SupportAssistant\SupportAssistantService;
use App\Support\SupportAssistantConfig;
use App\Support\Utf8Mojibake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportChatController extends Controller
{
    private const SESSION_KEY = 'support_chat_uuid';

    private const SESSION_ORDER_LOOKUPS = 'support_chat_order_lookups';

    public function message(Request $request, SupportAssistantService $assistant): JsonResponse
    {
        abort_unless(SupportAssistantConfig::isEnabled(), 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.SupportAssistantConfig::MAX_MESSAGE_LENGTH],
            'page' => ['nullable', 'string', 'max:500'],
        ]);

        $text = trim((string) Utf8Mojibake::repair(strip_tags($data['message'])));
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

        return $this->json($result);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget([self::SESSION_KEY, self::SESSION_ORDER_LOOKUPS]);

        return $this->json(['ok' => true]);
    }

    private function conversation(Request $request, ?string $page): SupportChatConversation
    {
        $uuid = $request->session()->get(self::SESSION_KEY);
        if (is_string($uuid) && $uuid !== '') {
            $existing = SupportChatConversation::query()->where('uuid', $uuid)->first();
            if ($existing) {
                return $existing;
            }
        }

        $conversation = SupportChatConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'ip_hash' => hash('sha256', (string) $request->ip().'|'.config('app.key')),
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
