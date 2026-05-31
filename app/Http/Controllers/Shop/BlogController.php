<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\CatalogPaginationSeo;
use App\Support\Seo;
use App\Support\SiteName;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = BlogPost::published()->paginate(12)->withQueryString();
        $paginationSeo = CatalogPaginationSeo::meta($request, $posts);

        return view('shop.blog.index', [
            'posts' => $posts,
            'metaTitle' => 'Blog',
            'metaDescription' => Seo::description([
                SiteName::get().' blog — pompa, hidrofor ve sulama rehberleri.',
            ]),
            'canonical' => route('blog.index'),
            'jsonLd' => [Seo::webPage('Blog', Seo::description(['Blog']), route('blog.index'))],
            ...$paginationSeo,
        ]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->published, 404);

        $breadcrumbs = [
            ['name' => 'Ana Sayfa', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $post->title],
        ];

        return view('shop.blog.show', [
            'post' => $post,
            'breadcrumbs' => $breadcrumbs,
            'metaTitle' => $post->meta_title ?: $post->title,
            'metaDescription' => Seo::description([$post->meta_description, $post->excerpt, $post->title]),
            'canonical' => route('blog.show', $post),
            'ogImage' => $post->imageUrl(),
            'jsonLd' => [Seo::article($post), Seo::breadcrumbs($breadcrumbs)],
        ]);
    }
}
