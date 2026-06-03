<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 96)->nullable()->index();
            $table->string('device_type', 20)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable();
            $table->text('referrer')->nullable();
            $table->text('landing_url')->nullable();
            $table->text('last_url')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 40)->index();
            $table->text('url')->nullable();
            $table->text('referrer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->foreign('visitor_id')->references('id')->on('analytics_visitors')->nullOnDelete();
            $table->index(['visitor_id', 'occurred_at']);
            $table->index(['product_id', 'event_type']);
        });

        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->json('items')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->timestamp('started_checkout_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('visitor_id')->references('id')->on('analytics_visitors')->nullOnDelete();
            $table->index(['visitor_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('analytics_visitor_id')->nullable()->after('coupon_code');
            $table->string('order_source')->nullable()->after('analytics_visitor_id');
            $table->string('order_medium')->nullable()->after('order_source');
            $table->string('order_campaign')->nullable()->after('order_medium');
            $table->text('landing_url')->nullable()->after('order_campaign');
            $table->text('referrer_url')->nullable()->after('landing_url');

            $table->foreign('analytics_visitor_id')->references('id')->on('analytics_visitors')->nullOnDelete();
            $table->index(['order_source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['analytics_visitor_id']);
            $table->dropIndex(['order_source', 'created_at']);
            $table->dropColumn([
                'analytics_visitor_id',
                'order_source',
                'order_medium',
                'order_campaign',
                'landing_url',
                'referrer_url',
            ]);
        });

        Schema::dropIfExists('abandoned_carts');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('analytics_visitors');
    }
};
