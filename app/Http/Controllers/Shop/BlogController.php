<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Product;
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

        $suggestedProducts = $this->suggestedProductsForPost($post);

        $breadcrumbs = [
            ['name' => 'Ana Sayfa', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $post->title],
        ];

        return view('shop.blog.show', [
            'post' => $post,
            'suggestedProducts' => $suggestedProducts,
            'breadcrumbs' => $breadcrumbs,
            'metaTitle' => $post->meta_title ?: $post->title,
            'metaDescription' => Seo::description([$post->meta_description, $post->excerpt, $post->title]),
            'canonical' => route('blog.show', $post),
            'ogImageMeta' => Seo::openGraphImage($post->image, 'blog-card', (string) ($post->image_alt ?: $post->title)),
            'ogImage' => $post->imageUrl('blog-card') ?? $post->imageUrl(),
            'jsonLd' => [Seo::article($post), Seo::breadcrumbs($breadcrumbs)],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function suggestedProductsForPost(BlogPost $post)
    {
        $keywords = collect($post->tags ?? [])
            ->merge(preg_split('/\s+/u', mb_strtolower($post->title, 'UTF-8')) ?: [])
            ->filter(fn ($word) => is_string($word) && mb_strlen($word) >= 4)
            ->unique()
            ->take(8)
            ->values();

        if ($keywords->isEmpty()) {
            return Product::query()->active()->where('featured', true)->inRandomOrder()->limit(4)->get();
        }

        $query = Product::query()->active()->with('brand');

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $like = '%'.$keyword.'%';
                $q->orWhere('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like);
            }
        });

        $products = $query->orderByDesc('stock')->orderByDesc('featured')->limit(4)->get();

        if ($products->count() < 4) {
            $products = $products->concat(
                Product::query()
                    ->active()
                    ->with('brand')
                    ->whereNotIn('id', $products->pluck('id'))
                    ->where('featured', true)
                    ->orderByDesc('stock')
                    ->limit(4 - $products->count())
                    ->get()
            );
        }

        return $products;
    }
}
