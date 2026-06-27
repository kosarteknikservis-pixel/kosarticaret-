<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('units_per_carton')->nullable()->after('depth_cm');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_carrier', 32)->nullable()->after('shipping_tracking');
            $table->timestamp('shipment_sms_sent_at')->nullable()->after('payment_reminder_sent_at');
        });

        Schema::create('order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('package_number')->default(1);
            $table->string('carrier', 32)->default('dhl');
            $table->string('status', 32)->default('draft');
            $table->string('external_id')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('barcode')->nullable();
            $table->string('label_path')->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('desi', 8, 2)->nullable();
            $table->json('items');
            $table->decimal('cod_amount', 12, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->json('carrier_payload')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'package_number']);
            $table->index(['carrier', 'status']);
            $table->index('tracking_number');
        });

        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_shipment_id')->constrained('order_shipments')->cascadeOnDelete();
            $table->string('status', 32);
            $table->string('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['order_shipment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('order_shipments');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_carrier', 'shipment_sms_sent_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('units_per_carton');
        });
    }
};
