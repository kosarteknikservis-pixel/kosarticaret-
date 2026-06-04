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
        src="{{ \App\Support\SiteLogo::url('site-logo') }}"
        @if($srcset = \App\Support\SiteLogo::srcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 8.5rem, 11.5rem" @endif
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
