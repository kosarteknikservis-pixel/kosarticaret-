<?php

namespace App\Console\Commands;

use App\Services\Catalog\WooCommerceCatalogImporter;
use Illuminate\Console\Command;

class PreviewCatalogImportCommand extends Command
{
    protected $signature = 'catalog:preview-import {--file=wc-product-export-30-5-2026-1780142002073.csv : CSV dosyası}';

    protected $description = 'İçe aktarım öncesi ürün, kategori ve marka sayılarını gösterir';

    public function handle(WooCommerceCatalogImporter $importer): int
    {
        $file = (string) $this->option('file');
        if (! str_contains($file, DIRECTORY_SEPARATOR)) {
            $file = base_path($file);
        }

        try {
            $stats = $importer->preview($file);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Dosya: '.$file);
        $this->table(
            ['Metrik', 'Adet'],
            [
                ['Ürün (SKU + isim)', $stats['products']],
                ['Atlanan satır', $stats['products_skipped']],
                ['Marka', $stats['brands']],
                ['Kategori (toplam düğüm)', $stats['categories_total']],
                ['Üst kategori', $stats['categories_root']],
                ['Alt kategori', $stats['categories_child']],
            ]
        );

        if ($stats['brand_names'] !== []) {
            $this->newLine();
            $this->line('Markalar: '.implode(', ', $stats['brand_names']));
        }

        if ($stats['category_roots'] !== []) {
            $this->newLine();
            $this->line('Üst kategoriler: '.implode(', ', $stats['category_roots']));
        }

        return self::SUCCESS;
    }
}
