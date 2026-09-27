<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uzun yollar önce gelmeli. Kısa bir yol, daha uzun bir yolun parçası olmamalı.
     *
     * @var array<string, string>
     */
    private array $linkMap = [
        '/kategoriler/su-pompalari/dalgic-pompalar/solar-dc-dalgic-pompalar' => '/kategoriler/su-pompalari/dalgic-pompalar',
        '/kategoriler/su-pompalari/dalgic-pompalar/pedrollo-dalgic-pompa' => '/marka/pedrollo',
        '/kategoriler/su-pompalari/dalgic-pompalar/sumak-dalgic-pompa' => '/marka/sumak',
        '/kategoriler/su-pompalari/dalgic-pompalar/dalgic-pompa' => '/kategoriler/su-pompalari/dalgic-pompalar',
        '/kategoriler/hidrofor-sistemleri/frekans-kontrollu-hidroforlar' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
        '/kategoriler/su-pompalari/su-pompasi' => '/kategoriler/su-pompalari',
    ];

    public function up(): void
    {
        $this->consolidateThinCategory('su-pompasi', 'su-pompalari');
        $this->consolidateThinCategory('dalgic-pompa', 'dalgic-pompalar');
        $this->rewriteStoredLinks();

        if (class_exists(UrlIndexingNotifier::class)) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }

    public function down(): void
    {
        foreach (['su-pompasi', 'dalgic-pompa'] as $slug) {
            DB::table('categories')->where('slug', $slug)->update([
                'active' => true,
                'show_in_menu' => true,
            ]);
        }
    }

    private function consolidateThinCategory(string $thinSlug, string $parentSlug): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        $parent = DB::table('categories')->where('slug', $parentSlug)->first();
        $thin = DB::table('categories')->where('slug', $thinSlug)->where('parent_id', $parent->id ?? 0)->first();

        if ($parent === null || $thin === null) {
            return;
        }

        if (Schema::hasTable('category_product')) {
            $parentProductIds = DB::table('category_product')
                ->where('category_id', $parent->id)
                ->pluck('product_id');

            $missing = DB::table('category_product')
                ->where('category_id', $thin->id)
                ->whereNotIn('product_id', $parentProductIds)
                ->pluck('product_id');

            foreach ($missing as $productId) {
                DB::table('category_product')->insert([
                    'category_id' => $parent->id,
                    'product_id' => $productId,
                ]);
            }
        }

        DB::table('categories')->where('id', $thin->id)->update([
            'active' => false,
            'show_in_menu' => false,
        ]);
    }

    private function rewriteStoredLinks(): void
    {
        $targets = [
            'blog_posts' => ['content', 'excerpt', 'translations'],
            'categories' => ['description', 'buying_guide', 'translations'],
            'navigation_items' => ['url'],
        ];

        foreach ($targets as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn($table, $column)
            ));

            if ($columns === []) {
                continue;
            }

            DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        if (! is_string($value) || $value === '') {
                            continue;
                        }
                        $rewritten = $value;
                        foreach ($this->linkMap as $from => $to) {
                            $rewritten = preg_replace(
                                '~'.preg_quote($from, '~').'(?=["\'\s?#]|$)~',
                                $to,
                                $rewritten
                            ) ?? $rewritten;
                        }
                        if ($rewritten !== $value) {
                            $updates[$column] = $rewritten;
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }
};
