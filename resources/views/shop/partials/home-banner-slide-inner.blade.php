<img src="{{ $banner->imageUrl('banner') }}"
     alt="{{ $banner->displayAlt() }}"
     width="{{ \App\Support\HomeBannerSpec::width() }}"
     height="{{ \App\Support\HomeBannerSpec::height() }}"
     @if($srcset = $banner->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 100vw, 80rem" @endif
     @if($index === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif
     decoding="async"
     class="shop-banner-slide__img">
@if($banner->hasOverlay())
    <div class="shop-banner-slide__overlay">
        <div class="shop-banner-slide__content shop-container">
            @if($banner->displayTitle())
                <h2 class="shop-banner-slide__title">{{ $banner->displayTitle() }}</h2>
            @endif
            @if($banner->subtitle)
                <p class="shop-banner-slide__subtitle">{{ $banner->subtitle }}</p>
            @endif
            @if($banner->cta_text && $banner->targetUrl())
                <span class="shop-banner-slide__cta btn-primary shop-btn-premium">{{ $banner->cta_text }}</span>
            @endif
        </div>
    </div>
@endif
