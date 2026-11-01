<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (! Schema::hasColumn('abandoned_carts', 'reminded_at')) {
                $table->timestamp('reminded_at')->nullable()->after('last_activity_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (Schema::hasColumn('abandoned_carts', 'reminded_at')) {
                $table->dropColumn('reminded_at');
            }
        });
    }
};
