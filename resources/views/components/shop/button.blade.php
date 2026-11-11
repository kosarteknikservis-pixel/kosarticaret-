@props([
    'variant' => 'primary', // primary|outline
    'size' => 'md', // sm|md|lg
    'block' => false,
    'premium' => false,
    'href' => null,
    'type' => 'submit',
])

@php
    $tag = $href ? 'a' : 'button';
    $classes = [
        $variant === 'outline' ? 'btn-outline' : 'btn-primary',
    ];
    if ($size === 'sm') {
        $classes[] = 'btn--sm';
    } elseif ($size === 'lg') {
        $classes[] = 'btn--lg';
    }
    if ($block) {
        $classes[] = 'btn--block';
    }
    if ($premium) {
        $classes[] = 'shop-btn-premium';
    }
@endphp

@if($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
