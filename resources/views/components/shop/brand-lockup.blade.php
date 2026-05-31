@props([
    'variant' => 'header',
])

@php
    $hasLogo = \App\Support\SiteLogo::has();
    $siteName = \App\Support\SiteLogo::alt();
    $tagline = app(\App\Services\StoreConfig::class)->vitrin('tagline', __('shop.tagline'));
@endphp

<a
    href="{{ route('home') }}"
    {{ $attributes->class([
        'shop-brand',
        'shop-brand--'.$variant,
        'group',
        $hasLogo ? 'shop-brand--has-logo' : '',
    ]) }}
    aria-label="{{ $siteName }} ana sayfa"
>
    <span class="shop-logo-wrap">
        <x-shop.site-logo :variant="$variant" />
    </span>

    @unless($hasLogo)
        <span class="shop-brand-text">
            <span class="shop-brand-name">{{ \App\Support\SiteName::get() }}</span>
            @if($variant === 'header')
                <span class="shop-brand-tagline">{{ $tagline }}</span>
            @endif
        </span>
    @endunless
</a>
