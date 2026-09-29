<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $changed = 0;

        foreach ([1445, 1465] as $id) {
            $specs = json_decode((string) DB::table('products')->where('id', $id)->value('specs'), true);
            if (! is_array($specs)) {
                continue;
            }
            $fixed = array_map(
                fn ($value) => is_string($value)
                    ? (preg_replace('/(P|p)eriferik(?:al)?(?![a-zçğıöşü])/u', '$1referikal', $value) ?? $value)
                    : $value,
                $specs
            );
            if ($fixed === $specs) {
                continue;
            }
            DB::table('products')->where('id', $id)->update([
                'specs' => json_encode($fixed, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
            $changed++;
        }

        if ($changed > 0) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }

    public function down(): void
    {
        // İçerik güncellemesi geri alınmaz.
    }
};
