<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->string('external_barcode', 64)->nullable()->after('external_sku');
            $table->index(['channel_key', 'external_barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropIndex(['channel_key', 'external_barcode']);
            $table->dropColumn('external_barcode');
        });
    }
};
