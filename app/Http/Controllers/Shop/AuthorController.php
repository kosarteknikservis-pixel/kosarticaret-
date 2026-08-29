<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\BlogAuthor;
use App\Support\Seo;
use App\Support\SiteName;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function show(string $author): View
    {
        $profile = BlogAuthor::find($author);
        abort_if($profile === null || $profile['name'] === '', 404);

        $posts = BlogPost::published()->latest('published_at')->limit(12)->get();

        $title = $profile['name'].' — '.SiteName::get().' Blog';

        return view('shop.authors.show', [
            'author' => $profile,
            'posts' => $posts,
            'metaTitle' => $title,
            'metaDescription' => Seo::description([
                $profile['bio'] ?: $profile['name'].' tarafından hazırlanan pompa ve hidrofor rehberleri.',
            ]),
            'canonical' => $profile['url'],
            'jsonLd' => array_filter([
                Seo::person($profile),
                Seo::webPage($profile['name'], Seo::description([$profile['bio']]), $profile['url']),
            ]),
            'breadcrumbs' => [
                ['name' => __('shop.home'), 'url' => route('home')],
                ['name' => __('shop.blog'), 'url' => route('blog.index')],
                ['name' => $profile['name']],
            ],
        ]);
    }
}
