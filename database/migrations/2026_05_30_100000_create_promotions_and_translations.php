<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('value', 12, 2)->nullable();
            $table->unsignedTinyInteger('buy_qty')->nullable();
            $table->unsignedTinyInteger('free_qty')->nullable();
            $table->decimal('min_cart', 12, 2)->nullable();
            $table->boolean('auto_apply')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
        });

        foreach (['products', 'categories', 'brands', 'pages', 'blog_posts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->json('translations')->nullable();
            });
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->string('type')->default('percent')->after('code');
            $table->decimal('fixed_amount', 12, 2)->nullable()->after('percent');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['type', 'fixed_amount']);
        });

        foreach (['products', 'categories', 'brands', 'pages', 'blog_posts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('translations');
            });
        }

        Schema::dropIfExists('promotions');
    }
};
