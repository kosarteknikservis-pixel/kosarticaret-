<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Services\Marketplace\PttAvm\PttAvmApiClient;
use Illuminate\Console\Command;

class DumpPttAvmCategoriesCommand extends Command
{
    protected $signature = 'marketplace:pttavm-dump-categories
        {--contains=pompa,motor,hidro,dalgic,jet,bahce,santrif,prefer,su : Virgülle arama kelimeleri}';

    protected $description = 'PTT AVM kategori ağacından pompa leaf’lerini listeler.';

    public function handle(PttAvmApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'pttavm')->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->error('pttavm kanalı yok veya API bilgileri eksik.');

            return self::FAILURE;
        }

        $needles = array_values(array_filter(array_map(
            fn ($s) => mb_strtolower(trim($s), 'UTF-8'),
            explode(',', (string) $this->option('contains'))
        )));

        try {
            $client = $apiClient->forChannel($channel);
            $all = [];
            foreach ($needles as $needle) {
                foreach ($client->searchLeafCategories($needle, 80) as $leaf) {
                    $all[$leaf['id']] = $leaf;
                }
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Leaf eşleşen: '.count($all));
        foreach ($all as $leaf) {
            $this->line(sprintf('#%d | %s | %s', $leaf['id'], $leaf['name'], $leaf['path']));
        }

        return self::SUCCESS;
    }
}
