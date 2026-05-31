<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
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
            'metaTitle' => \App\Support\SiteName::get(),
            'metaDescription' => Seo::description([
                SiteSetting::get('site_description'),
                config('kosar.description'),
            ]),
            'canonical' => route('home'),
            'jsonLd' => [Seo::organization(), Seo::webSite(), Seo::onlineStore()],
        ]);
    }
}
