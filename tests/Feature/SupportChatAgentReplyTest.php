<?php

namespace Tests\Feature;

use App\Http\Controllers\Shop\SupportChatController;
use App\Models\SiteSetting;
use App\Models\SupportChatConversation;
use App\Models\SupportChatMessage;
use App\Models\SupportChatPushSubscription;
use App\Models\User;
use App\Services\SupportAssistant\SupportChatPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportChatAgentReplyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_reply_is_stored_and_the_visitor_can_read_it_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Ayşe Yönetici']);
        $token = Str::random(40);
        $conversation = SupportChatConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'visitor_token' => $token,
            'message_count' => 1,
            'last_message_at' => now(),
        ]);
        SupportChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Led armatör kaç ledli',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support-chats.reply', $conversation), ['reply' => 'Stokta 60 ledli model var.'])
            ->assertRedirect(route('admin.support-chats.show', $conversation));

        $reply = SupportChatMessage::query()->where('role', 'agent')->first();
        $this->assertNotNull($reply);
        $this->assertSame('Stokta 60 ledli model var.', $reply->content);
        $this->assertSame('Ayşe Yönetici', $reply->author_name);
        $this->assertNotNull($conversation->fresh()->last_agent_reply_at);

        $this->actingAs($admin)
            ->get(route('admin.support-chats.show', $conversation))
            ->assertOk()
            ->assertSee('Temsilci yanıtı', false)
            ->assertSee('henüz görülmedi', false)
            ->assertSee('Bildirim kapalı', false);

        $cookie = SupportChatController::VISITOR_COOKIE;
        $this->withCredentials()->withCookie($cookie, $token)
            ->getJson(route('support-chat.replies'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('messages.0.question', 'Led armatör kaç ledli');

        $this->withCredentials()->withCookie($cookie, $token)
            ->postJson(route('support-chat.replies.seen'), ['last_id' => $reply->id])
            ->assertOk()
            ->assertSessionHas('support_chat_uuid', $conversation->uuid);

        $this->assertSame($reply->id, (int) $conversation->fresh()->agent_seen_message_id);

        $this->withCredentials()->withCookie($cookie, $token)
            ->getJson(route('support-chat.replies'))
            ->assertOk()
            ->assertJsonPath('unread', 0);
    }

    #[Test]
    public function an_older_conversation_can_still_be_answered_in_the_same_browser_session(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conversation = SupportChatConversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'message_count' => 1,
            'last_message_at' => now(),
        ]);
        SupportChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Led armatör kaç ledli',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.support-chats.show', $conversation))
            ->assertOk()
            ->assertSee('Müşteriye gönder', false);

        $this->actingAs($admin)
            ->post(route('admin.support-chats.reply', $conversation), ['reply' => 'Stokta 60 ledli model var.'])
            ->assertRedirect(route('admin.support-chats.show', $conversation))
            ->assertSessionHas('success');

        $this->assertSame(1, SupportChatMessage::query()->where('role', 'agent')->count());

        auth()->logout();
        $this->flushSession();
        $this->withSession(['support_chat_uuid' => $conversation->uuid])
            ->getJson(route('support-chat.replies'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('messages.0.text', 'Stokta 60 ledli model var.');

        $this->assertNotNull($conversation->fresh()->visitor_token);
    }

    #[Test]
    public function the_storefront_launcher_exposes_reply_and_push_hooks(): void
    {
        SiteSetting::set('ai_assistant_enabled', '1');
        SiteSetting::set('openai_api_key', 'sk-test');
        SiteSetting::set(SupportChatPushService::PUBLIC_KEY_SETTING, str_repeat('A', 80));
        SiteSetting::set(SupportChatPushService::PRIVATE_KEY_SETTING, str_repeat('B', 40));

        $this->get('/')
            ->assertOk()
            ->assertSee('data-replies-endpoint', false)
            ->assertSee('data-support-chat-badge', false)
            ->assertSee('data-push-key', false)
            ->assertSee('js/destek-bildirim-sw.js', false);
    }

    #[Test]
    public function push_subscription_accepts_only_browser_push_services(): void
    {
        $this->assertFalse(SupportChatPushService::isAllowedEndpoint('https://example.com/push'));
        $this->assertFalse(SupportChatPushService::isAllowedEndpoint('http://fcm.googleapis.com/fcm/send/abc'));
        $this->assertTrue(SupportChatPushService::isAllowedEndpoint('https://fcm.googleapis.com/fcm/send/abc'));

        SiteSetting::set(SupportChatPushService::PUBLIC_KEY_SETTING, str_repeat('A', 80));
        SiteSetting::set(SupportChatPushService::PRIVATE_KEY_SETTING, str_repeat('B', 40));

        $this->postJson(route('support-chat.subscribe'), [
            'endpoint' => 'https://evil.example/push',
            'keys' => ['p256dh' => 'abc', 'auth' => 'def'],
        ])->assertStatus(422);
        $this->assertSame(0, SupportChatPushSubscription::query()->count());

        $this->postJson(route('support-chat.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'BKtest', 'auth' => 'authkey'],
            'encoding' => 'aes128gcm',
        ])->assertOk();

        $this->assertSame(1, SupportChatPushSubscription::query()->count());
        $this->assertNotEmpty($this->postJson(route('support-chat.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'BKtest', 'auth' => 'authkey'],
        ])->headers->get('set-cookie'));
    }
}
