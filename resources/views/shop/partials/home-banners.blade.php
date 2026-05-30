@if($homeSliders->isNotEmpty())
    @php $bannerSpec = \App\Support\HomeBannerSpec::all(); @endphp
    <section class="shop-banner-slider shop-reveal mb-6 lg:mb-8"
             aria-roledescription="carousel"
             aria-label="{{ __('shop.home_banners') }}"
             data-autoplay-ms="6000">
        <div class="shop-banner-slider__viewport rounded-2xl lg:rounded-3xl overflow-hidden border border-brand-200/80 shadow-[0_12px_40px_rgb(30_58_95/0.12)]"
             style="--shop-banner-ratio: {{ $bannerSpec['aspect_ratio'] }};">
            <div class="shop-banner-slider__track" id="shop-banner-track">
                @foreach($homeSliders as $index => $banner)
                    @php $href = $banner->targetUrl(); @endphp
                    <div class="shop-banner-slide {{ $index === 0 ? 'is-active' : '' }}"
                         role="group"
                         aria-roledescription="slide"
                         aria-label="{{ $index + 1 }} / {{ $homeSliders->count() }}"
                         data-slide-index="{{ $index }}"
                         @if($index !== 0) hidden @endif>
                        @if($href)
                            <a href="{{ $href }}" class="shop-banner-slide__frame shop-banner-slide__frame--link" aria-label="{{ $banner->displayAlt() }}">
                                @include('shop.partials.home-banner-slide-inner', ['banner' => $banner, 'index' => $index])
                            </a>
                        @else
                            <div class="shop-banner-slide__frame">
                                @include('shop.partials.home-banner-slide-inner', ['banner' => $banner, 'index' => $index])
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if($homeSliders->count() > 1)
                <button type="button" class="shop-banner-slider__nav shop-banner-slider__nav--prev" data-banner-prev aria-label="{{ __('shop.banner_prev') }}">
                    <x-shop.icon name="chevron-right" class="w-5 h-5 rotate-180" />
                </button>
                <button type="button" class="shop-banner-slider__nav shop-banner-slider__nav--next" data-banner-next aria-label="{{ __('shop.banner_next') }}">
                    <x-shop.icon name="chevron-right" class="w-5 h-5" />
                </button>
                <div class="shop-banner-slider__dots" role="tablist" aria-label="{{ __('shop.home_banners') }}">
                    @foreach($homeSliders as $index => $banner)
                        <button type="button"
                                class="shop-banner-slider__dot {{ $index === 0 ? 'is-active' : '' }}"
                                data-banner-dot="{{ $index }}"
                                role="tab"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                aria-label="{{ __('shop.banner_goto', ['num' => $index + 1]) }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
