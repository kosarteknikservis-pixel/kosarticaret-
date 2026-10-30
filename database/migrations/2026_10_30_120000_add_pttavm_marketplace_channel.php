<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('marketplace_channels')->where('key', 'pttavm')->exists();
        if ($exists) {
            return;
        }

        DB::table('marketplace_channels')->insert([
            'key' => 'pttavm',
            'name' => 'PTT AVM',
            'type' => 'marketplace',
            'is_active' => false,
            'environment' => 'production',
            'credentials' => null,
            'settings' => json_encode([
                'price_mode' => 'site',
                'estimated_courier_delivery' => 2,
                'auto_sync_stock' => false,
                'auto_sync_price' => false,
            ], JSON_UNESCAPED_UNICODE),
            'sort_order' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('marketplace_channels')->where('key', 'pttavm')->delete();
    }
};
