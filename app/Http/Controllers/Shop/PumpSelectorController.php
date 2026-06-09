<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\PumpSelection\PumpRecommendationService;
use App\Support\Seo;
use App\Support\SiteName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PumpSelectorController extends Controller
{
    public function __construct(
        private PumpRecommendationService $recommendations,
    ) {}

    public function show(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if (SiteSetting::get('pump_selector_enabled', '1') === '0') {
            abort(404);
        }

        $applications = array_keys(config('pump_selector.applications', []));
        $preselected = $request->query('uygulama', $request->query('app'));
        if (! is_string($preselected) || ! in_array($preselected, $applications, true)) {
            $preselected = null;
        }

        return view('shop.pump-selector.index', [
            'preselectedApplication' => $preselected,
            'metaTitle' => __('shop.pump_selector_page_title'),
            'metaDescription' => Seo::description([
                __('shop.pump_selector_page_description'),
                SiteName::get(),
            ]),
            'canonical' => route('pump-selector.show'),
            'jsonLd' => [
                Seo::webSite(),
                Seo::breadcrumbs([
                    ['name' => __('shop.home'), 'url' => route('home')],
                    ['name' => __('shop.pump_selector_page_title')],
                ]),
            ],
        ]);
    }

    public function recommend(Request $request): JsonResponse
    {
        $applications = array_keys(config('pump_selector.applications', []));

        $validated = $request->validate([
            'application' => ['required', 'string', Rule::in($applications)],
            'apartments' => ['nullable', 'integer', 'min:1', 'max:500'],
            'floors' => ['nullable', 'integer', 'min:1', 'max:40'],
            'bathrooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'depth' => ['nullable', 'integer', 'min:1', 'max:200'],
            'suction_depth' => ['nullable', 'integer', 'min:1', 'max:8'],
            'volume_m3' => ['nullable', 'integer', 'min:1', 'max:500'],
            'drain_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'distance' => ['nullable', 'integer', 'min:5', 'max:100'],
            'area_m2' => ['nullable', 'integer', 'min:50', 'max:50000'],
            'heated_area_m2' => ['nullable', 'integer', 'min:40', 'max:2000'],
            'space_m2' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'height_m' => ['nullable', 'integer', 'min:2', 'max:15'],
            'usage' => ['nullable', 'string', 'max:32'],
            'method' => ['nullable', 'string', 'max:32'],
            'lift' => ['nullable', 'string', 'max:16'],
            'environment' => ['nullable', 'string', 'max:32'],
        ]);

        $application = (string) $validated['application'];
        unset($validated['application']);

        try {
            $result = $this->recommendations->recommend($application, $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'requirements' => $result['requirements'],
            'products' => $result['products'],
            'category_url' => $result['category_url'],
        ]);
    }
}
