<?php



namespace App\Http\Controllers\Shop;



use App\Http\Controllers\Controller;

use App\Models\Brand;

use App\Models\Product;

use App\Services\AnalyticsTracker;
use App\Services\CatalogQuery;

use App\Services\Payment\InstallmentOptionsService;

use App\Support\CatalogPaginationSeo;

use App\Support\Seo;
use App\Support\SiteName;

use Illuminate\Http\Request;

use Illuminate\View\View;



class ProductController extends Controller

{

    public function index(Request $request): View

    {

        $query = CatalogQuery::products()->with('brand', 'categories');

        CatalogQuery::apply($request, $query);



        $products = $query->paginate(12)->withQueryString();

        $paginationSeo = CatalogPaginationSeo::meta($request, $products);



        return view('shop.products.index', [

            'products' => $products,

            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),

            'search' => $request->string('q'),

            'metaTitle' => 'Tüm Ürünler',

            'metaDescription' => Seo::description([

                'Tüm ürünler — '.SiteName::get().' kataloğu. Pompa, hidrofor, fan ve sulama ekipmanları.',

            ]),

            'canonical' => route('products.index'),

            'jsonLd' => array_filter([

                Seo::webPage('Tüm Ürünler', Seo::description(['Ürün kataloğu']), route('products.index')),

                Seo::itemListProducts($products, route('products.index'), $products->total()),

            ]),

            ...$paginationSeo,

        ]);

    }



    public function show(Request $request, Product $product): View

    {

        abort_unless($product->is_active, 404);

        app(AnalyticsTracker::class)->trackProductView($request, $product);



        $product->load(['brand', 'categories', 'images', 'approvedReviews']);

        $categoryName = $product->categories->first()?->name;



        $related = CatalogQuery::products()

            ->where('id', '!=', $product->id)

            ->when(

                $product->categories->isNotEmpty(),

                fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $product->categories->pluck('id'))),

                fn ($q) => $q->where('featured', true),

            )

            ->take(6)

            ->get();



        $breadcrumbs = [

            ['name' => 'Ana Sayfa', 'url' => route('home')],

            ['name' => 'Ürünler', 'url' => route('products.index')],

        ];

        if ($categoryName && $product->categories->first()) {

            $breadcrumbs[] = ['name' => $categoryName, 'url' => $product->categories->first()->storefrontUrl()];

        }

        $breadcrumbs[] = ['name' => $product->name];



        $jsonLd = [

            Seo::product($product),

            Seo::breadcrumbs($breadcrumbs),

            ...Seo::productReviews($product),

        ];



        $installmentTable = app(InstallmentOptionsService::class)
            ->forAmount((float) $product->price);

        return view('shop.products.show', [

            'product' => $product,

            'installmentTable' => $installmentTable,

            'related' => $related,

            'breadcrumbs' => $breadcrumbs,

            'metaTitle' => $product->meta_title ?: $product->name,

            'metaDescription' => Seo::description([

                $product->meta_description,

                $product->short_description,

                $product->description,

                $product->name,

            ]),

            'metaKeywords' => Seo::keywords([$product->tags, $product->name, $product->brand?->name, $product->categories->pluck('name')->all()]),

            'canonical' => route('products.show', $product),

            'ogType' => 'product',

            'ogImage' => Seo::productImages($product)[0] ?? null,

            'productPrice' => number_format((float) $product->price, 2, '.', ''),

            'jsonLd' => $jsonLd,

        ]);

    }

}


