<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\MappingSuggestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceBrandMappingController extends Controller
{
    public function index(Request $request): View
    {
        $channelKey = $request->query('channel', 'trendyol');
        $search = trim((string) $request->query('q', ''));

        $brands = Brand::query()
            ->where('active', true)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get();

        $mappings = MarketplaceBrandMapping::query()
            ->where('channel_key', $channelKey)
            ->get()
            ->keyBy('brand_id');

        return view('admin.marketplace.mappings.brands', [
            'channels' => MarketplaceChannel::query()->orderBy('sort_order')->get(),
            'channelKey' => $channelKey,
            'brands' => $brands,
            'mappings' => $mappings,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
            'brand_id' => ['required', 'exists:brands,id'],
            'external_brand_id' => ['required', 'string', 'max:120'],
            'external_brand_name' => ['nullable', 'string', 'max:255'],
        ]);

        MarketplaceBrandMapping::query()->updateOrCreate(
            [
                'channel_key' => $data['channel_key'],
                'brand_id' => $data['brand_id'],
            ],
            [
                'external_brand_id' => $data['external_brand_id'],
                'external_brand_name' => $data['external_brand_name'] ?: Brand::query()->find($data['brand_id'])?->name,
            ],
        );

        return back()->with('success', 'Marka eşleştirmesi kaydedildi.');
    }

    public function destroy(MarketplaceBrandMapping $mapping): RedirectResponse
    {
        $mapping->delete();

        return back()->with('success', 'Marka eşleştirmesi silindi.');
    }

    public function suggest(Request $request, MappingSuggestService $suggest): RedirectResponse
    {
        $data = $request->validate([
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
        ]);

        $brands = Brand::query()->where('active', true)->get();
        $existing = MarketplaceBrandMapping::query()
            ->where('channel_key', $data['channel_key'])
            ->pluck('brand_id')
            ->all();

        $applied = 0;

        foreach ($brands as $brand) {
            if (in_array($brand->id, $existing, true)) {
                continue;
            }

            $item = $suggest->suggestBrand($brand, $data['channel_key']);

            MarketplaceBrandMapping::query()->create([
                'channel_key' => $data['channel_key'],
                'brand_id' => $item['brand_id'],
                'external_brand_id' => $item['external_id'],
                'external_brand_name' => $item['external_name'],
            ]);

            $applied++;
        }

        return back()->with('success', "{$applied} marka eşleştirmesi oluşturuldu.");
    }
}
