@props([
    'variant' => 'primary', // primary|secondary|danger
    'size' => 'md', // sm|md
    'block' => false,
    'href' => null,
    'type' => 'submit',
])

@php
    $tag = $href ? 'a' : 'button';
    $classes = ['admin-btn'];
    $classes[] = match ($variant) {
        'secondary' => 'admin-btn-secondary',
        'danger' => 'admin-btn-danger',
        default => 'admin-btn-primary',
    };
    if ($size === 'sm') {
        $classes[] = 'admin-btn--sm';
    }
    if ($block) {
        $classes[] = 'admin-btn--block';
    }
@endphp

@if($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
