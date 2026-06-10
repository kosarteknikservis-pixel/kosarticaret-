<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_speed_audits', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 64);
            $table->string('label');
            $table->string('url', 2048);
            $table->string('strategy', 16);
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedInteger('fcp_ms')->nullable();
            $table->unsignedInteger('lcp_ms')->nullable();
            $table->decimal('cls', 8, 4)->nullable();
            $table->unsignedInteger('tbt_ms')->nullable();
            $table->unsignedInteger('speed_index_ms')->nullable();
            $table->unsignedInteger('field_lcp_p75_ms')->nullable();
            $table->decimal('field_cls_p75', 8, 4)->nullable();
            $table->unsignedInteger('field_inp_p75_ms')->nullable();
            $table->string('field_overall_category', 32)->nullable();
            $table->json('opportunities')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->index(['page_key', 'strategy', 'measured_at']);
            $table->index(['url', 'strategy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_speed_audits');
    }
};
