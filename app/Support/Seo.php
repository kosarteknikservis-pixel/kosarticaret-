<?php



namespace App\Support;



use App\Models\BlogPost;

use App\Models\Brand;

use App\Models\Category;

use App\Models\Product;

use App\Models\SiteSetting;

use Illuminate\Contracts\Pagination\Paginator;

use Illuminate\Support\Str;



class Seo

{

    public static function siteUrl(): string

    {

        return rtrim(config('kosar.url', config('app.url')), '/');

    }



    public static function absolute(string $path = '/'): string

    {

        if (str_starts_with($path, 'http')) {

            return $path;

        }



        return self::siteUrl().'/'.ltrim($path, '/');

    }



    public static function pageTitle(string $title): string

    {

        $site = SiteName::get();

        $title = self::stripKnownSiteSuffixes(SiteName::normalize(trim($title)));



        if ($title === '' || strcasecmp($title, $site) === 0) {

            return $site;

        }



        return "{$title} | {$site}";

    }



    /**

     * WooCommerce/Yoast importlarında meta_title sonuna eklenmiş site adlarını temizler.

     */

    private static function stripKnownSiteSuffixes(string $title): string

    {

        if ($title === '') {

            return '';

        }



        $candidates = self::siteNameSuffixCandidates();

        $changed = true;



        while ($changed) {

            $changed = false;



            foreach ($candidates as $candidate) {

                if ($candidate === '') {

                    continue;

                }



                $pattern = '/\s*[\|\-–—]\s*'.preg_quote($candidate, '/').'\s*$/iu';

                $stripped = preg_replace($pattern, '', $title);



                if (is_string($stripped) && $stripped !== $title) {

                    $title = trim($stripped);

                    $changed = true;

                }

            }

        }



        return trim($title);

    }



    /** @return list<string> */

    private static function siteNameSuffixCandidates(): array

    {

        $raw = [

            SiteName::get(),

            (string) config('kosar.name'),

            (string) config('kosar.legal_name'),

            SiteSetting::get('site_name'),

            SiteSetting::get('legal_name'),

            'Koşar Ticaret',

            'Kosar Ticaret',

            'Koşar',

            'Kosar',

        ];



        $normalized = [];



        foreach ($raw as $name) {

            $name = SiteName::normalize(trim((string) $name));

            if ($name !== '') {

                $normalized[] = $name;

            }

        }



        usort($normalized, fn (string $a, string $b): int => strlen($b) <=> strlen($a));



        return array_values(array_unique($normalized));

    }



    /**

     * @param  array<int, string|null>  $candidates

     */

    public static function description(array $candidates, int $limit = 160): string

    {

        foreach ($candidates as $text) {

            $clean = RichContent::plainText($text);

            if ($clean !== '') {

                return Str::limit($clean, $limit);

            }

        }



        return Str::limit(RichContent::plainText((string) config('kosar.description')), $limit);

    }



    /**

     * @param  array<int, string|null>  $candidates

     */

    public static function keywords(array $candidates, int $limit = 10): ?string

    {

        $parts = [];

        foreach ($candidates as $item) {

            if (is_array($item)) {

                $parts = array_merge($parts, $item);

            } elseif (is_string($item) && $item !== '') {

                $parts = array_merge($parts, array_map('trim', explode(',', $item)));

            }

        }



        $parts = array_values(array_unique(array_filter($parts)));

        if ($parts === []) {

            return null;

        }



        return implode(', ', array_slice($parts, 0, $limit));

    }



    /** @return array<string, mixed> */

    public static function organization(): array

    {

        $phone = SiteSetting::get('contact_phone', config('kosar.contact.phone'));

        $email = SiteSetting::get('contact_email', config('kosar.contact.email'));



        return array_filter([

            '@context' => 'https://schema.org',

            '@type' => 'Organization',

            '@id' => self::siteUrl().'/#organization',

            'name' => SiteSetting::get('legal_name', config('kosar.legal_name')),

            'alternateName' => SiteName::get(),

            'url' => self::siteUrl(),

            'logo' => SiteLogo::url() ?? self::absolute('/storage/logo.png'),

            'description' => SiteSetting::get('site_description', config('kosar.description')),

            'contactPoint' => [

                '@type' => 'ContactPoint',

                'telephone' => $phone,

                'email' => $email,

                'contactType' => 'customer service',

                'areaServed' => 'TR',

                'availableLanguage' => ['Turkish'],

            ],

        ]);

    }



    /** @return array<string, mixed> */

    public static function webSite(): array

    {

        return [

            '@context' => 'https://schema.org',

            '@type' => 'WebSite',

            '@id' => self::siteUrl().'/#website',

            'name' => SiteName::get(),

            'url' => self::siteUrl(),

            'publisher' => ['@id' => self::siteUrl().'/#organization'],

            'inLanguage' => 'tr-TR',

            'potentialAction' => [

                '@type' => 'SearchAction',

                'target' => [

                    '@type' => 'EntryPoint',

                    'urlTemplate' => self::absolute('/ara?q={search_term_string}'),

                ],

                'query-input' => 'required name=search_term_string',

            ],

        ];

    }



    /** @return array<string, mixed> */

    public static function onlineStore(): array

    {

        return [

            '@context' => 'https://schema.org',

            '@type' => 'OnlineStore',

            '@id' => self::siteUrl().'/#store',

            'name' => SiteName::get(),

            'url' => self::siteUrl(),

            'description' => SiteSetting::get('site_description', config('kosar.description')),

            'priceRange' => '₺₺',

            'currenciesAccepted' => 'TRY',

            'paymentAccepted' => 'Credit Card, Bank Transfer, Cash',

        ];

    }



    /**

     * @param  list<array{name: string, url?: string}>  $items

     * @return array<string, mixed>

     */

    public static function breadcrumbs(array $items): array

    {

        return [

            '@context' => 'https://schema.org',

            '@type' => 'BreadcrumbList',

            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => array_filter([

                '@type' => 'ListItem',

                'position' => $i + 1,

                'name' => $item['name'],

                'item' => isset($item['url']) ? $item['url'] : null,

            ]))->all(),

        ];

    }



    /** @return list<string> */

    public static function productImages(Product $product): array

    {

        $images = [];

        if ($url = $product->imageUrl()) {

            $images[] = $url;

        }

        foreach ($product->images as $img) {

            if ($url = $img->url()) {

                $images[] = $url;

            }

        }



        return array_values(array_unique($images));

    }



    /** @return array<string, mixed> */

    public static function product(Product $product): array

    {

        $url = route('products.show', $product);

        $description = self::description([

            $product->meta_description,

            $product->short_description,

            $product->description,

            $product->name,

        ], 5000);



        $schema = [

            '@context' => 'https://schema.org',

            '@type' => 'Product',

            '@id' => $url.'#product',

            'name' => $product->name,

            'description' => $description,

            'sku' => $product->sku ?: (string) $product->id,

            'mpn' => $product->sku ?: (string) $product->id,

            'url' => $url,

            'itemCondition' => 'https://schema.org/NewCondition',

            'offers' => [

                '@type' => 'Offer',

                'url' => $url,

                'priceCurrency' => 'TRY',

                'price' => number_format((float) $product->price, 2, '.', ''),

                'availability' => $product->inStock()

                    ? 'https://schema.org/InStock'

                    : 'https://schema.org/OutOfStock',

                'itemCondition' => 'https://schema.org/NewCondition',

                'priceValidUntil' => now()->addMonths(6)->format('Y-m-d'),

                'seller' => [

                    '@type' => 'Organization',

                    'name' => SiteSetting::get('legal_name', config('kosar.legal_name')),

                ],

            ],

        ];



        if ($product->brand) {

            $schema['brand'] = [

                '@type' => 'Brand',

                'name' => $product->brand->name,

            ];

        }



        if ($category = $product->categories->first()) {

            $schema['category'] = $category->name;

        }



        $images = self::productImages($product);

        if ($images !== []) {

            $schema['image'] = count($images) === 1 ? $images[0] : $images;

        }



        if ($keywords = self::keywords([$product->tags, $product->name, $product->brand?->name])) {

            $schema['keywords'] = $keywords;

        }



        if ($product->review_count > 0) {

            $schema['aggregateRating'] = [

                '@type' => 'AggregateRating',

                'ratingValue' => number_format((float) $product->rating, 1, '.', ''),

                'reviewCount' => (int) $product->review_count,

                'bestRating' => '5',

                'worstRating' => '1',

            ];

        }



        return $schema;

    }



    /** @return list<array<string, mixed>> */

    public static function productReviews(Product $product): array

    {

        if (! $product->relationLoaded('approvedReviews')) {

            $product->load('approvedReviews');

        }



        return $product->approvedReviews

            ->take(10)

            ->map(fn ($review) => array_filter([

                '@context' => 'https://schema.org',

                '@type' => 'Review',

                'itemReviewed' => [

                    '@type' => 'Product',

                    'name' => $product->name,

                    'url' => route('products.show', $product),

                ],

                'reviewRating' => [

                    '@type' => 'Rating',

                    'ratingValue' => (int) $review->rating,

                    'bestRating' => 5,

                    'worstRating' => 1,

                ],

                'author' => [

                    '@type' => 'Person',

                    'name' => $review->author_name ?: 'Müşteri',

                ],

                'reviewBody' => $review->body,

                'name' => $review->title,

            ]))

            ->all();

    }



    /** @return array<string, mixed> */

    public static function brand(Brand $brand): array

    {

        return array_filter([

            '@context' => 'https://schema.org',

            '@type' => 'Brand',

            '@id' => route('brands.show', $brand).'#brand',

            'name' => $brand->name,

            'description' => self::description([$brand->meta_description, $brand->description, $brand->name], 500),

            'url' => route('brands.show', $brand),

            'logo' => $brand->logoUrl(),

        ]);

    }



    /** @return array<string, mixed> */

    public static function category(Category $category): array

    {

        return array_filter([

            '@context' => 'https://schema.org',

            '@type' => 'CollectionPage',

            '@id' => $category->storefrontUrl().'#category',

            'name' => $category->meta_title ?: $category->name,

            'description' => self::description([$category->meta_description, $category->description, $category->name], 500),

            'url' => $category->storefrontUrl(),

            'inLanguage' => 'tr-TR',

            'isPartOf' => ['@id' => self::siteUrl().'/#website'],

        ]);

    }



    /**

     * @param  Paginator<int, Product>|iterable<int, Product>  $products

     * @return array<string, mixed>

     */

    public static function itemListProducts(iterable $products, string $pageUrl, ?int $total = null): array

    {

        $elements = [];

        $position = 1;

        foreach ($products as $product) {

            $elements[] = [

                '@type' => 'ListItem',

                'position' => $position++,

                'name' => $product->name,

                'url' => route('products.show', $product),

            ];

        }



        if ($elements === []) {

            return [];

        }



        return [

            '@context' => 'https://schema.org',

            '@type' => 'ItemList',

            'url' => $pageUrl,

            'numberOfItems' => $total ?? count($elements),

            'itemListElement' => $elements,

        ];

    }



    /** @return array<string, mixed> */

    public static function article(BlogPost $post): array

    {

        return array_filter([

            '@context' => 'https://schema.org',

            '@type' => 'Article',

            'headline' => $post->meta_title ?: $post->title,

            'description' => self::description([$post->meta_description, $post->excerpt, $post->title]),

            'datePublished' => $post->published_at?->toIso8601String(),

            'dateModified' => $post->updated_at->toIso8601String(),

            'author' => [

                '@type' => 'Organization',

                'name' => SiteName::get(),

            ],

            'publisher' => [

                '@type' => 'Organization',

                'name' => SiteSetting::get('legal_name', config('kosar.legal_name')),

                'logo' => [

                    '@type' => 'ImageObject',

                    'url' => SiteLogo::url() ?? self::absolute('/storage/logo.png'),

                ],

            ],

            'mainEntityOfPage' => route('blog.show', $post),

            'inLanguage' => 'tr-TR',

            'image' => $post->imageUrl(),

        ]);

    }

    /** @return array<string, mixed> */
    public static function contactPage(): array
    {
        $phone = SiteSetting::get('contact_phone', config('kosar.contact.phone'));
        $email = SiteSetting::get('contact_email', config('kosar.contact.email'));
        $address = SiteSetting::get('contact_address', config('kosar.contact.address'));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            '@id' => route('contact.show').'#contact',
            'name' => SiteSetting::get('contact_meta_title', 'İletişim'),
            'description' => self::description([
                SiteSetting::get('contact_meta_description'),
                SiteSetting::get('contact_page_intro'),
            ]),
            'url' => route('contact.show'),
            'isPartOf' => ['@id' => self::siteUrl().'/#website'],
            'mainEntity' => [
                '@type' => 'Organization',
                '@id' => self::siteUrl().'/#organization',
                'telephone' => $phone,
                'email' => $email,
                'address' => $address ? [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $address,
                    'addressCountry' => 'TR',
                ] : null,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    public static function webPage(string $name, string $description, string $url): array

    {

        return [

            '@context' => 'https://schema.org',

            '@type' => 'WebPage',

            'name' => $name,

            'description' => $description,

            'url' => $url,

            'isPartOf' => ['@id' => self::siteUrl().'/#website'],

            'inLanguage' => 'tr-TR',

        ];

    }

}


