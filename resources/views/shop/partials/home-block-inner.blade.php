<div class="shop-home-block__media">
    <img
        src="{{ $block->imageUrl('banner') }}"
        @if($srcset = $block->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 100vw, 50vw" @endif
        alt="{{ $block->displayAlt() }}"
        loading="lazy"
        decoding="async"
        class="shop-home-block__img">
    <span class="shop-home-block__shade" aria-hidden="true"></span>
</div>
@if($block->hasOverlay())
    <div class="shop-home-block__body">
        @if($block->displayTitle())
            <p class="shop-home-block__title">{{ $block->displayTitle() }}</p>
        @endif
        @if($block->subtitle)
            <p class="shop-home-block__sub">{{ $block->subtitle }}</p>
        @endif
        @if($block->isProduct() && $block->product)
            <p class="shop-home-block__price">{{ number_format($block->product->price, 2, ',', '.') }} ₺</p>
        @endif
        @if($block->cta_text && $block->targetUrl())
            <span class="shop-home-block__cta">{{ $block->cta_text }}</span>
        @endif
    </div>
@endif
