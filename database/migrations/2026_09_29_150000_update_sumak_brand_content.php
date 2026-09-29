<?php

use App\Models\Brand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Canlıdaki eski açıklamanın md5'i; panelden değiştirilmişse üzerine yazılmaz. */
    private const EXPECTED_OLD_DESCRIPTION_MD5 = '5dfe53a5578c2255bce31a1b6ef32564';

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'sumak')->first();
        if (! $brand || md5((string) $brand->getRawOriginal('description')) !== self::EXPECTED_OLD_DESCRIPTION_MD5) {
            return;
        }

        // config:cache bu migration'dan sonra çalıştığı için önbellekteki eski config okunmamalı.
        $seo = (require config_path('brand_seo.php'))['sumak'];

        $brand->forceFill([
            'meta_title' => $seo['meta_title'],
            'meta_description' => $seo['meta_description'],
            'description' => $seo['description'],
            'faq' => $seo['faq'],
        ])->save();
    }

    public function down(): void
    {
        // İçerik güncellemesi; geri alma için yedekten dönülür.
    }
};
