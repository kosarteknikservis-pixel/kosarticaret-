<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Seo;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->published, 404);

        $breadcrumbs = [
            ['name' => 'Ana Sayfa', 'url' => route('home')],
            ['name' => $page->title],
        ];

        return view('shop.pages.show', [
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
            'metaTitle' => $page->meta_title ?: $page->title,
            'metaDescription' => Seo::description([$page->meta_description, strip_tags($page->content), $page->title]),
            'canonical' => route('pages.show', $page),
            'jsonLd' => [
                Seo::webPage($page->title, Seo::description([$page->meta_description, strip_tags($page->content)]), route('pages.show', $page)),
                Seo::breadcrumbs($breadcrumbs),
            ],
        ]);
    }
}
