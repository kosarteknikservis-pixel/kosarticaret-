<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\GoogleProductCategory;
use App\Support\Seo;
use App\Support\SitemapGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $cacheSeconds = (int) config('seo.sitemap_cache_seconds', 3600);

        $xml = Cache::remember('seo.sitemap.xml', $cacheSeconds, function (): string {
            if (SitemapGenerator::usesIndex()) {
                return view('seo.sitemap-index', [
                    'entries' => SitemapGenerator::indexEntries(),
                ])->render();
            }

            return view('seo.sitemap', ['urls' => SitemapGenerator::allUrls()])->render();
        });

        return response($xml, 200, $this->xmlHeaders($cacheSeconds));
    }

    public function sitemapChunk(string $chunk): Response
    {
        if (! SitemapGenerator::usesIndex()) {
            abort(404);
        }

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

    /**
     * @param  list<array{loc: string, lastmod?: string, priority?: string}>  $urls
     */
    private function urlsetResponse(array $urls): Response
    {
        $cacheSeconds = (int) config('seo.sitemap_cache_seconds', 3600);
        $xml = view('seo.sitemap', ['urls' => $urls])->render();

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
                'Disallow: /urun-kategori',
                'Disallow: /urun-etiket',
                'Disallow: /tag/',
                'Disallow: /page/',
                'Disallow: /magaza',
                'Disallow: /shop',
                'Disallow: /*?add-to-cart*',
                'Disallow: /*?*filter*',
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

    public function merchantFeed(): StreamedResponse
    {
        $storeName = config('kosar.name', 'Koşar');
        $storeUrl = Seo::siteUrl();

        return response()->stream(function () use ($storeName, $storeUrl): void {
            $this->streamMerchantFeedXml($storeName, $storeUrl);
        }, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
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
            ->with(['brand:id,name', 'categories:id,name,slug,parent_id'])
            ->select([
                'id', 'sku', 'slug', 'name', 'short_description',
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
        $desc = strip_tags((string) ($product->short_description ?: $product->name));
        $desc = trim((string) preg_replace('/\s+/', ' ', $desc));
        $desc = $this->truncateText($desc, 4990);
        $title = $this->truncateText((string) $product->name, 150);
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
        echo '<g:availability>'.($product->inStock() ? 'in stock' : 'out of stock').'</g:availability>';
        echo '<g:price>'.$this->xmlText($price).'</g:price>';
        if ($salePrice) {
            echo '<g:sale_price>'.$this->xmlText($salePrice).'</g:sale_price>';
        }
        echo '<g:brand>'.$this->xmlCdata($brand).'</g:brand>';
        echo '<g:condition>new</g:condition>';
        echo '<g:identifier_exists>no</g:identifier_exists>';
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
