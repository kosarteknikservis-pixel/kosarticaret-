<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAttributeMapping;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceExternalCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MappingBackupService
{
    /**
     * @return array<string, mixed>
     */
    public function export(?string $channelKey = null): array
    {
        $categoryQuery = MarketplaceCategoryMapping::query()->with('category:id,name');
        $brandQuery = MarketplaceBrandMapping::query()->with('brand:id,name');
        $attributeQuery = MarketplaceAttributeMapping::query()->with('category:id,name');
        $externalQuery = MarketplaceExternalCategory::query();

        if ($channelKey) {
            $categoryQuery->where('channel_key', $channelKey);
            $brandQuery->where('channel_key', $channelKey);
            $attributeQuery->where('channel_key', $channelKey);
            $externalQuery->where('channel_key', $channelKey);
        }

        return [
            'exported_at' => now()->toIso8601String(),
            'channel_key' => $channelKey,
            'category_mappings' => $categoryQuery->get()->map(fn (MarketplaceCategoryMapping $m) => [
                'channel_key' => $m->channel_key,
                'category_id' => $m->category_id,
                'category_name' => $m->category?->name,
                'external_category_id' => $m->external_category_id,
                'external_category_name' => $m->external_category_name,
                'external_category_path' => $m->external_category_path,
            ])->values()->all(),
            'brand_mappings' => $brandQuery->get()->map(fn (MarketplaceBrandMapping $m) => [
                'channel_key' => $m->channel_key,
                'brand_id' => $m->brand_id,
                'brand_name' => $m->brand?->name,
                'external_brand_id' => $m->external_brand_id,
                'external_brand_name' => $m->external_brand_name,
            ])->values()->all(),
            'attribute_mappings' => $attributeQuery->get()->map(fn (MarketplaceAttributeMapping $m) => [
                'channel_key' => $m->channel_key,
                'category_id' => $m->category_id,
                'category_name' => $m->category?->name,
                'local_spec_key' => $m->local_spec_key,
                'external_attribute_id' => $m->external_attribute_id,
                'external_attribute_name' => $m->external_attribute_name,
                'value_map' => $m->value_map,
            ])->values()->all(),
            'external_categories' => $externalQuery->get()->map(fn (MarketplaceExternalCategory $c) => [
                'channel_key' => $c->channel_key,
                'external_id' => $c->external_id,
                'name' => $c->name,
                'path' => $c->path,
                'parent_external_id' => $c->parent_external_id,
                'metadata' => $c->metadata,
            ])->values()->all(),
        ];
    }

    /**
     * @return array{category: int, brand: int, attribute: int, external: int}
     */
    public function importFromJson(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath() ?: '');

        if ($contents === false || trim($contents) === '') {
            throw new InvalidArgumentException('JSON dosyası okunamadı.');
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($contents, true);

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Geçersiz JSON formatı.');
        }

        $counts = ['category' => 0, 'brand' => 0, 'attribute' => 0, 'external' => 0];

        DB::transaction(function () use ($payload, &$counts): void {
            foreach ($payload['external_categories'] ?? [] as $row) {
                if (! is_array($row) || empty($row['channel_key']) || empty($row['external_id'])) {
                    continue;
                }

                MarketplaceExternalCategory::query()->updateOrCreate(
                    [
                        'channel_key' => $row['channel_key'],
                        'external_id' => (string) $row['external_id'],
                    ],
                    [
                        'name' => (string) ($row['name'] ?? $row['external_id']),
                        'path' => $row['path'] ?? null,
                        'parent_external_id' => $row['parent_external_id'] ?? null,
                        'metadata' => $row['metadata'] ?? null,
                        'synced_at' => now(),
                    ],
                );
                $counts['external']++;
            }

            foreach ($payload['category_mappings'] ?? [] as $row) {
                if (! is_array($row) || empty($row['channel_key']) || empty($row['category_id'])) {
                    continue;
                }

                MarketplaceCategoryMapping::query()->updateOrCreate(
                    [
                        'channel_key' => $row['channel_key'],
                        'category_id' => (int) $row['category_id'],
                    ],
                    [
                        'external_category_id' => (string) ($row['external_category_id'] ?? ''),
                        'external_category_name' => $row['external_category_name'] ?? null,
                        'external_category_path' => $row['external_category_path'] ?? null,
                    ],
                );
                $counts['category']++;
            }

            foreach ($payload['brand_mappings'] ?? [] as $row) {
                if (! is_array($row) || empty($row['channel_key']) || empty($row['brand_id'])) {
                    continue;
                }

                MarketplaceBrandMapping::query()->updateOrCreate(
                    [
                        'channel_key' => $row['channel_key'],
                        'brand_id' => (int) $row['brand_id'],
                    ],
                    [
                        'external_brand_id' => (string) ($row['external_brand_id'] ?? ''),
                        'external_brand_name' => $row['external_brand_name'] ?? null,
                    ],
                );
                $counts['brand']++;
            }

            foreach ($payload['attribute_mappings'] ?? [] as $row) {
                if (! is_array($row) || empty($row['channel_key']) || empty($row['category_id']) || empty($row['local_spec_key'])) {
                    continue;
                }

                MarketplaceAttributeMapping::query()->updateOrCreate(
                    [
                        'channel_key' => $row['channel_key'],
                        'category_id' => (int) $row['category_id'],
                        'local_spec_key' => (string) $row['local_spec_key'],
                    ],
                    [
                        'external_attribute_id' => (string) ($row['external_attribute_id'] ?? ''),
                        'external_attribute_name' => $row['external_attribute_name'] ?? null,
                        'value_map' => $row['value_map'] ?? null,
                    ],
                );
                $counts['attribute']++;
            }
        });

        return $counts;
    }
}
