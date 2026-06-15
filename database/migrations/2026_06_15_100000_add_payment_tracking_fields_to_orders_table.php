<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_failed_at')->nullable()->after('payment_method');
            $table->timestamp('payment_reminder_sent_at')->nullable()->after('payment_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_failed_at', 'payment_reminder_sent_at']);
        });
    }
};
