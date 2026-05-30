<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->string('product_source', 20)->nullable()->after('category_id');
            $table->foreignId('brand_id')->nullable()->after('product_source')->constrained('brands')->nullOnDelete();
            $table->json('product_ids')->nullable()->after('brand_id');
            $table->unsignedTinyInteger('product_limit')->default(4)->after('product_ids');
        });
    }

    public function down(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['product_source', 'product_ids', 'product_limit']);
        });
    }
};
