<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blog_posts')) {
            Schema::create('blog_posts', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->string('excerpt')->nullable();
                $table->string('image')->nullable();
                $table->string('image_alt', 255)->nullable();
                $table->longText('content');
                $table->json('tags')->nullable();
                $table->json('translations')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->boolean('published')->default(true);
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_posts', 'image')) {
                $table->string('image')->nullable()->after('excerpt');
            }
            if (! Schema::hasColumn('blog_posts', 'image_alt')) {
                $table->string('image_alt', 255)->nullable()->after(
                    Schema::hasColumn('blog_posts', 'image') ? 'image' : 'excerpt'
                );
            }
            if (! Schema::hasColumn('blog_posts', 'translations')) {
                $table->json('translations')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        // Canlıda blog verisi korunur; geri alma yok.
    }
};
