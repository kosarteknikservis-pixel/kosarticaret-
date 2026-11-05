<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'panel_synced_at')) {
                $table->timestamp('panel_synced_at')->nullable()->after('telegram_notified_at');
            }
            if (! Schema::hasColumn('orders', 'panel_order_id')) {
                $table->unsignedBigInteger('panel_order_id')->nullable()->after('panel_synced_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'panel_order_id')) {
                $table->dropColumn('panel_order_id');
            }
            if (Schema::hasColumn('orders', 'panel_synced_at')) {
                $table->dropColumn('panel_synced_at');
            }
        });
    }
};
