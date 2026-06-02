@props([
    'icon',
    'href' => null,
    'emphasis' => false,
])

@php
    $tag = $href ? 'a' : 'button';
    $classes = 'shop-header-icon'.($emphasis ? ' shop-header-icon--emphasis' : '');
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    @if($tag === 'button' && ! $attributes->has('type')) type="button" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    <span class="shop-header-icon__halo" aria-hidden="true"></span>
    <x-shop.icon :name="$icon" class="shop-header-icon__svg" />
    {{ $slot }}
</{{ $tag }}>
