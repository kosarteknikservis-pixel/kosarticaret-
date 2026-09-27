<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marka adı araması marka sayfasında kalır.
     * "Sumak hidrofor" kendi kategorisinde kalır; o yazının başlığı değişmez.
     *
     * @var array<string, array{title: string, meta_title: string, meta_description: string}>
     */
    private array $posts = [
        'sumak-pompa-marka-rehberi' => [
            'title' => 'Sumak Pompa Nasıl Seçilir? Hidrofor, Dalgıç ve Santrifüj',
            'meta_title' => 'Sumak Pompa Nasıl Seçilir?',
            'meta_description' => 'Sumak hidrofor, dalgıç ve santrifüj serileri arasındaki fark. Doğru seriyi debi, kat sayısı ve su kaynağı belirler.',
        ],
        'pedrollo-pompa-marka-rehberi' => [
            'title' => 'Pedrollo Pompa Nasıl Seçilir? Hidrofor, Dalgıç ve Santrifüj',
            'meta_title' => 'Pedrollo Pompa Nasıl Seçilir?',
            'meta_description' => 'Pedrollo hidrofor, dalgıç ve santrifüj serileri arasındaki fark. Doğru modeli debi, basma yüksekliği ve su kaynağı belirler.',
        ],
        'kaysu-pompa-marka-rehberi' => [
            'title' => 'Kaysu Pompa Nasıl Seçilir? Hidrofor, Santrifüj ve Dalgıç',
            'meta_title' => 'Kaysu Pompa Nasıl Seçilir?',
            'meta_description' => 'Kaysu paket hidrofor, santrifüj ve dalgıç serileri arasındaki fark. Doğru kapasiteyi kat sayısı ve debi belirler.',
        ],
        'winpo-hidrofor-marka-rehberi' => [
            'title' => 'Winpo Hidrofor Nasıl Seçilir? Paket Sistem ve Tank',
            'meta_title' => 'Winpo Hidrofor Nasıl Seçilir?',
            'meta_description' => 'Winpo paket hidroforda doğru modeli kat sayısı, daire adedi ve tank hacmi belirler.',
        ],
        'pedrollo-dalgic-pompa-modelleri-rehberi' => [
            'title' => 'Pedrollo Dalgıç Pompa Nasıl Seçilir? Kuyu ve Drenaj',
            'meta_title' => 'Pedrollo Dalgıç Pompa Nasıl Seçilir?',
            'meta_description' => 'Pedrollo dalgıç pompada doğru modeli kuyu derinliği, debi ve suyun temiz ya da kirli olması belirler.',
        ],
    ];

    /** @var array<string, array{title: string, meta_title: string, meta_description: string}> */
    private array $previousPosts = [
        'sumak-pompa-marka-rehberi' => [
            'title' => 'Sumak Pompa Marka Rehberi: Hidrofor, Dalgıç ve Santrifüj Modeller',
            'meta_title' => 'Sumak Pompa Marka Rehberi: Hidrofor ve Modeller',
            'meta_description' => 'Sumak pompa ve hidrofor rehberi: SKS/SKT serileri, dalgıç pompa, jet pompa. Yerli üretim, yedek parça ve model seçimi. Orijinal ürün listesi.',
        ],
        'pedrollo-pompa-marka-rehberi' => [
            'title' => 'Pedrollo Pompa Marka Rehberi: Hidrofor, Dalgıç ve Santrifüj Modeller',
            'meta_title' => 'Pedrollo Pompa Marka Rehberi 2026',
            'meta_description' => 'Pedrollo pompa marka rehberi: hidrofor, dalgıç pompa ve santrifüj modelleri, avantajlar, garanti ve doğru model seçimi.',
        ],
        'kaysu-pompa-marka-rehberi' => [
            'title' => 'Kaysu Pompa Marka Rehberi: Hidrofor, Santrifüj ve Dalgıç Modeller',
            'meta_title' => 'Kaysu Hidrofor ve Pompa Marka Rehberi 2026',
            'meta_description' => 'Kaysu hidrofor ve pompa modelleri: ev tipi paket sistemler, santrifüj ve dalgıç pompa. Fiyat-performans, yedek parça ve doğru kapasite seçimi.',
        ],
        'winpo-hidrofor-marka-rehberi' => [
            'title' => 'Winpo Hidrofor Marka Rehberi: Paket Sistemler ve Model Seçimi',
            'meta_title' => 'Winpo Hidrofor Marka Rehberi 2026',
            'meta_description' => 'Winpo hidrofor marka rehberi: paket jet hidrofor sistemleri, model seçimi, fiyat segmenti ve ev/apartman kullanımı.',
        ],
        'pedrollo-dalgic-pompa-modelleri-rehberi' => [
            'title' => 'Pedrollo Dalgıç Pompa Modelleri: Kuyu, Drenaj ve Seçim Rehberi',
            'meta_title' => 'Pedrollo Dalgıç Pompa Modelleri Rehberi',
            'meta_description' => 'Pedrollo dalgıç pompa modelleri: kuyu derinliği, debi seçimi, 4 inç kuyu, drenaj ve temiz su uygulamaları rehberi.',
        ],
    ];

    /** @var array<string, string> */
    private array $brandTitles = [
        'sumak' => 'Sumak Pompa Fiyatları ve Modelleri',
        'kaysu' => 'Kaysu Pompa ve Hidrofor Fiyatları',
        'winpo' => 'Winpo Pompa Modelleri ve Fiyatları',
    ];

    /** @var array<string, string> */
    private array $previousBrandTitles = [
        'sumak' => 'Sumak Pompa ve Hidrofor Modelleri | Yetkili Satıcı',
        'kaysu' => 'Kaysu Hidrofor ve Pompa Fiyatları | Orijinal Ürün',
        'winpo' => 'Winpo Pompa ve WNP Modelleri | Orijinal Ürün',
    ];

    public function up(): void
    {
        $this->applyPosts($this->posts);
        $this->applyBrands($this->brandTitles);
        $this->clearCache();
    }

    public function down(): void
    {
        $this->applyPosts($this->previousPosts);
        $this->applyBrands($this->previousBrandTitles);
        $this->clearCache();
    }

    /**
     * @param  array<string, array{title: string, meta_title: string, meta_description: string}>  $rows
     */
    private function applyPosts(array $rows): void
    {
        if (! Schema::hasTable('blog_posts')) {
            return;
        }

        foreach ($rows as $slug => $fields) {
            DB::table('blog_posts')->where('slug', $slug)->update([
                'title' => $fields['title'],
                'meta_title' => $fields['meta_title'],
                'meta_description' => $fields['meta_description'],
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $titles
     */
    private function applyBrands(array $titles): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasColumn('brands', 'meta_title')) {
            return;
        }

        foreach ($titles as $slug => $metaTitle) {
            DB::table('brands')->where('slug', $slug)->update([
                'meta_title' => $metaTitle,
                'updated_at' => now(),
            ]);
        }
    }

    private function clearCache(): void
    {
        if (class_exists(UrlIndexingNotifier::class)) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }
};
