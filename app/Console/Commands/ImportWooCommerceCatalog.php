<?php

namespace App\Console\Commands;

use App\Services\Catalog\WooCommerceCatalogImporter;
use Illuminate\Console\Command;

class ImportWooCommerceCatalog extends Command
{
    protected $signature = 'catalog:import-woocommerce
                            {--file=final_woocommerce_seo_import.xlsx : Excel veya WooCommerce CSV (proje köküne göre)}
                            {--limit= : Sadece N ürün (test için)}
                            {--offset=0 : İlk N ürün satırını atla (kademeli aktarım)}
                            {--seed-only : Sadece kategori ve marka oluştur}
                            {--products-only : Sadece ürün aktar (kategori/marka dokunma)}
                            {--no-fresh : Mevcut katalog verisini silme}
                            {--skip-images : Görselleri indirme}
                            {--force : Onay sormadan sil ve aktar}';

    protected $description = 'WooCommerce Excel/CSV kataloğunu içe aktarır (kategori, marka, ürün, SEO)';

    public function handle(WooCommerceCatalogImporter $importer): int
    {
        $file = (string) $this->option('file');
        if (! str_contains($file, DIRECTORY_SEPARATOR)) {
            $file = base_path($file);
        }

        $limit = $this->option('limit');
        $limit = $limit !== null && $limit !== '' ? (int) $limit : null;
        $offset = max(0, (int) $this->option('offset'));

        if ($this->option('seed-only')) {
            $this->warn('Sadece kategori ve marka oluşturulacak.');
        } elseif ($this->option('products-only')) {
            $this->warn('Sadece ürün satırları aktarılacak (offset='.$offset.($limit ? ', limit='.$limit : '').').');
        } elseif (! $this->option('no-fresh')) {
            $this->warn('Mevcut ürün, kategori ve markalar silinip dosyadan yeniden kurulacak.');
        }

        if (! $this->option('no-fresh') && ! $this->option('products-only') && ! $this->option('force') && ! $this->confirm('Devam edilsin mi?', true)) {
            return self::SUCCESS;
        }

        $this->info('İçe aktarım başlıyor: '.$file);

        try {
            $stats = $importer->import(
                $file,
                fresh: ! $this->option('no-fresh'),
                limit: $limit,
                downloadImages: ! $this->option('skip-images'),
                offset: $offset,
                seedOnly: (bool) $this->option('seed-only'),
                productsOnly: (bool) $this->option('products-only'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrik', 'Adet'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if (! $this->option('skip-images')) {
            $this->comment('Görseller CSV “Görüntüler” sütunundaki URL’lerden indirildi (kapak + galeri).');
        }

        $this->info('Katalog içe aktarımı tamamlandı.');

        return self::SUCCESS;
    }
}
