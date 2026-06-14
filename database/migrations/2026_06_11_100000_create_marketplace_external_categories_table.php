<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_external_categories', function (Blueprint $table) {
            $table->id();
            $table->string('channel_key', 40);
            $table->string('external_id');
            $table->string('name');
            $table->text('path')->nullable();
            $table->string('parent_external_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_key', 'external_id']);
            $table->index(['channel_key', 'name']);
            $table->foreign('channel_key')->references('key')->on('marketplace_channels')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_external_categories');
    }
};
