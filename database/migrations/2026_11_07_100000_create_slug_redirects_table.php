<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 255)->unique();
            $table->string('to_path', 255);
            $table->string('source', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
