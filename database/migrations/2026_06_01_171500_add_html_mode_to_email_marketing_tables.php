<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->boolean('body_is_html')->default(false)->after('body');
        });

        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->boolean('body_is_html')->default(false)->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropColumn('body_is_html');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('body_is_html');
        });
    }
};
