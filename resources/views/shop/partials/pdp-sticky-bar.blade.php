@if($product->inStock())
<div class="shop-pdp-sticky" data-pdp-sticky aria-hidden="true">
    <div class="shop-pdp-sticky__inner">
        <div class="shop-pdp-sticky__info">
            <p class="shop-pdp-sticky__price">{{ number_format($product->price, 2, ',', '.') }} ₺</p>
            <p class="shop-pdp-sticky__name">{{ \Illuminate\Support\Str::limit($product->name, 48) }}</p>
        </div>
        <div class="shop-pdp-sticky__actions">
            <button type="button"
                    data-add-cart="{{ $product->slug }}"
                    data-qty-from="#pdp-qty"
                    class="shop-pdp-sticky__cart btn-primary">
                {{ __('shop.add_to_cart') }}
            </button>
            <button type="button"
                    data-compare-add="{{ $product->slug }}"
                    class="shop-pdp-sticky__compare"
                    title="{{ __('shop.compare_add') }}">
                {{ __('shop.compare_short') }}
            </button>
        </div>
    </div>
</div>
@endif
