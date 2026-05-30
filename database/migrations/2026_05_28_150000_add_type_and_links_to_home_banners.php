<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->string('type', 20)->default('slider')->after('id');
            $table->foreignId('product_id')->nullable()->after('image')->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('type');
        });
    }
};
