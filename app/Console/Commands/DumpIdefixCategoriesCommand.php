<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Services\Marketplace\Idefix\IdefixApiClient;
use Illuminate\Console\Command;

class DumpIdefixCategoriesCommand extends Command
{
    protected $signature = 'marketplace:idefix-dump-categories
        {--contains=pompa,motor,hidro,dalgic,jet,bahce : Virgülle arama parçaları}';

    protected $description = 'Idefix leaf kategorileri dump (pompa/hidrofor eşlemesi için).';

    public function handle(IdefixApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'idefix')->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->error('Idefix yapılandırılmamış.');

            return self::FAILURE;
        }

        $needles = array_values(array_filter(array_map(
            fn ($p) => $this->fold(trim($p)),
            explode(',', (string) $this->option('contains'))
        )));

        $tree = $apiClient->forChannel($channel)->getProductCategories();
        $items = $tree['items'] ?? $tree;
        if (! is_array($items)) {
            $this->error('Kategori ağacı boş.');

            return self::FAILURE;
        }

        $leaves = $this->flattenLeaves($items);
        $matched = 0;
        foreach ($leaves as $leaf) {
            $hay = $this->fold($leaf['path'].' '.$leaf['name']);
            $ok = $needles === [];
            foreach ($needles as $n) {
                if ($n !== '' && str_contains($hay, $n)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                continue;
            }
            $matched++;
            $this->line(sprintf('#%d | %s | %s', $leaf['id'], $leaf['name'], $leaf['path']));
        }

        $this->info("Leaf eşleşen: {$matched} / toplam ".count($leaves));

        return self::SUCCESS;
    }

    /**
     * @param  list<mixed>|array<string, mixed>  $nodes
     * @return list<array{id: int, name: string, path: string}>
     */
    private function flattenLeaves(array $nodes, string $prefix = ''): array
    {
        $out = [];
        $list = array_is_list($nodes) ? $nodes : (isset($nodes[0]) ? $nodes : [$nodes]);

        foreach ($list as $node) {
            if (! is_array($node)) {
                continue;
            }
            if (isset($node['items']) && is_array($node['items']) && ! isset($node['id'])) {
                $out = array_merge($out, $this->flattenLeaves($node['items'], $prefix));
                continue;
            }
            $id = (int) ($node['id'] ?? 0);
            $name = trim((string) ($node['name'] ?? ''));
            $path = $prefix === '' ? $name : ($prefix.' > '.$name);
            $subs = $node['subs'] ?? [];
            if (! is_array($subs) || $subs === []) {
                if ($id > 0 && $name !== '') {
                    $out[] = ['id' => $id, 'name' => $name, 'path' => $path];
                }
                continue;
            }
            $out = array_merge($out, $this->flattenLeaves($subs, $path));
        }

        return $out;
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        return strtr($value, [
            'ş' => 's', 'ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c',
            'Ş' => 's', 'İ' => 'i', 'I' => 'i', 'Ğ' => 'g', 'Ü' => 'u', 'Ö' => 'o', 'Ç' => 'c',
        ]);
    }
}
