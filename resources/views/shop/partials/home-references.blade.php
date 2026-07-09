@if($references->isNotEmpty())
<section class="shop-section shop-home-references" aria-labelledby="home-references-title">
    <div class="shop-container">
        <div class="shop-section__head">
            <h2 id="home-references-title" class="shop-section__title">{{ __('shop.home_references_title') }}</h2>
            @if(__('shop.home_references_sub'))
                <p class="shop-section__sub">{{ __('shop.home_references_sub') }}</p>
            @endif
        </div>

        <div class="shop-home-references__grid">
            @foreach($references as $ref)
                <article class="shop-home-references__card shop-reveal">
                    @if($ref->imageUrl())
                        <div class="shop-home-references__media">
                            <img src="{{ $ref->imageUrl('category') }}" alt="{{ $ref->title }}" loading="lazy" decoding="async" width="480" height="280" class="shop-home-references__img">
                        </div>
                    @endif
                    <div class="shop-home-references__body">
                        @if($ref->sector)
                            <p class="shop-home-references__sector">{{ $ref->sector }}</p>
                        @endif
                        <h3 class="shop-home-references__title">{{ $ref->title }}</h3>
                        @if($ref->summary)
                            <p class="shop-home-references__summary">{{ $ref->summary }}</p>
                        @endif
                        <dl class="shop-home-references__meta">
                            @if($ref->client)
                                <div><dt>{{ __('shop.quote_company') }}</dt><dd>{{ $ref->client }}</dd></div>
                            @endif
                            @if($ref->location)
                                <div><dt>Konum</dt><dd>{{ $ref->location }}</dd></div>
                            @endif
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
