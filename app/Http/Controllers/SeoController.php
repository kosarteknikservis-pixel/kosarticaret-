<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\Seo;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
            ->with(['brand:id,name', 'categories:id,name'])
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
        $storedFile = SiteSetting::get('google_verification_file_name');
        $content = SiteSetting::get('google_verification_file_content');

        abort_unless($storedFile && $content && hash_equals($storedFile, $file), 404);

        return response(trim($content)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
