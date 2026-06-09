@php
    if (\App\Models\SiteSetting::get('pump_selector_enabled', '1') === '0') return;
    $applications = \App\Support\PumpSelectorUiConfig::applications();
    $featuredApps = array_values(array_filter($applications, fn ($app) => $app['featured']));
@endphp

<section class="shop-pump-teaser shop-reveal" aria-labelledby="pump-teaser-heading">
    <div class="shop-pump-teaser__card">
        <div class="shop-pump-teaser__main">
            <div class="shop-pump-teaser__copy">
                <p class="shop-pump-teaser__eyebrow">{{ __('shop.pump_selector_eyebrow') }}</p>
                <h2 id="pump-teaser-heading" class="shop-pump-teaser__title">{{ __('shop.pump_selector_teaser_title') }}</h2>
                <p class="shop-pump-teaser__sub">{{ __('shop.pump_selector_teaser_sub') }}</p>
                <ul class="shop-pump-teaser__highlights">
                    <li>{{ __('shop.pump_selector_badge_steps') }}</li>
                    <li>{{ __('shop.pump_selector_badge_free') }}</li>
                    <li>{{ __('shop.pump_selector_badge_catalog') }}</li>
                </ul>
                <a href="{{ route('pump-selector.show') }}" class="shop-pump-teaser__cta btn-primary">
                    {{ __('shop.pump_selector_teaser_cta') }}
                    <x-shop.icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>

            <div class="shop-pump-teaser__quick">
                <p class="shop-pump-teaser__quick-label">{{ __('shop.pump_selector_teaser_quick') }}</p>
                <div class="shop-pump-teaser__chips">
                    @foreach(array_slice($featuredApps, 0, 6) as $app)
                        <a href="{{ route('pump-selector.show', ['uygulama' => $app['id']]) }}"
                           class="shop-pump-teaser__chip">
                            <span class="shop-pump-teaser__chip-icon" aria-hidden="true">
                                <x-shop.icon :name="$app['icon']" class="w-4 h-4" />
                            </span>
                            <span>{{ $app['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
