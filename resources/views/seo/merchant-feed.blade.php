<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>{{ $storeName }} Ürün Kataloğu</title>
        <link>{{ $storeUrl }}</link>
        <description>{{ $storeName }} — Sanayi pompaları, vantilatörler ve teknik ekipmanlar.</description>
@foreach($products as $product)
@php
    $imageUrl = $product->imageUrl('product-pdp') ?? $product->imageUrl();
    $brand    = $product->brand?->name ?? $storeName;
    $category = $product->categories->pluck('name')->implode(' > ');
    $desc     = strip_tags($product->short_description ?: $product->description ?: $product->name);
    $desc     = trim(preg_replace('/\s+/', ' ', $desc));
    $desc     = mb_substr($desc, 0, 4990);
    $title    = mb_substr($product->name, 0, 150);
    $hasDiscount = $product->hasDiscount();
    // Google: g:price = normal/liste fiyatı, g:sale_price = indirimli fiyat
    $gPrice     = $hasDiscount
        ? number_format((float)$product->compare_at_price, 2, '.', '').' TRY'
        : number_format((float)$product->price, 2, '.', '').' TRY';
    $gSalePrice = $hasDiscount
        ? number_format((float)$product->price, 2, '.', '').' TRY'
        : null;
@endphp
@if($imageUrl)
        <item>
            <g:id>{{ $product->sku ?: 'KOS-'.$product->id }}</g:id>
            <title><![CDATA[{{ $title }}]]></title>
            <description><![CDATA[{{ $desc }}]]></description>
            <link>{{ route('products.show', $product->slug) }}</link>
            <g:image_link>{{ $imageUrl }}</g:image_link>
            <g:availability>{{ $product->inStock() ? 'in stock' : 'out of stock' }}</g:availability>
            <g:price>{{ $gPrice }}</g:price>
@if($gSalePrice)
            <g:sale_price>{{ $gSalePrice }}</g:sale_price>
@endif
            <g:brand><![CDATA[{{ $brand }}]]></g:brand>
            <g:condition>new</g:condition>
            <g:identifier_exists>no</g:identifier_exists>
@if($product->sku)
            <g:mpn>{{ $product->sku }}</g:mpn>
@endif
@if($category)
            <g:product_type><![CDATA[{{ $category }}]]></g:product_type>
@endif
            <g:shipping>
                <g:country>TR</g:country>
                <g:service>Standart Kargo</g:service>
                <g:price>0 TRY</g:price>
            </g:shipping>
        </item>
@endif
@endforeach
    </channel>
</rss>
