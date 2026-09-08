<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_price_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('search_query', 500)->nullable();
            $table->string('status', 30)->default('pending_review')->index();
            $table->decimal('google_min_price', 12, 2)->nullable();
            $table->decimal('google_median_price', 12, 2)->nullable();
            $table->unsignedInteger('offer_count')->default(0);
            $table->json('offers')->nullable();
            $table->timestamp('last_scanned_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_price_scans');
    }
};
