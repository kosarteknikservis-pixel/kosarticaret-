@props([
    'icon' => 'grid',
    'title',
    'description' => null,
])

<div {{ $attributes->class(['shop-empty-state']) }}>
    <span class="shop-empty-state__icon" aria-hidden="true">
        <x-shop.icon :name="$icon" class="w-10 h-10" />
    </span>
    <p class="shop-empty-state__title">{{ $title }}</p>
    @if($description)
        <p class="shop-empty-state__desc">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="shop-empty-state__action">{{ $action }}</div>
    @endisset
</div>
