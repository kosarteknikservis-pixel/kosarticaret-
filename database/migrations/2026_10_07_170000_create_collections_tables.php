<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('target_keyword')->nullable();
            $table->string('status', 16)->default('draft');
            $table->text('description')->nullable();
            $table->json('rules');
            $table->json('faq')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('collection_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('source', 16);
            $table->string('evidence')->nullable();
            $table->timestamps();
            $table->unique(['collection_id', 'product_id']);
        });

        $now = now();
        $definitions = [
            ['1 HP Hidrofor', '1-hp-hidrofor', '1 hp hidrofor', 'hidrofor-sistemleri', ['motor_hp' => 1]],
            ['1 HP Dalgıç Pompa', '1-hp-dalgic-pompa', '1 hp dalgıç pompa', 'dalgic-pompalar', ['motor_hp' => 1]],
            ['2 HP Dalgıç Pompa', '2-hp-dalgic-pompa', '2 hp dalgıç pompa', 'dalgic-pompalar', ['motor_hp' => 2]],
            ['3 HP Hidrofor', '3-hp-hidrofor', '3 hp hidrofor', 'hidrofor-sistemleri', ['motor_hp' => 3]],
            ['Monofaze Dalgıç Pompa', 'monofaze-dalgic-pompa', 'monofaze dalgıç pompa', 'dalgic-pompalar', ['phase' => 'monofaze']],
        ];

        foreach ($definitions as [$name, $slug, $keyword, $categorySlug, $rules]) {
            $categoryId = DB::table('categories')->where('slug', $categorySlug)->value('id');
            if (! $categoryId) {
                continue;
            }
            DB::table('collections')->insert([
                'name' => $name,
                'slug' => $slug,
                'target_keyword' => $keyword,
                'status' => 'draft',
                'rules' => json_encode($rules),
                'category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::table('collections')->exists()) {
            app(\App\Services\CollectionMatcher::class)->syncAll();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_products');
        Schema::dropIfExists('collections');
    }
};
