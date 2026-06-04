<article class="shop-product-card relative group flex flex-col h-full">
    @if($product->hasDiscount())
        <span class="shop-product-card__badge">-%{{ $product->discountPercent() }}</span>
    @endif
    <button type="button" data-toggle-favorite="{{ $product->slug }}" aria-pressed="false" aria-label="{{ __('shop.add_favorite') }}"
            class="shop-product-card__fav">
        <x-shop.icon name="heart" class="w-4 h-4" />
    </button>
    <a href="{{ route('products.show', $product) }}" class="shop-product-card__link">
        <div class="shop-product-card__media aspect-square">
            @if($product->imageUrl())
                <img
                    src="{{ $product->imageUrl('product-card') }}"
                    @if($srcset = $product->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 639px) 76vw, (max-width: 1023px) 13rem, 15rem" @endif
                    alt="{{ $product->imageAltText() }}"
                    loading="lazy"
                    decoding="async"
                    width="400"
                    height="400"
                    class="shop-product-card__img">
            @else
                <x-shop.icon name="grid" class="w-12 h-12 text-slate-300" />
            @endif
        </div>
        @if($product->brand)
            <p class="shop-product-card__brand">{{ $product->brand->name }}</p>
        @endif
        <h2 class="shop-product-card__title line-clamp-3 mt-1.5">{{ $product->name }}</h2>
        @if($product->review_count > 0)
            <div class="mt-2">
                @include('shop.partials.product-rating', ['rating' => $product->rating, 'count' => $product->review_count])
            </div>
        @endif
        <div class="shop-product-card__price-row">
            <p class="shop-product-card__price">{{ number_format($product->price, 2, ',', '.') }} ₺</p>
            @if($product->hasDiscount())
                <p class="shop-product-card__compare">{{ number_format($product->compare_at_price, 2, ',', '.') }} ₺</p>
            @endif
        </div>
        @if(!$product->inStock())
            <p class="shop-product-card__stock shop-product-card__stock--out">{{ __('shop.out_of_stock') }}</p>
        @elseif(\App\Support\ShopStockDisplay::showQuantity())
            <p class="shop-product-card__stock shop-product-card__stock--in">{{ \App\Support\ShopStockDisplay::storefrontLabel($product) }}</p>
        @endif
    </a>
    @if($product->inStock())
        <div class="shop-product-card__footer">
            <button type="button" data-add-cart="{{ $product->slug }}" class="shop-add-cart-btn shop-cart-btn">
                <span class="shop-add-cart-btn__icon" aria-hidden="true">
                    <x-shop.icon name="cart" class="w-4 h-4" />
                </span>
                <span class="shop-add-cart-btn__text">{{ __('shop.add_to_cart') }}</span>
            </button>
        </div>
    @endif
</article>
