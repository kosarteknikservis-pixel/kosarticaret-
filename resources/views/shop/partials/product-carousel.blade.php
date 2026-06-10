@props([
    'products',
    'title' => null,
    'headingId' => null,
    'priority' => false,
])

@if($products->isNotEmpty())
    <div {{ $attributes->class(['shop-home-product-carousel shop-related-carousel']) }} data-product-carousel>
        @if($title)
            <div class="shop-home-product-list__top shop-related-carousel__top">
                <h2 @if($headingId) id="{{ $headingId }}" @endif class="shop-related-section__title">{{ $title }}</h2>
                <div class="shop-home-product-carousel__controls" aria-hidden="false">
                    <button type="button"
                            class="shop-carousel-nav shop-carousel-nav--prev"
                            data-product-carousel-prev
                            aria-label="{{ __('shop.product_carousel_prev') }}">
                        <x-shop.icon name="chevron-right" class="w-4 h-4 rotate-180" />
                    </button>
                    <button type="button"
                            class="shop-carousel-nav shop-carousel-nav--next"
                            data-product-carousel-next
                            aria-label="{{ __('shop.product_carousel_next') }}">
                        <x-shop.icon name="chevron-right" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        @endif

        <div class="shop-home-product-carousel__viewport" data-product-carousel-viewport>
            <div class="shop-home-product-carousel__track shop-reveal-group" data-product-carousel-track role="list">
                @foreach($products as $product)
                    <div class="shop-home-product-carousel__item" role="listitem">
                        @include('shop.partials.product-card', [
                            'product' => $product,
                            'priority' => $priority && $loop->index < 2,
                        ])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
