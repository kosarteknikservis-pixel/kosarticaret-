<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Seo\ProductSchemaEnricher;
use Illuminate\Console\Command;

class EnrichProductSchemaFieldsCommand extends Command
{
    protected $signature = 'seo:enrich-product-schema-fields
                            {--meta : Sablon meta_description alanlarini gercek aciklamadan yenile}
                            {--specs : Description HTML tablolarindan specs cikar}
                            {--force-specs : Mevcut specs uzerine yaz}
                            {--dry-run : Kaydetmeden raporla}
                            {--limit=0 : En fazla N urun (0 = hepsi)}';

    protected $description = 'Urun schema kalitesi: sablon meta temizligi + teknik ozellik (specs) cikarma.';

    public function handle(ProductSchemaEnricher $enricher): int
    {
        $doMeta = $this->option('meta') || (! $this->option('meta') && ! $this->option('specs'));
        $doSpecs = $this->option('specs') || (! $this->option('meta') && ! $this->option('specs'));
        $forceSpecs = (bool) $this->option('force-specs');
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        $query = Product::query()->with('brand:id,name')->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $metaUpdated = 0;
        $specsUpdated = 0;
        $scanned = 0;

        $query->chunkById(100, function ($products) use (
            $enricher,
            $doMeta,
            $doSpecs,
            $forceSpecs,
            $dryRun,
            &$metaUpdated,
            &$specsUpdated,
            &$scanned
        ): void {
            foreach ($products as $product) {
                $scanned++;
                $result = $enricher->enrich($product, $doMeta, $doSpecs, $forceSpecs);

                if (! $result['meta_changed'] && ! $result['specs_changed']) {
                    continue;
                }

                if ($result['meta_changed']) {
                    $metaUpdated++;
                    if (! $dryRun) {
                        $product->meta_description = $result['meta_description'];
                    }
                }

                if ($result['specs_changed']) {
                    $specsUpdated++;
                    if (! $dryRun) {
                        $product->specs = $result['specs'];
                    }
                }

                if (! $dryRun && ($result['meta_changed'] || $result['specs_changed'])) {
                    $product->save();
                }
            }
        });

        $this->info(($dryRun ? '[dry-run] ' : '')."Taranan: {$scanned}");
        $this->line("Meta guncellenecek/guncellendi: {$metaUpdated}");
        $this->line("Specs guncellenecek/guncellendi: {$specsUpdated}");
        $this->line('Not: Barkod (GTIN) kaynak veri gerektirir; panel veya toplu CSV ile doldurulur. Yorumlar onaylaninca rating otomatik acilir.');

        return self::SUCCESS;
    }
}
