@props([
    'variant' => 'header',
])

@php
    $logoUrl = \App\Support\SiteLogo::url();
    $siteName = \App\Support\SiteLogo::alt();
    $variantClass = match ($variant) {
        'footer' => 'shop-logo shop-logo--footer',
        'mobile' => 'shop-logo shop-logo--mobile',
        'panel' => 'shop-logo shop-logo--panel',
        default => 'shop-logo shop-logo--header',
    };
@endphp

@if($logoUrl)
    <img
        src="{{ $logoUrl }}"
        alt="{{ $siteName }}"
        {{ $attributes->class([$variantClass]) }}
        width="200"
        height="48"
        decoding="async"
        loading="eager"
    >
@else
    <span {{ $attributes->class([$variantClass, 'shop-logo-fallback']) }} aria-hidden="true">K</span>
@endif
