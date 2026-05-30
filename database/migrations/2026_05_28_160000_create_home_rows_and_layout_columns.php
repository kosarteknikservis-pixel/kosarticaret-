<?php

use App\Models\HomeBanner;
use App\Models\HomeRow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_rows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->json('columns');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('home_banners', function (Blueprint $table) {
            $table->foreignId('home_row_id')->nullable()->after('type')->constrained('home_rows')->nullOnDelete();
            $table->unsignedTinyInteger('col_index')->default(0)->after('home_row_id');
        });

        $this->migrateExistingBanners();
    }

    public function down(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('home_row_id');
            $table->dropColumn('col_index');
        });
        Schema::dropIfExists('home_rows');
    }

    private function migrateExistingBanners(): void
    {
        $banners = HomeBanner::query()->orderBy('sort_order')->orderBy('id')->get();
        if ($banners->isEmpty()) {
            return;
        }

        $sliders = $banners->filter(fn (HomeBanner $b) => $b->isSlider());
        $tiles = $banners->filter(fn (HomeBanner $b) => $b->isTile());

        if ($sliders->isNotEmpty()) {
            $row = HomeRow::query()->create([
                'name' => 'Slider',
                'columns' => [12],
                'sort_order' => 0,
            ]);
            foreach ($sliders->values() as $i => $banner) {
                $banner->update(['home_row_id' => $row->id, 'col_index' => 0, 'sort_order' => $i]);
            }
        }

        if ($tiles->isNotEmpty()) {
            $row = HomeRow::query()->create([
                'name' => 'Kutular',
                'columns' => [6, 6],
                'sort_order' => 1,
            ]);
            foreach ($tiles->values() as $i => $banner) {
                $banner->update([
                    'home_row_id' => $row->id,
                    'col_index' => $i % 2,
                    'sort_order' => (int) floor($i / 2),
                ]);
            }
        }
    }
};
