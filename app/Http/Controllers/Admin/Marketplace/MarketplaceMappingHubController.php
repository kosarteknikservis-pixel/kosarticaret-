<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MarketplaceAttributeMapping;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceExternalCategory;
use App\Services\Marketplace\MarketplaceManager;
use Illuminate\View\View;

class MarketplaceMappingHubController extends Controller
{
    public function __invoke(MarketplaceManager $manager): View
    {
        $channels = $manager->channels();
        $channelKey = request()->query('channel', $channels->first()?->key ?? 'trendyol');

        $totalCategories = Category::query()->where('active', true)->count();
        $mappedCategories = MarketplaceCategoryMapping::query()->where('channel_key', $channelKey)->count();
        $totalBrands = \App\Models\Brand::query()->where('active', true)->count();
        $mappedBrands = MarketplaceBrandMapping::query()->where('channel_key', $channelKey)->count();
        $attributeMappings = MarketplaceAttributeMapping::query()->where('channel_key', $channelKey)->count();
        $externalCategories = MarketplaceExternalCategory::query()->where('channel_key', $channelKey)->count();

        return view('admin.marketplace.mappings.index', [
            'channels' => $channels,
            'channelKey' => $channelKey,
            'stats' => [
                'categories' => ['total' => $totalCategories, 'mapped' => $mappedCategories],
                'brands' => ['total' => $totalBrands, 'mapped' => $mappedBrands],
                'attributes' => $attributeMappings,
                'external_categories' => $externalCategories,
            ],
        ]);
    }
}
