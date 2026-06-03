@php
    $href = $block->targetUrl();
    $span = (int) ($mediaSpan ?? $block->columnSpan());
    $bannerSpec = \App\Support\HomeBannerSpec::all();
@endphp
@if($block->isSlider() && $span >= 12)
    @php
        $sliderBlocks = collect([$block]);
    @endphp
    @include('shop.partials.home-banners', ['homeSliders' => $sliderBlocks])
@else
    <div class="shop-home-block shop-home-block--{{ $block->type }} shop-home-block--span-{{ $span }}"
         style="--shop-banner-ratio: {{ $bannerSpec['aspect_ratio'] }};">
        @if($href)
            <a href="{{ $href }}" class="shop-home-block__link group">
                @include('shop.partials.home-block-inner', ['block' => $block])
            </a>
        @else
            <div class="shop-home-block__link">
                @include('shop.partials.home-block-inner', ['block' => $block])
            </div>
        @endif
    </div>
@endif
