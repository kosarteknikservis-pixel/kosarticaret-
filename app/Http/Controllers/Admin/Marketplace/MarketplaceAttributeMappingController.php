<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MarketplaceAttributeMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\ExternalCategoryImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceAttributeMappingController extends Controller
{
    public function index(Request $request, ExternalCategoryImporter $importer): View
    {
        $channelKey = $request->query('channel', 'trendyol');
        $categoryId = (int) $request->query('category_id', 0);

        $mappedCategories = MarketplaceCategoryMapping::query()
            ->where('channel_key', $channelKey)
            ->with('category:id,name')
            ->get();

        $specKeys = $categoryId > 0 ? $importer->discoverSpecKeysForCategory($categoryId) : [];

        $mappings = MarketplaceAttributeMapping::query()
            ->where('channel_key', $channelKey)
            ->when($categoryId > 0, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('local_spec_key')
            ->get();

        return view('admin.marketplace.mappings.attributes', [
            'channels' => MarketplaceChannel::query()->orderBy('sort_order')->get(),
            'channelKey' => $channelKey,
            'categoryId' => $categoryId,
            'mappedCategories' => $mappedCategories,
            'specKeys' => $specKeys,
            'mappings' => $mappings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
            'category_id' => ['required', 'exists:categories,id'],
            'local_spec_key' => ['required', 'string', 'max:120'],
            'external_attribute_id' => ['required', 'string', 'max:120'],
            'external_attribute_name' => ['nullable', 'string', 'max:255'],
            'value_map' => ['nullable', 'string'],
        ]);

        $valueMap = null;
        if (! empty($data['value_map'])) {
            $decoded = json_decode($data['value_map'], true);
            $valueMap = is_array($decoded) ? $decoded : null;
        }

        MarketplaceAttributeMapping::query()->updateOrCreate(
            [
                'channel_key' => $data['channel_key'],
                'category_id' => $data['category_id'],
                'local_spec_key' => $data['local_spec_key'],
            ],
            [
                'external_attribute_id' => $data['external_attribute_id'],
                'external_attribute_name' => $data['external_attribute_name'],
                'value_map' => $valueMap,
            ],
        );

        return back()->with('success', 'Özellik eşleştirmesi kaydedildi.');
    }

    public function destroy(MarketplaceAttributeMapping $mapping): RedirectResponse
    {
        $mapping->delete();

        return back()->with('success', 'Özellik eşleştirmesi silindi.');
    }
}
