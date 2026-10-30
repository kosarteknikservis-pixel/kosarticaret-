<?php

namespace App\Console\Commands;

use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PublishIdefixQueueCommand extends Command
{
    protected $signature = 'marketplace:idefix-publish-queue
        {--limit=0 : Maksimum yeni gönderim (0 = kalanın hepsi)}
        {--sleep=15 : Ürünler arası bekleme (saniye)}
        {--dry-run : API çağrısı yapmadan listele}';

    protected $description = 'Idefix kuyruğunu yavaş ve hatada durarak gönderir (Koşar yok, Troy→Sumak, stok≥1).';

    /**
     * TY/HB/N11 ile aynı ürün seti. SDF6 zaten pilot; listing varsa atlanır.
     *
     * @var list<array{sku: string, slug: string, price: int}>
     */
    private array $queue = [
        ['sku' => 'HMJH15', 'slug' => 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli', 'price' => 10990],
        ['sku' => 'SDF151', 'slug' => 'sumak-sdf-151-temiz-su-dalgic-pompa-monofaze-220v-15-hp', 'price' => 23599],
        ['sku' => 'HWD1100S', 'slug' => 'kaysu-hwd-1100s-paslanmaz-govdeli-kirli-su-dalgic-pompasi-15-hp-220-volt', 'price' => 14799],
        ['sku' => 'SMJH-150', 'slug' => 'sumak-smj-150-hidrofor-4-kat-6-daire-24-litre-tankli-hidrofor', 'price' => 17999],
        ['sku' => 'HMC145', 'slug' => 'kaysu-pompa-hmc145-65h-paket-hidrofor-8-kat-18-daire-100-litre-tankli', 'price' => 21999],
        ['sku' => '2HCP160', 'slug' => 'kaysu-2hcp-160-cift-kademeli-hidrofor-7-kat-14-daire-50-litre-tankli', 'price' => 18199],
        ['sku' => 'SMJ220', 'slug' => 'sumak-smj-220-jet-paket-hidrofor-8-kat-16-daire-50-litre-tankli', 'price' => 34199],
        ['sku' => 'HMJ15JET', 'slug' => 'troy-hmj-15-dokum-govdeli-jet-pompa-1-hp-49-mss-4-m3-h', 'price' => 8599],
        ['sku' => 'SMJK100', 'slug' => 'sumak-smjk-100-kendinden-emisli-jet-pompa-monofaze-220v-1hp', 'price' => 13599],
        ['sku' => 'SMAC600', 'slug' => 'sumak-smac-600-wc-3-unite-foseptik-tahliye-cihazi', 'price' => 31599],
        ['sku' => 'BC1550N', 'slug' => 'pedrollo-bc-1550n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa', 'price' => 41799],
        ['sku' => 'BCM1050N', 'slug' => 'pedrollo-bcm-1050n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa', 'price' => 35999],
        ['sku' => 'TOPMULTIAD', 'slug' => 'pedrollo-top-multi-ad-drenaj-dalgic-pompa-mazot-pompasi-27-mss-4-2-m3h', 'price' => 23199],
        ['sku' => 'SP100050', 'slug' => 'kaysu-sp100050-kapali-fanli-plastik-drenaj-dalgic-su-pompasi', 'price' => 6999],
        ['sku' => 'SDF152', 'slug' => 'sumak-sdf152-komple-paslanmaz-foseptik-dalgic-drenaj-pompa-220-volt-15-hp', 'price' => 26999],
        ['sku' => 'TS1212H', 'slug' => 'troy-ts-12-12-h-12v-karavan-hidroforu', 'price' => 7599],
        ['sku' => 'TS128H', 'slug' => 'troy-ts-12-8-h-12v-karavan-hidroforu', 'price' => 7999],
        ['sku' => 'STN2000G', 'slug' => 'sumak-stn2000g-sintine-dalgic-pompa-6-mss-4500-litre-h-12-volt-manuel', 'price' => 5999],
        ['sku' => 'HD500', 'slug' => 'sumak-hd-500-drenaj-dalgic-pompa-acik-carkli-monofaze-220v-370-watt', 'price' => 3999],
        ['sku' => 'WNP400AW', 'slug' => 'winpo-wnp-400-aw-flatorlu-drenaj-dalgic-pompa-6mss-7m3h-monofoze220v', 'price' => 5599],
        ['sku' => '4SDF4', 'slug' => 'sumak-4sdf4-07-hp-220v-keson-dalgici-1-cikisli', 'price' => 14199],
        ['sku' => 'WNP400AWGF', 'slug' => 'winpo-wnp-400-aw-gf-gizli-flatorlu-drenaj-dalgic-pompa-7mss-7m3h-monofaze-220v', 'price' => 5499],
        ['sku' => 'SDF500GF', 'slug' => 'sumak-sdf500-gf-sifirdan-emisli-gizli-samandirali-drenaj-pompasi-05-hp', 'price' => 12399],
        ['sku' => 'SDF6A', 'slug' => 'sumak-sdf-6a-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp', 'price' => 15199],
        ['sku' => 'SDF6', 'slug' => 'sumak-sdf-6-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp', 'price' => 11899],
        ['sku' => 'HQBM60', 'slug' => 'kaysu-hqbm60-preferikal-surtme-fanli-su-pompasi', 'price' => 3699],
        ['sku' => 'SM10', 'slug' => 'sumak-sm-10-preferikal-pompa-monofaze-220v-1hp', 'price' => 13922],
        ['sku' => 'HD1300-2', 'slug' => 'sumak-hd-1300-2-flatorlu-kirli-su-foseptik-dalgic-pompa-1-hp', 'price' => 11199],
        ['sku' => 'HD1700-2', 'slug' => 'sumak-hd-1700-2-flatorlu-kirli-su-foseptik-dalgic-pompa-15-hp', 'price' => 12599],
        ['sku' => 'SBRM18-2-P', 'slug' => 'sumak-sbrm-182p-parcalayici-bicakli-foseptik-dalgic-pompa-monofaze-220v-18-hp', 'price' => 33399],
        ['sku' => 'SDF102', 'slug' => 'sumak-sdf-102-paslanmaz-govdeli-foseptik-dalgic-pompa-monofaze-220-v-1-hp', 'price' => 18970],
        ['sku' => 'HKJMH', 'slug' => 'kaysu-pompa-hidrofor-050-hp-1-kat-1-daire', 'price' => 5625],
        ['sku' => 'HKJM', 'slug' => 'kaysu-pompa-hkjmh-10h-jet-hidrofor-4-kat-4-daire-24l-tankli', 'price' => 10125],
        ['sku' => 'SMJH-150-24', 'slug' => 'sumak-smj-150-jet-paket-hidrofor-5-kat-10-daire-24-litre-tankli', 'price' => 25313],
    ];

    public function handle(): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $sleep = max(0, (int) $this->option('sleep'));
        $dryRun = (bool) $this->option('dry-run');

        $sent = 0;
        $skipped = 0;
        $failed = [];

        foreach ($this->queue as $index => $item) {
            if ($limit > 0 && $sent >= $limit) {
                $this->info('Limit doldu (--limit='.$limit.'). Kalan sonraki çalıştırmada.');

                break;
            }

            $product = Product::query()->with('brand')->where('slug', $item['slug'])->first();
            if ($product === null) {
                // SDF152 slug varyantı
                if ($item['sku'] === 'SDF152') {
                    $product = Product::query()->with('brand')
                        ->where('slug', 'like', 'sumak-sdf15%paslanmaz-foseptik%')
                        ->orWhere('sku', 'SDF152')
                        ->first();
                }
            }
            if ($product === null) {
                $this->error('Ürün yok, duruldu: '.$item['sku'].' / '.$item['slug']);
                $failed[] = $item['sku'].' (ürün yok)';

                return self::FAILURE;
            }

            if ($this->alreadyOnIdefix($product->id)) {
                $this->line(sprintf('[%d/%d] ATLA %s (Idefix listing var)', $index + 1, count($this->queue), $item['sku']));
                $skipped++;

                continue;
            }

            $brandNote = '';
            $brandSlug = mb_strtolower((string) ($product->brand?->slug ?? ''), 'UTF-8');
            if ($brandSlug === 'troy' || str_starts_with($item['slug'], 'troy-')) {
                $brandNote = ' | Troy→Sumak + orijinal görsel';
            }

            $catMap = \App\Models\MarketplaceCategoryMapping::query()
                ->where('channel_key', 'idefix')
                ->whereIn('category_id', $product->categories()->pluck('categories.id'))
                ->get();
            $priority = [
                '771397498' => 1,
                '231078808' => 2,
                '530141372' => 3,
                '530114652' => 4,
                '530110498' => 5,
            ];
            $picked = $catMap->sortBy(fn ($m) => $priority[(string) $m->external_category_id] ?? 99)->first();
            $catNote = $picked
                ? sprintf(' | cat #%s %s', $picked->external_category_id, $picked->external_category_name)
                : ' | cat YOK';

            $this->info(sprintf(
                '[%d/%d] GÖNDER %s | %s | %d TL%s%s',
                $index + 1,
                count($this->queue),
                $item['sku'],
                $product->slug,
                $item['price'],
                $brandNote,
                $catNote
            ));

            if ($dryRun) {
                $sent++;

                continue;
            }

            $exit = Artisan::call('marketplace:publish-product', [
                'slug' => $product->slug,
                '--channel' => 'idefix',
                '--map-brand' => true,
                '--price' => (string) $item['price'],
            ]);
            $this->output->write(Artisan::output());

            if ($exit !== self::SUCCESS) {
                $failed[] = $item['sku'];
                $this->error('Durdu: '.$item['sku'].' (exit '.$exit.'). Öncekiler Idefix’te kaldı; hatalı ürün gönderilmedi.');
                $this->warn('Hatalı / gönderilmeyen: '.implode(', ', $failed));

                return self::FAILURE;
            }

            $sent++;

            if ($sleep > 0) {
                $hasMore = false;
                for ($j = $index + 1; $j < count($this->queue); $j++) {
                    $next = Product::query()->where('slug', $this->queue[$j]['slug'])->first();
                    if ($next !== null && ! $this->alreadyOnIdefix($next->id)) {
                        $hasMore = true;
                        break;
                    }
                }
                if ($hasMore && ($limit === 0 || $sent < $limit)) {
                    $this->line('Bekleniyor '.$sleep.' sn…');
                    sleep($sleep);
                }
            }
        }

        $this->info(sprintf('Idefix kuyruk bitti: gönderilen=%d atlanan=%d', $sent, $skipped));

        return self::SUCCESS;
    }

    private function alreadyOnIdefix(int $productId): bool
    {
        return MarketplaceListing::query()
            ->where('channel_key', 'idefix')
            ->where('product_id', $productId)
            ->whereIn('status', ['pending', 'published', 'approved'])
            ->exists();
    }
}
