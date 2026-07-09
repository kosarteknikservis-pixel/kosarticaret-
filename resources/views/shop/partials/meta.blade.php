@php

    $pageTitle = $metaTitle ?? (trim($__env->yieldContent('title')) ?: \App\Support\SiteName::get());

    $pageDesc = $metaDescription ?? (trim($__env->yieldContent('meta_description')) ?: config('kosar.description'));

    $canonical = $canonical ?? url()->current();

    $ogImage = $ogImage ?? \App\Support\SiteLogo::url() ?? \App\Support\Seo::absolute('/storage/logo.png');

    if (! empty($ogImageMeta) && is_array($ogImageMeta)) {
        $ogImage = $ogImageMeta['url'] ?? $ogImage;
        $ogImageAlt = $ogImageMeta['alt'] ?? null;
        $ogImageWidth = $ogImageMeta['width'] ?? null;
        $ogImageHeight = $ogImageMeta['height'] ?? null;
    } else {
        $ogImageAlt = $ogImageAlt ?? null;
        $ogImageWidth = $ogImageWidth ?? null;
        $ogImageHeight = $ogImageHeight ?? null;
    }

    $ogImage = \App\Support\Seo::absoluteAssetUrl($ogImage) ?? $ogImage;

    $ogType = $ogType ?? 'website';

    $robots = $robots ?? 'index, follow';

    $metaKeywords = $metaKeywords ?? null;

    $schemas = $jsonLd ?? [\App\Support\Seo::organization(), \App\Support\Seo::webSite()];

    $fullTitle = \App\Support\Seo::pageTitle($pageTitle);

    $cleanDesc = \Illuminate\Support\Str::limit(\App\Support\RichContent::plainText($pageDesc), 160);

@endphp

<title>{{ $fullTitle }}</title>

<meta name="description" content="{{ $cleanDesc }}">

@if($metaKeywords)

    <meta name="keywords" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaKeywords), 255) }}">

@endif

<link rel="canonical" href="{{ $canonical }}">

@if(!empty($paginationPrev))
    <link rel="prev" href="{{ $paginationPrev }}">
@endif
@if(!empty($paginationNext))
    <link rel="next" href="{{ $paginationNext }}">
@endif

<meta name="robots" content="{{ $robots }}">

@if($verification = \App\Models\SiteSetting::get('google_site_verification'))
    <meta name="google-site-verification" content="{{ $verification }}">
@endif

@foreach(config('kosar.locales', ['tr']) as $loc)
    @if(count(config('kosar.locales', ['tr'])) > 1)
        <link rel="alternate" hreflang="{{ $loc === 'tr' ? 'tr-TR' : $loc }}" href="{{ request()->fullUrlWithQuery(['lang' => $loc]) }}">
    @endif
@endforeach
@if(count(config('kosar.locales', ['tr'])) > 1)
    <link rel="alternate" hreflang="x-default" href="{{ $canonical }}">
@endif

<meta property="og:type" content="{{ $ogType }}">

<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale() === 'tr' ? 'tr_TR' : app()->getLocale()) }}">

<meta property="og:site_name" content="{{ \App\Support\SiteName::get() }}">

<meta property="og:title" content="{{ $fullTitle }}">

<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(\App\Support\RichContent::plainText($pageDesc), 200) }}">

<meta property="og:url" content="{{ $canonical }}">

<meta property="og:image" content="{{ $ogImage }}">

@if($ogImageWidth && $ogImageHeight)
    <meta property="og:image:width" content="{{ $ogImageWidth }}">
    <meta property="og:image:height" content="{{ $ogImageHeight }}">
@endif

@if($ogImageAlt)
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
@endif

@if($ogType === 'product' && isset($productPrice))

    <meta property="product:price:amount" content="{{ $productPrice }}">

    <meta property="product:price:currency" content="TRY">

@endif

<meta name="twitter:card" content="summary_large_image">

<meta name="twitter:title" content="{{ $fullTitle }}">

<meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(\App\Support\RichContent::plainText($pageDesc), 200) }}">

<meta name="twitter:image" content="{{ $ogImage }}">

@if($ogImageAlt)
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
@endif

@foreach($schemas as $schema)

    @if(!empty($schema))

        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    @endif

@endforeach


