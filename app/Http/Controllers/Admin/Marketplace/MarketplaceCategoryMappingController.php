<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceExternalCategory;
use App\Services\Marketplace\ExternalCategoryImporter;
use App\Services\Marketplace\MappingSuggestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class MarketplaceCategoryMappingController extends Controller
{
    public function index(Request $request): View
    {
        $channelKey = $request->query('channel', 'trendyol');
        $search = trim((string) $request->query('q', ''));

        $categories = Category::query()
            ->with('parent')
            ->where('active', true)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get();

        $mappings = MarketplaceCategoryMapping::query()
            ->where('channel_key', $channelKey)
            ->get()
            ->keyBy('category_id');

        $externalCategories = MarketplaceExternalCategory::query()
            ->where('channel_key', $channelKey)
            ->orderBy('path')
            ->orderBy('name')
            ->get();

        return view('admin.marketplace.mappings.categories', [
            'channels' => MarketplaceChannel::query()->orderBy('sort_order')->get(),
            'channelKey' => $channelKey,
            'categories' => $categories,
            'mappings' => $mappings,
            'externalCategories' => $externalCategories,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
            'category_id' => ['required', 'exists:categories,id'],
            'external_category_id' => ['required', 'string', 'max:120'],
            'external_category_name' => ['nullable', 'string', 'max:255'],
            'external_category_path' => ['nullable', 'string', 'max:500'],
        ]);

        $external = MarketplaceExternalCategory::query()
            ->where('channel_key', $data['channel_key'])
            ->where('external_id', $data['external_category_id'])
            ->first();

        MarketplaceCategoryMapping::query()->updateOrCreate(
            [
                'channel_key' => $data['channel_key'],
                'category_id' => $data['category_id'],
            ],
            [
                'external_category_id' => $data['external_category_id'],
                'external_category_name' => $data['external_category_name'] ?: $external?->name,
                'external_category_path' => $data['external_category_path'] ?: $external?->path,
            ],
        );

        return back()->with('success', 'Kategori eşleştirmesi kaydedildi.');
    }

    public function destroy(MarketplaceCategoryMapping $mapping): RedirectResponse
    {
        $mapping->delete();

        return back()->with('success', 'Kategori eşleştirmesi silindi.');
    }

    public function suggest(Request $request, MappingSuggestService $suggest): RedirectResponse
    {
        $data = $request->validate([
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
        ]);

        $categories = Category::query()->where('active', true)->get();
        $existing = MarketplaceCategoryMapping::query()
            ->where('channel_key', $data['channel_key'])
            ->pluck('category_id')
            ->all();

        $applied = 0;

        foreach ($suggest->suggestCategories($categories, $data['channel_key']) as $item) {
            if (in_array($item['category_id'], $existing, true)) {
                continue;
            }

            MarketplaceCategoryMapping::query()->create([
                'channel_key' => $data['channel_key'],
                'category_id' => $item['category_id'],
                'external_category_id' => $item['external_id'],
                'external_category_name' => $item['external_name'],
                'external_category_path' => $item['external_path'],
            ]);

            $applied++;
        }

        return back()->with('success', $applied > 0 ? "{$applied} kategori otomatik eşleştirildi." : 'Yeni otomatik eşleşme bulunamadı. Önce harici kategori listesini import edin.');
    }

    public function importExternal(Request $request, ExternalCategoryImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
            'json_file' => ['required', 'file', 'mimes:json,txt', 'max:20480'],
            'replace' => ['sometimes', 'boolean'],
        ]);

        try {
            $stats = $importer->importJson(
                $data['channel_key'],
                $request->file('json_file'),
                $request->boolean('replace'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['json_file' => $e->getMessage()]);
        }

        return back()->with('success', "{$stats['imported']} harici kategori import edildi ({$stats['skipped']} atlandı).");
    }
}
