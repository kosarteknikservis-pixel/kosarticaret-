@if($homePromoTiles->isNotEmpty())
    <section class="shop-home-promos shop-reveal mb-8 lg:mb-10" aria-label="{{ __('shop.home_promo_tiles') }}">
        <div class="shop-home-promos__grid">
            @foreach($homePromoTiles as $tile)
                @php
                    $href = $tile->targetUrl();
                    $wide = $tile->isBanner();
                @endphp
                @if($href)
                    <a href="{{ $href }}"
                       class="shop-home-promo shop-home-promo--{{ $tile->type }} {{ $wide ? 'shop-home-promo--wide' : '' }} group">
                        @include('shop.partials.home-promo-tile-inner', ['tile' => $tile])
                    </a>
                @else
                    <div class="shop-home-promo shop-home-promo--{{ $tile->type }} {{ $wide ? 'shop-home-promo--wide' : '' }}">
                        @include('shop.partials.home-promo-tile-inner', ['tile' => $tile])
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endif
