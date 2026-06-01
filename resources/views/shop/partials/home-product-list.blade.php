@php
    $products = $block->listedProducts();
@endphp

<section class="shop-home-product-list shop-reveal" aria-label="{{ $block->displayTitle() ?: __('shop.banner_type_product_list') }}">
    @if($products->isNotEmpty())
        <div class="shop-home-product-carousel" data-product-carousel>
            <div class="shop-home-product-list__top">
                @if(filled($block->title) || filled($block->subtitle))
                    <div class="shop-home-product-list__head">
                        @if(filled($block->title))
                            <h2 class="shop-home-product-list__title">{{ $block->title }}</h2>
                        @endif
                        @if(filled($block->subtitle))
                            <p class="shop-home-product-list__subtitle">{{ $block->subtitle }}</p>
                        @endif
                    </div>
                @endif

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

            <div class="shop-home-product-carousel__viewport" data-product-carousel-viewport>
                <div class="shop-home-product-carousel__track" data-product-carousel-track role="list">
                    @foreach($products as $product)
                        <div class="shop-home-product-carousel__item" role="listitem">
                            @include('shop.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif(filled($block->title) || filled($block->subtitle))
        <div class="shop-home-product-list__top">
            <div class="shop-home-product-list__head">
                @if(filled($block->title))
                    <h2 class="shop-home-product-list__title">{{ $block->title }}</h2>
                @endif
                @if(filled($block->subtitle))
                    <p class="shop-home-product-list__subtitle">{{ $block->subtitle }}</p>
                @endif
            </div>
        </div>
    @endif
</section>
