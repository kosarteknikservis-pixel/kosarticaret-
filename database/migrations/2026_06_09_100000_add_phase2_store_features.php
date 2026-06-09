<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('type', 32)->default('contact')->after('id');
            $table->json('meta')->nullable()->after('body');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->longText('buying_guide')->nullable()->after('description');
        });

        Schema::create('project_references', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('client')->nullable();
            $table->string('sector')->nullable();
            $table->string('location')->nullable();
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query', 255);
            $table->string('normalized', 255)->index();
            $table->unsignedInteger('results_count')->default(0);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('searched_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('project_references');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('buying_guide');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'meta']);
        });
    }
};
