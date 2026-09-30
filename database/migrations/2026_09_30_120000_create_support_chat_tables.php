<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_chat_conversations')) {
            Schema::create('support_chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('ip_hash', 64)->nullable();
                $table->string('page_url', 500)->nullable();
                $table->unsignedInteger('message_count')->default(0);
                $table->unsignedInteger('unanswered_count')->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->timestamp('handed_off_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_chat_messages')) {
            Schema::create('support_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('support_chat_conversations')->cascadeOnDelete();
                $table->string('role', 16);
                $table->text('content');
                $table->json('tools')->nullable();
                $table->json('products')->nullable();
                $table->boolean('unanswered')->default(false)->index();
                $table->unsignedInteger('tokens')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_messages');
        Schema::dropIfExists('support_chat_conversations');
    }
};
