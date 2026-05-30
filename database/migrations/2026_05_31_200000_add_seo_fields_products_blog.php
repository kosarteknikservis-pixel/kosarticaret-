<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('featured');
            $table->string('image_alt', 255)->nullable()->after('image');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('image')->nullable()->after('excerpt');
            $table->string('image_alt', 255)->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'image_alt']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['image', 'image_alt']);
        });
    }
};
