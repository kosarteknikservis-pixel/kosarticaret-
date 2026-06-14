<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceExternalCategory;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ExternalCategoryImporter
{
    /**
     * @return array{imported: int, skipped: int}
     */
    public function importJson(string $channelKey, UploadedFile $file, bool $replace = false): array
    {
        $contents = file_get_contents($file->getRealPath() ?: '');

        if ($contents === false) {
            throw new InvalidArgumentException('Dosya okunamadı.');
        }

        /** @var array<int, array<string, mixed>>|null $rows */
        $rows = json_decode($contents, true);

        if (! is_array($rows)) {
            throw new InvalidArgumentException('JSON bir dizi olmalıdır.');
        }

        if ($replace) {
            MarketplaceExternalCategory::query()->where('channel_key', $channelKey)->delete();
        }

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['id'])) {
                $skipped++;
                continue;
            }

            MarketplaceExternalCategory::query()->updateOrCreate(
                [
                    'channel_key' => $channelKey,
                    'external_id' => (string) $row['id'],
                ],
                [
                    'name' => (string) ($row['name'] ?? $row['id']),
                    'path' => $row['path'] ?? null,
                    'parent_external_id' => isset($row['parent_id']) ? (string) $row['parent_id'] : null,
                    'metadata' => $row['metadata'] ?? null,
                    'synced_at' => now(),
                ],
            );

            $imported++;
        }

        return compact('imported', 'skipped');
    }

    /**
     * @return list<string>
     */
    public function discoverSpecKeysForCategory(int $categoryId): array
    {
        $keys = [];

        Product::query()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->whereNotNull('specs')
            ->select(['specs'])
            ->chunkById(200, function ($products) use (&$keys): void {
                foreach ($products as $product) {
                    foreach (array_keys($product->specs ?? []) as $key) {
                        $keys[(string) $key] = true;
                    }
                }
            });

        return array_keys($keys);
    }
}
