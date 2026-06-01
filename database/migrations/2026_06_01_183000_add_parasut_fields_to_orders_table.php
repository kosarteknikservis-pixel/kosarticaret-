<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('parasut_sales_invoice_id')->nullable()->after('admin_note');
            $table->string('parasut_status')->nullable()->after('parasut_sales_invoice_id');
            $table->text('parasut_error')->nullable()->after('parasut_status');
            $table->timestamp('parasut_synced_at')->nullable()->after('parasut_error');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'parasut_sales_invoice_id',
                'parasut_status',
                'parasut_error',
                'parasut_synced_at',
            ]);
        });
    }
};
