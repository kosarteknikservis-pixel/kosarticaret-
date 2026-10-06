<?php

use App\Services\SupportAssistant\SupportChatPushService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chat_conversations', function (Blueprint $table) {
            $table->string('visitor_token', 64)->nullable()->index()->after('ip_hash');
            $table->unsignedBigInteger('agent_seen_message_id')->default(0)->after('read_at');
            $table->timestamp('last_agent_reply_at')->nullable()->after('agent_seen_message_id');
        });

        Schema::table('support_chat_messages', function (Blueprint $table) {
            $table->string('author_name', 120)->nullable()->after('role');
        });

        Schema::create('support_chat_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_token', 64)->index();
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        try {
            app(SupportChatPushService::class)->ensureKeys();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_push_subscriptions');

        Schema::table('support_chat_messages', function (Blueprint $table) {
            $table->dropColumn('author_name');
        });

        Schema::table('support_chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['visitor_token']);
            $table->dropColumn(['visitor_token', 'agent_seen_message_id', 'last_agent_reply_at']);
        });
    }
};
