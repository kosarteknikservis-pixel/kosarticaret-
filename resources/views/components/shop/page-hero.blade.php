@props([
    'title',
    'subtitle' => null,
])

<header {{ $attributes->class(['shop-page-hero', 'shop-reveal']) }}>
    <div class="shop-page-hero__inner">
        <div class="shop-page-hero__content">
            @isset($eyebrow)
                <div class="shop-page-hero__eyebrow">{{ $eyebrow }}</div>
            @endisset
            <h1 class="shop-page-hero__title">{{ $title }}</h1>
            @if($subtitle)
                <p class="shop-page-hero__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="shop-page-hero__actions">{{ $actions }}</div>
        @endisset
    </div>
</header>
