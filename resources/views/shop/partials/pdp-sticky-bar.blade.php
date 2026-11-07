@php
    $stickyOption = $selectedPurchaseOption ?? null;
    $stickyPrice = (float) data_get($stickyOption, 'price', $product->price);
    $stickySlug = (string) data_get($stickyOption, 'slug', $product->slug);
    $stickyId = (int) data_get($stickyOption, 'product_id', $product->id);
    $stickyInStock = data_get($stickyOption, 'in_stock');
    if ($stickyInStock === null) {
        $stickyInStock = $product->inStock();
    }
@endphp
@if($stickyInStock)
<div class="shop-pdp-sticky" data-pdp-sticky aria-hidden="true">
    <div class="shop-pdp-sticky__inner">
        <div class="shop-pdp-sticky__info">
            <p class="shop-pdp-sticky__price" data-pdp-sticky-price>{{ number_format($stickyPrice, 2, ',', '.') }} ₺</p>
            <p class="shop-pdp-sticky__name">{{ \Illuminate\Support\Str::limit($product->name, 48) }}</p>
        </div>
        <div class="shop-pdp-sticky__actions">
            <button type="button"
                    data-add-cart="{{ $stickySlug }}"
                    data-qty-from="#pdp-qty"
                    data-ga-item-id="{{ (string) $stickyId }}"
                    data-ga-item-name="{{ $product->name }}"
                    data-ga-price="{{ number_format($stickyPrice, 2, '.', '') }}"
                    data-ga-brand="{{ $product->brand?->name }}"
                    class="shop-pdp-sticky__cart btn-primary">
                {{ __('shop.add_to_cart') }}
            </button>
        </div>
    </div>
</div>
@endif
