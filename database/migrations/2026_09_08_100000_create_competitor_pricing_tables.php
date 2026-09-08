<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Varsayılan');
            $table->decimal('undercut_percent', 5, 2)->default(2);
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('min_margin_percent', 5, 2)->nullable();
            $table->boolean('keep_compare_at')->default(true);
            $table->boolean('auto_apply')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('competitor_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('competitor_name', 120);
            $table->text('competitor_url');
            $table->string('competitor_title')->nullable();
            $table->string('match_status', 20)->default('pending')->index();
            $table->decimal('last_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->timestamp('last_fetched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['product_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_offers');
        Schema::dropIfExists('competitor_price_rules');
    }
};
