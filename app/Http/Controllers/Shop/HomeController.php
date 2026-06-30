<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\HomeLayout;
use App\Support\Seo;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('shop.home', [
            'homeRows' => HomeLayout::rowsForHomepage(),
            'featuredBrands' => Brand::query()
                ->where('featured', true)
                ->where('active', true)
                ->orderBy('sort_order')
                ->get(),
            'recentBlogPosts' => BlogPost::published()->limit(3)->get(),
            'projectReferences' => \App\Models\ProjectReference::query()
                ->active()
                ->where('featured', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->get(),
            'siteStats' => [
                'products' => Product::query()->active()->count(),
                'brands' => Brand::query()->where('active', true)->count(),
                'categories' => Category::query()->where('active', true)->count(),
            ],
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
}
