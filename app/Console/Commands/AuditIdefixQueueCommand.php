<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\Idefix\IdefixApiClient;
use Illuminate\Console\Command;

class AuditIdefixQueueCommand extends Command
{
    protected $signature = 'marketplace:idefix-audit-queue
        {--check-batch : Her listing için batch-result sorgula}
        {--sleep=1 : Batch sorguları arası bekleme}';

    protected $description = 'Idefix kuyruk ürünlerinin listing / kategori / batch durumunu raporlar.';

    /** @var list<array{sku: string, slug: string}> */
    private array $queue = [
        ['sku' => 'HMJH15', 'slug' => 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli'],
        ['sku' => 'SDF151', 'slug' => 'sumak-sdf-151-temiz-su-dalgic-pompa-monofaze-220v-15-hp'],
        ['sku' => 'HWD1100S', 'slug' => 'kaysu-hwd-1100s-paslanmaz-govdeli-kirli-su-dalgic-pompasi-15-hp-220-volt'],
        ['sku' => 'SMJH-150', 'slug' => 'sumak-smj-150-hidrofor-4-kat-6-daire-24-litre-tankli-hidrofor'],
        ['sku' => 'HMC145', 'slug' => 'kaysu-pompa-hmc145-65h-paket-hidrofor-8-kat-18-daire-100-litre-tankli'],
        ['sku' => '2HCP160', 'slug' => 'kaysu-2hcp-160-cift-kademeli-hidrofor-7-kat-14-daire-50-litre-tankli'],
        ['sku' => 'SMJ220', 'slug' => 'sumak-smj-220-jet-paket-hidrofor-8-kat-16-daire-50-litre-tankli'],
        ['sku' => 'HMJ15JET', 'slug' => 'troy-hmj-15-dokum-govdeli-jet-pompa-1-hp-49-mss-4-m3-h'],
        ['sku' => 'SMJK100', 'slug' => 'sumak-smjk-100-kendinden-emisli-jet-pompa-monofaze-220v-1hp'],
        ['sku' => 'SMAC600', 'slug' => 'sumak-smac-600-wc-3-unite-foseptik-tahliye-cihazi'],
        ['sku' => 'BC1550N', 'slug' => 'pedrollo-bc-1550n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa'],
        ['sku' => 'BCM1050N', 'slug' => 'pedrollo-bcm-1050n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa'],
        ['sku' => 'TOPMULTIAD', 'slug' => 'pedrollo-top-multi-ad-drenaj-dalgic-pompa-mazot-pompasi-27-mss-4-2-m3h'],
        ['sku' => 'SP100050', 'slug' => 'kaysu-sp100050-kapali-fanli-plastik-drenaj-dalgic-su-pompasi'],
        ['sku' => 'SDF152', 'slug' => 'sumak-sdf152-komple-paslanmaz-foseptik-dalgic-drenaj-pompa-220-volt-15-hp'],
        ['sku' => 'TS1212H', 'slug' => 'troy-ts-12-12-h-12v-karavan-hidroforu'],
        ['sku' => 'TS128H', 'slug' => 'troy-ts-12-8-h-12v-karavan-hidroforu'],
        ['sku' => 'STN2000G', 'slug' => 'sumak-stn2000g-sintine-dalgic-pompa-6-mss-4500-litre-h-12-volt-manuel'],
        ['sku' => 'HD500', 'slug' => 'sumak-hd-500-drenaj-dalgic-pompa-acik-carkli-monofaze-220v-370-watt'],
        ['sku' => 'WNP400AW', 'slug' => 'winpo-wnp-400-aw-flatorlu-drenaj-dalgic-pompa-6mss-7m3h-monofoze220v'],
        ['sku' => '4SDF4', 'slug' => 'sumak-4sdf4-07-hp-220v-keson-dalgici-1-cikisli'],
        ['sku' => 'WNP400AWGF', 'slug' => 'winpo-wnp-400-aw-gf-gizli-flatorlu-drenaj-dalgic-pompa-7mss-7m3h-monofaze-220v'],
        ['sku' => 'SDF500GF', 'slug' => 'sumak-sdf500-gf-sifirdan-emisli-gizli-samandirali-drenaj-pompasi-05-hp'],
        ['sku' => 'SDF6A', 'slug' => 'sumak-sdf-6a-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp'],
        ['sku' => 'SDF6', 'slug' => 'sumak-sdf-6-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp'],
        ['sku' => 'HQBM60', 'slug' => 'kaysu-hqbm60-preferikal-surtme-fanli-su-pompasi'],
        ['sku' => 'SM10', 'slug' => 'sumak-sm-10-preferikal-pompa-monofaze-220v-1hp'],
        ['sku' => 'HD1300-2', 'slug' => 'sumak-hd-1300-2-flatorlu-kirli-su-foseptik-dalgic-pompa-1-hp'],
        ['sku' => 'HD1700-2', 'slug' => 'sumak-hd-1700-2-flatorlu-kirli-su-foseptik-dalgic-pompa-15-hp'],
        ['sku' => 'SBRM18-2-P', 'slug' => 'sumak-sbrm-182p-parcalayici-bicakli-foseptik-dalgic-pompa-monofaze-220v-18-hp'],
        ['sku' => 'SDF102', 'slug' => 'sumak-sdf-102-paslanmaz-govdeli-foseptik-dalgic-pompa-monofaze-220-v-1-hp'],
        ['sku' => 'HKJMH', 'slug' => 'kaysu-pompa-hidrofor-050-hp-1-kat-1-daire'],
        ['sku' => 'HKJM', 'slug' => 'kaysu-pompa-hkjmh-10h-jet-hidrofor-4-kat-4-daire-24l-tankli'],
        ['sku' => 'SMJH-150-24', 'slug' => 'sumak-smj-150-jet-paket-hidrofor-5-kat-10-daire-24-litre-tankli'],
    ];

    public function handle(IdefixApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'idefix')->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->error('Idefix kanalı yok/yapılandırılmamış.');

            return self::FAILURE;
        }

        $client = null;
        if ($this->option('check-batch')) {
            $client = $apiClient->forChannel($channel);
        }

        $missing = [];
        $wrongCat = [];
        $batchFail = [];
        $ok = 0;
        $sleep = max(0, (int) $this->option('sleep'));

        foreach ($this->queue as $item) {
            $product = Product::query()->where('slug', $item['slug'])->first();
            if ($product === null && $item['sku'] === 'SDF152') {
                $product = Product::query()
                    ->where('slug', 'like', 'sumak-sdf15%paslanmaz-foseptik%')
                    ->orWhere('sku', 'SDF152')
                    ->first();
            }

            if ($product === null) {
                $this->error('YOK ürün: '.$item['sku']);
                $missing[] = $item['sku'].' (ürün)';

                continue;
            }

            $listing = MarketplaceListing::query()
                ->where('channel_key', 'idefix')
                ->where('product_id', $product->id)
                ->first();

            if ($listing === null) {
                $this->warn(sprintf('%s | listing YOK', $item['sku']));
                $missing[] = $item['sku'];

                continue;
            }

            $catId = (int) data_get($listing->payload_snapshot, 'products.0.categoryId', 0);
            $catLabel = match ($catId) {
                530114652 => 'Dalgıç Pompa',
                231078808 => 'Hidroforlar',
                771397498 => 'Tekne Hidrofor',
                530141372 => 'Santrifüj',
                530110498 => 'Su Motorları',
                530173799 => 'YANLIŞ Bahçe Hidrofonları',
                default => 'bilinmeyen',
            };

            $line = sprintf(
                '%s | status=%s | cat=#%d %s | batch=%s',
                $item['sku'],
                $listing->status,
                $catId,
                $catLabel,
                (string) ($listing->external_product_id ?: '—')
            );

            if ($catId === 530173799) {
                $this->error($line);
                $wrongCat[] = $item['sku'];
            } elseif ($listing->status === 'error') {
                $this->error($line.' | '.$listing->last_error);
                $batchFail[] = $item['sku'].' (error)';
            } else {
                $this->line($line);
            }

            if ($client !== null && $listing->external_product_id) {
                try {
                    $result = $client->batchResult((string) $listing->external_product_id);
                    $status = (string) data_get($result, 'status', '—');
                    $products = data_get($result, 'products') ?? data_get($result, 'items') ?? [];
                    $fail = '';
                    if (is_array($products)) {
                        foreach ($products as $row) {
                            if (! is_array($row)) {
                                continue;
                            }
                            $reasons = $row['failureReasons'] ?? $row['status'] ?? null;
                            if (is_array($reasons) && $reasons !== []) {
                                $fail = json_encode($reasons, JSON_UNESCAPED_UNICODE) ?: '';
                            } elseif (is_string($reasons) && ! in_array(mb_strtolower($reasons), ['', 'success', 'matched', 'approved', 'completed'], true)) {
                                $pool = (string) ($row['status'] ?? $row['poolState'] ?? '');
                                if (str_contains(mb_strtolower($pool), 'not_matched')
                                    || str_contains(mb_strtolower($pool), 'fail')
                                    || str_contains(mb_strtolower((string) $reasons), 'not_matched')) {
                                    $fail = $pool !== '' ? $pool : (string) $reasons;
                                }
                            }
                            $poolState = mb_strtolower((string) ($row['status'] ?? $row['poolState'] ?? ''));
                            if (str_contains($poolState, 'not_matched') || str_contains($poolState, 'rejected')) {
                                $fail = $poolState;
                            }
                        }
                    }
                    if ($fail !== '') {
                        $this->warn('  batch '.$status.' FAIL: '.$fail);
                        $batchFail[] = $item['sku'].' ('.$fail.')';
                    } else {
                        $this->info('  batch '.$status.' OK');
                        $ok++;
                    }
                } catch (\Throwable $e) {
                    $this->warn('  batch hata: '.$e->getMessage());
                }
                if ($sleep > 0) {
                    sleep($sleep);
                }
            } elseif ($listing->status !== 'error' && $catId !== 530173799) {
                $ok++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Özet: ok≈%d | listing_yok=%d | yanlis_cat=%d | batch_sorun=%d',
            $ok,
            count($missing),
            count($wrongCat),
            count($batchFail)
        ));
        if ($missing !== []) {
            $this->warn('Eksik: '.implode(', ', $missing));
        }
        if ($wrongCat !== []) {
            $this->warn('Yanlış kategori: '.implode(', ', $wrongCat));
        }
        if ($batchFail !== []) {
            $this->warn('Batch sorun: '.implode(', ', $batchFail));
        }

        return ($missing === [] && $wrongCat === [] && $batchFail === [])
            ? self::SUCCESS
            : self::FAILURE;
    }
}
