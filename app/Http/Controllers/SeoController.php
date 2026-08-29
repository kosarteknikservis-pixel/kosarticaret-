<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\GoogleProductCategory;
use App\Support\ImageSitemapGenerator;
use App\Support\LlmsTxt;
use App\Support\Seo;
use App\Support\SitemapGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $cacheSeconds = (int) config('seo.sitemap_cache_seconds', 3600);

        $xml = Cache::remember('seo.sitemap.xml', $cacheSeconds, function (): string {
            return view('seo.sitemap-index', [
                'entries' => SitemapGenerator::indexEntries(),
            ])->render();
        });

        return response($xml, 200, $this->xmlHeaders($cacheSeconds));
    }

    public function imageSitemap(): Response
    {
        $cacheSeconds = (int) config('seo.sitemap_cache_seconds', 3600);

        $xml = Cache::remember('seo.sitemap.images.xml', $cacheSeconds, function (): string {
            return view('seo.sitemap-images', [
                'entries' => ImageSitemapGenerator::allEntries(),
            ])->render();
        });

        return response($xml, 200, $this->xmlHeaders($cacheSeconds));
    }

    public function sitemapChunk(string $chunk): Response
    {
        $cacheSeconds = (int) config('seo.sitemap_cache_seconds', 3600);
        $cacheKey = 'seo.sitemap.chunk.'.$chunk;

        $xml = Cache::remember($cacheKey, $cacheSeconds, function () use ($chunk): ?string {
            $urls = SitemapGenerator::chunkUrls($chunk);
            if ($urls->isEmpty()) {
                return null;
            }

            return view('seo.sitemap', ['urls' => $urls->all()])->render();
        });

        if ($xml === null) {
            abort(404);
        }

        return response($xml, 200, $this->xmlHeaders($cacheSeconds));
    }

    /** @return array<string, string> */
    private function xmlHeaders(int $cacheSeconds): array
    {
        return [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.$cacheSeconds,
        ];
    }

    public function robots(): Response
    {
        $cacheSeconds = (int) config('seo.robots_cache_seconds', 86400);

        $body = Cache::remember('seo.robots.txt', $cacheSeconds, function (): string {
            $lines = [
                'User-agent: GPTBot',
                'Allow: /',
                '',
                'User-agent: ChatGPT-User',
                'Allow: /',
                '',
                'User-agent: OAI-SearchBot',
                'Allow: /',
                '',
                'User-agent: ClaudeBot',
                'Allow: /',
                '',
                'User-agent: PerplexityBot',
                'Allow: /',
                '',
                'User-agent: Google-Extended',
                'Allow: /',
                '',
                'User-agent: Claude-SearchBot',
                'Allow: /',
                '',
                'User-agent: Perplexity-User',
                'Allow: /',
                '',
                'User-agent: Applebot-Extended',
                'Allow: /',
                '',
                'User-agent: Amazonbot',
                'Allow: /',
                '',
                'User-agent: cohere-ai',
                'Allow: /',
                '',
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
                'Disallow: /siparis-takip',
                'Disallow: /siparis-onay',
                'Disallow: /karsilastir',
                'Disallow: /urun-kategori',
                'Disallow: /urun-etiket',
                'Disallow: /tag/',
                'Disallow: /page/',
                'Disallow: /magaza',
                'Disallow: /shop',
                'Disallow: /*?add-to-cart*',
                'Disallow: /*?marka=',
                'Disallow: /*?siralama=',
                'Disallow: /*?min=',
                'Disallow: /*?max=',
                'Disallow: /*?stokta=',
                'Disallow: /*?grid=',
                'Disallow: /urun-feed.xml',
                '',
                '# llms.txt: '.Seo::absolute('/llms.txt'),
                '',
                'Sitemap: '.Seo::absolute('/sitemap.xml'),
            ];

            return implode("\n", $lines);
        });

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.$cacheSeconds,
        ]);
    }

    public function stylesheet(): Response
    {
        $cacheSeconds = (int) config('seo.sitemap_cache_seconds', 3600);
        $xsl = Cache::remember('seo.sitemap.xsl', $cacheSeconds, fn (): string => view('seo.sitemap-xsl')->render());

        return response($xsl, 200, [
            'Content-Type' => 'text/xsl; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.$cacheSeconds,
        ]);
    }

    public function llmsTxt(): Response
    {
        $cacheSeconds = (int) config('seo.robots_cache_seconds', 86400);

        return response(LlmsTxt::render(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.$cacheSeconds,
        ]);
    }

    public function htmlSitemap(): View
    {
        $categoryTree = Category::query()
            ->where('active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['activeChildren' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->get();

        return view('shop.seo.html-sitemap', [
            'categoryTree' => $categoryTree,
            'brands' => Brand::query()->where('active', true)->orderBy('sort_order')->orderBy('name')->get(['slug', 'name']),
            'pages' => Page::query()->where('published', true)->orderBy('sort_order')->get(['slug', 'title']),
            'blogPosts' => BlogPost::published()->limit(40)->get(['slug', 'title']),
            'recentProducts' => Product::query()->active()->latest('updated_at')->limit(80)->get(['slug', 'name']),
            'metaTitle' => __('shop.html_sitemap_title'),
            'metaDescription' => Seo::description([__('shop.html_sitemap_lead')]),
            'canonical' => route('sitemap.html'),
            'breadcrumbs' => [
                ['name' => __('shop.home'), 'url' => route('home')],
                ['name' => __('shop.html_sitemap_title')],
            ],
            'jsonLd' => [
                Seo::webSite(),
                Seo::webPage(
                    __('shop.html_sitemap_title'),
                    (string) __('shop.html_sitemap_lead'),
                    route('sitemap.html'),
                ),
                Seo::breadcrumbs([
                    ['name' => __('shop.home'), 'url' => route('home')],
                    ['name' => __('shop.html_sitemap_title')],
                ]),
            ],
        ]);
    }

    public function rss(): Response
    {
        $cacheSeconds = (int) config('seo.sitemap_cache_seconds', 3600);
        $xml = Cache::remember('seo.feed.xml', $cacheSeconds, function (): string {
            $posts = BlogPost::published()->latest('published_at')->limit(40)->get();
            $name = \App\Support\SiteName::get();

            $items = $posts->map(function (BlogPost $post) {
                $date = ($post->published_at ?? $post->updated_at)?->toRfc2822String();

                return [
                    'title' => $post->title,
                    'link' => route('blog.show', $post),
                    'description' => Seo::description([$post->excerpt, $post->content], 400),
                    'pubDate' => $date,
                ];
            });

            return view('seo.rss', [
                'title' => $name,
                'link' => Seo::siteUrl(),
                'description' => SiteSetting::get('site_description', config('kosar.description')),
                'items' => $items,
            ])->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.$cacheSeconds,
        ]);
    }

    public function merchantFeed(): StreamedResponse
    {
        $storeName = config('kosar.name', 'Koşar');
        $storeUrl = Seo::siteUrl();

        return response()->stream(function () use ($storeName, $storeUrl): void {
            $this->streamMerchantFeedXml($storeName, $storeUrl);
        }, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function streamMerchantFeedXml(string $storeName, string $storeUrl): void
    {
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
        echo '<channel>';
        echo '<title>'.$this->xmlText($storeName.' Ürün Kataloğu').'</title>';
        echo '<link>'.$this->xmlText($storeUrl).'</link>';
        echo '<description>'.$this->xmlText($storeName.' — Sanayi pompaları, vantilatörler ve teknik ekipmanlar.').'</description>';

        Product::query()
            ->active()
            ->where('stock', '>', 0)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->with(['brand:id,name', 'categories:id,name,slug,parent_id', 'images:id,product_id,path'])
            ->select([
                'id', 'sku', 'slug', 'name', 'short_description', 'description', 'barcode',
                'price', 'compare_at_price', 'stock', 'image', 'brand_id',
            ])
            ->orderBy('id')
            ->cursor()
            ->each(function (Product $product) use ($storeName, $storeUrl): void {
                try {
                    $this->echoMerchantFeedItem($product, $storeName, $storeUrl);
                } catch (Throwable) {
                    // Tek bozuk ürün tüm feed'i düşürmesin.
                }
            });

        echo '</channel>';
        echo '</rss>';
    }

    private function echoMerchantFeedItem(Product $product, string $storeName, string $storeUrl): void
    {
        $imageUrl = $product->imageUrl('product-pdp') ?? $product->imageUrl();
        if (! $imageUrl) {
            return;
        }

        if (! str_starts_with($imageUrl, 'http://') && ! str_starts_with($imageUrl, 'https://')) {
            $imageUrl = Seo::absolute($imageUrl);
        }

        $brand = $product->brand?->name ?? $storeName;
        $category = $product->categories->pluck('name')->filter()->implode(' > ');
        $desc = Seo::description([
            $product->short_description,
            $product->description,
            $product->name,
        ], 4990);
        $title = $this->truncateText((string) $product->name, 150);
        $gtin = Seo::normalizeGtin($product->barcode);
        $hasDiscount = $product->hasDiscount();
        $price = $hasDiscount
            ? number_format((float) $product->compare_at_price, 2, '.', '').' TRY'
            : number_format((float) $product->price, 2, '.', '').' TRY';
        $salePrice = $hasDiscount
            ? number_format((float) $product->price, 2, '.', '').' TRY'
            : null;
        $productUrl = route('products.show', $product->slug, absolute: true);

        echo '<item>';
        echo '<g:id>'.$this->xmlText($product->sku ?: 'KOS-'.$product->id).'</g:id>';
        echo '<title>'.$this->xmlCdata($title).'</title>';
        echo '<description>'.$this->xmlCdata($desc).'</description>';
        echo '<link>'.$this->xmlText($productUrl).'</link>';
        echo '<g:image_link>'.$this->xmlText($imageUrl).'</g:image_link>';

        foreach ($this->merchantAdditionalImages($product, $imageUrl) as $additionalUrl) {
            echo '<g:additional_image_link>'.$this->xmlText($additionalUrl).'</g:additional_image_link>';
        }

        echo '<g:availability>'.($product->inStock() ? 'in stock' : 'out of stock').'</g:availability>';
        echo '<g:price>'.$this->xmlText($price).'</g:price>';
        if ($salePrice) {
            echo '<g:sale_price>'.$this->xmlText($salePrice).'</g:sale_price>';
        }
        echo '<g:brand>'.$this->xmlCdata($brand).'</g:brand>';
        echo '<g:condition>new</g:condition>';
        echo '<g:identifier_exists>'.($gtin ? 'yes' : 'no').'</g:identifier_exists>';
        if ($gtin) {
            echo '<g:gtin>'.$this->xmlText($gtin['value']).'</g:gtin>';
        }
        if ($product->sku) {
            echo '<g:mpn>'.$this->xmlText($product->sku).'</g:mpn>';
        }
        if ($category !== '') {
            echo '<g:product_type>'.$this->xmlCdata($category).'</g:product_type>';
        }
        echo '<g:google_product_category>'.$this->xmlText((string) GoogleProductCategory::forProduct($product)).'</g:google_product_category>';
        echo '<g:shipping>';
        echo '<g:country>TR</g:country>';
        echo '<g:service>Standart Kargo</g:service>';
        echo '<g:price>0 TRY</g:price>';
        echo '</g:shipping>';
        echo '</item>';
    }

    /** @return list<string> */
    private function merchantAdditionalImages(Product $product, string $primaryUrl): array
    {
        $urls = [];

        foreach ($product->images as $image) {
            $url = $image->url('product-pdp') ?? $image->url();
            if (! $url) {
                continue;
            }

            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                $url = Seo::absolute($url);
            }

            if ($url === $primaryUrl || in_array($url, $urls, true)) {
                continue;
            }

            $urls[] = $url;

            if (count($urls) >= 10) {
                break;
            }
        }

        return $urls;
    }

    private function xmlText(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlCdata(string $value): string
    {
        return '<![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', $value).']]>';
    }

    private function truncateText(string $value, int $limit): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit);
        }

        return substr($value, 0, $limit);
    }

    public function bingSiteAuth(): Response
    {
        $xml = trim((string) SiteSetting::get('bing_site_auth_xml', ''));

        abort_if($xml === '', 404);

        return response($xml."\n", 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function verificationFile(string $file): Response
    {
        if (str_ends_with($file, '.txt')) {
            return $this->indexNowKeyFile($file);
        }

        $storedFile = SiteSetting::get('google_verification_file_name');
        $content = SiteSetting::get('google_verification_file_content');

        abort_unless($storedFile && $content && hash_equals($storedFile, $file), 404);

        return response(trim($content)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function indexNowKeyFile(string $file): Response
    {
        $key = trim((string) SiteSetting::get('indexnow_key', ''));

        abort_unless($key !== '' && hash_equals($key.'.txt', $file), 404);

        return response($key."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
