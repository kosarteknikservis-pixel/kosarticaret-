<div class="shop-home-promo__media">
    <img src="{{ $tile->imageUrl() }}" alt="{{ $tile->displayAlt() }}" loading="lazy" decoding="async" class="shop-home-promo__img">
    <span class="shop-home-promo__shade" aria-hidden="true"></span>
</div>
<div class="shop-home-promo__body">
    @if($tile->isProduct())
        <span class="shop-home-promo__eyebrow">{{ __('shop.banner_type_product') }}</span>
    @elseif($tile->isCategory())
        <span class="shop-home-promo__eyebrow">{{ __('shop.banner_type_category') }}</span>
    @elseif($tile->isBanner())
        <span class="shop-home-promo__eyebrow">{{ __('shop.banner_type_banner') }}</span>
    @endif
    @if($tile->displayTitle())
        <p class="shop-home-promo__title">{{ $tile->displayTitle() }}</p>
    @endif
    @if($tile->subtitle)
        <p class="shop-home-promo__sub">{{ $tile->subtitle }}</p>
    @endif
    @if($tile->isProduct() && $tile->product)
        <p class="shop-home-promo__price">{{ number_format($tile->product->price, 2, ',', '.') }} ₺</p>
    @endif
    @if($tile->cta_text && $tile->targetUrl())
        <span class="shop-home-promo__cta">{{ $tile->cta_text }}</span>
    @endif
</div>
