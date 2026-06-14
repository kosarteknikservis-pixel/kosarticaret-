<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 32)->nullable()->after('sku');
            $table->decimal('weight_kg', 8, 3)->nullable()->after('stock');
            $table->decimal('width_cm', 8, 2)->nullable()->after('weight_kg');
            $table->decimal('height_cm', 8, 2)->nullable()->after('width_cm');
            $table->decimal('depth_cm', 8, 2)->nullable()->after('height_cm');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('depth_cm');
            $table->boolean('marketplace_enabled')->default(true)->after('is_active');

            $table->index('barcode');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('sales_channel', 40)->default('website')->after('payment_method');
            $table->string('external_order_id')->nullable()->after('sales_channel');
            $table->string('external_package_id')->nullable()->after('external_order_id');
            $table->decimal('marketplace_commission', 12, 2)->nullable()->after('external_package_id');
            $table->json('marketplace_payload')->nullable()->after('marketplace_commission');

            $table->index(['sales_channel', 'created_at']);
            $table->unique(['sales_channel', 'external_order_id']);
        });

        Schema::create('marketplace_channels', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name');
            $table->string('type', 20)->default('marketplace'); // marketplace | feed
            $table->boolean('is_active')->default(false);
            $table->string('environment', 20)->default('production'); // sandbox | production
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('channel_key', 40);
            $table->string('external_product_id')->nullable();
            $table->string('external_sku')->nullable();
            $table->string('status', 30)->default('draft');
            $table->decimal('channel_price', 12, 2)->nullable();
            $table->unsignedInteger('channel_stock_limit')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'channel_key']);
            $table->index(['channel_key', 'status']);
            $table->foreign('channel_key')->references('key')->on('marketplace_channels')->cascadeOnDelete();
        });

        Schema::create('marketplace_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_key', 40);
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('external_category_id');
            $table->string('external_category_name')->nullable();
            $table->text('external_category_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['channel_key', 'category_id']);
            $table->foreign('channel_key')->references('key')->on('marketplace_channels')->cascadeOnDelete();
        });

        Schema::create('marketplace_brand_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_key', 40);
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('external_brand_id');
            $table->string('external_brand_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['channel_key', 'brand_id']);
            $table->foreign('channel_key')->references('key')->on('marketplace_channels')->cascadeOnDelete();
        });

        Schema::create('marketplace_attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_key', 40);
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('local_spec_key');
            $table->string('external_attribute_id');
            $table->string('external_attribute_name')->nullable();
            $table->json('value_map')->nullable();
            $table->timestamps();

            $table->unique(['channel_key', 'category_id', 'local_spec_key'], 'marketplace_attr_map_unique');
            $table->foreign('channel_key')->references('key')->on('marketplace_channels')->cascadeOnDelete();
        });

        Schema::create('marketplace_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel_key', 40)->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->string('status', 20)->default('pending');
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['channel_key', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        $now = now();
        $channels = [
            ['key' => 'trendyol', 'name' => 'Trendyol', 'type' => 'marketplace', 'sort_order' => 10],
            ['key' => 'hepsiburada', 'name' => 'Hepsiburada', 'type' => 'marketplace', 'sort_order' => 20],
            ['key' => 'n11', 'name' => 'N11', 'type' => 'marketplace', 'sort_order' => 30],
            ['key' => 'idefix', 'name' => 'Idefix', 'type' => 'marketplace', 'sort_order' => 40],
            ['key' => 'pazarama', 'name' => 'Pazarama', 'type' => 'marketplace', 'sort_order' => 50],
            ['key' => 'akakce', 'name' => 'Akakçe', 'type' => 'feed', 'sort_order' => 60],
        ];

        foreach ($channels as $channel) {
            DB::table('marketplace_channels')->insert([
                ...$channel,
                'is_active' => false,
                'environment' => 'production',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_sync_logs');
        Schema::dropIfExists('marketplace_attribute_mappings');
        Schema::dropIfExists('marketplace_brand_mappings');
        Schema::dropIfExists('marketplace_category_mappings');
        Schema::dropIfExists('marketplace_listings');
        Schema::dropIfExists('marketplace_channels');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['sales_channel', 'external_order_id']);
            $table->dropIndex(['sales_channel', 'created_at']);
            $table->dropColumn([
                'sales_channel',
                'external_order_id',
                'external_package_id',
                'marketplace_commission',
                'marketplace_payload',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropColumn([
                'barcode',
                'weight_kg',
                'width_cm',
                'height_cm',
                'depth_cm',
                'vat_rate',
                'marketplace_enabled',
            ]);
        });
    }
};
