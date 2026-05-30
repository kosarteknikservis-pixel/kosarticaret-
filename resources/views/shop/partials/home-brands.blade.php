@if($brands->isNotEmpty())

    <section class="shop-section shop-brands-band shop-reveal" aria-labelledby="home-brands-heading">

        <h2 id="home-brands-heading" class="shop-brands-band__label">

            {{ \App\Models\SiteSetting::get('home_brands_title', __('shop.home_brands')) }}

        </h2>

        <div class="mt-6 flex flex-wrap justify-center items-center gap-6 sm:gap-8">

            @foreach($brands as $b)

                <a href="{{ route('brands.show', $b) }}"

                   class="shop-brands-band__item group"

                   title="{{ $b->name }} — {{ __('shop.view_brand_products') }}">

                    @include('shop.partials.brand-logo', ['brand' => $b, 'class' => 'shop-brands-band__logo', 'fallbackClass' => 'shop-brands-band__name'])

                </a>

            @endforeach

        </div>

    </section>

@endif

