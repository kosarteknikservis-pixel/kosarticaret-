<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProjectReference;
use App\Models\SiteSetting;
use App\Support\HomeLayout;
use App\Support\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('shop.home', [
            'homeRows' => $this->homeRows(),
            'featuredBrands' => Brand::query()
                ->where('featured', true)
                ->where('active', true)
                ->orderBy('sort_order')
                ->get(),
            'recentBlogPosts' => BlogPost::published()->limit(3)->get(),
            'projectReferences' => $this->projectReferences(),
            'siteStats' => $this->siteStats(),
            'metaTitle' => config('seo.homepage.title', \App\Support\SiteName::get()),
            'metaDescription' => Seo::description([
                SiteSetting::get('site_description'),
                config('seo.homepage.description'),
                config('kosar.description'),
            ]),
            'homeH1' => config('seo.homepage.h1', \App\Support\SiteName::get()),
            'canonical' => route('home'),
            'jsonLd' => [Seo::organization(), Seo::webSite(), Seo::onlineStore()],
        ]);
    }

    private function homeRows(): Collection
    {
        try {
            return HomeLayout::rowsForHomepage();
        } catch (Throwable $e) {
            Log::warning('homepage.home_rows_failed', ['message' => $e->getMessage()]);

            return collect();
        }
    }

    private function projectReferences(): Collection
    {
        if (! Schema::hasTable('project_references')) {
            return collect();
        }

        try {
            return ProjectReference::query()
                ->active()
                ->where('featured', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->get();
        } catch (Throwable $e) {
            Log::warning('homepage.project_references_failed', ['message' => $e->getMessage()]);

            return collect();
        }
    }

    /** @return array{products: int, brands: int, categories: int} */
    private function siteStats(): array
    {
        try {
            return [
                'products' => Product::query()->active()->count(),
                'brands' => Brand::query()->where('active', true)->count(),
                'categories' => Category::query()->where('active', true)->count(),
            ];
        } catch (Throwable $e) {
            Log::warning('homepage.site_stats_failed', ['message' => $e->getMessage()]);

            return [
                'products' => 0,
                'brands' => 0,
                'categories' => 0,
            ];
        }
    }
}
