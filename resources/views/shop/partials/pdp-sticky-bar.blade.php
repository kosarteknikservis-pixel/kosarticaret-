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
                    data-ga-item-id="{{ $product->sku ?: 'KOS-'.$product->id }}"
                    data-ga-item-name="{{ $product->name }}"
                    data-ga-price="{{ number_format((float) $product->price, 2, '.', '') }}"
                    data-ga-brand="{{ $product->brand?->name }}"
                    class="shop-pdp-sticky__cart btn-primary">
                {{ __('shop.add_to_cart') }}
            </button>
        </div>
    </div>
</div>
@endif
