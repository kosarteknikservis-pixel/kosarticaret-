<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('products.index'), 'priority' => '0.9'],
            ['loc' => route('categories.index'), 'priority' => '0.8'],
            ['loc' => route('brands.index'), 'priority' => '0.8'],
            ['loc' => route('blog.index'), 'priority' => '0.7'],
            ['loc' => route('contact.show'), 'priority' => '0.6'],
        ]);

        Product::query()->active()->select('slug', 'updated_at')->orderBy('id')->chunk(100, function ($chunk) use ($urls) {
            foreach ($chunk as $p) {
                $urls->push(['loc' => route('products.show', $p), 'lastmod' => $p->updated_at->toAtomString(), 'priority' => '0.8']);
            }
        });

        Category::query()->where('active', true)->select('slug', 'updated_at')->each(function ($c) use ($urls) {
            $urls->push(['loc' => $c->storefrontUrl(), 'lastmod' => $c->updated_at->toAtomString(), 'priority' => '0.7']);
        });

        Brand::query()->where('active', true)->select('slug', 'updated_at')->each(function ($b) use ($urls) {
            $urls->push(['loc' => route('brands.show', $b), 'lastmod' => $b->updated_at->toAtomString(), 'priority' => '0.7']);
        });

        BlogPost::published()->select('slug', 'updated_at')->each(function ($post) use ($urls) {
            $urls->push(['loc' => route('blog.show', $post), 'lastmod' => $post->updated_at->toAtomString(), 'priority' => '0.6']);
        });

        Page::query()->where('published', true)->select('slug', 'updated_at')->each(function ($page) use ($urls) {
            $urls->push(['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at->toAtomString(), 'priority' => '0.5']);
        });

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /yonetim',
            'Disallow: /odeme',
            'Disallow: /sepet',
            'Disallow: /sepet/ajax',
            'Disallow: /ara',
            'Disallow: /favoriler',
            'Disallow: /hesabim',
            'Disallow: /giris',
            'Disallow: /kayit',
            '',
            'Sitemap: '.Seo::absolute('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
