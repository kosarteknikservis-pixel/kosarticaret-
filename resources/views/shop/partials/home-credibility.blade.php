@if(($siteStats['products'] ?? 0) > 0)
@php
    $trustItems = [
        ['icon' => 'shield', 'title' => __('shop.home_trust_secure'), 'desc' => __('shop.home_trust_secure_desc')],
        ['icon' => 'truck', 'title' => __('shop.home_trust_shipping'), 'desc' => __('shop.home_trust_shipping_desc')],
        ['icon' => 'phone', 'title' => __('shop.home_trust_support'), 'desc' => __('shop.home_trust_support_desc')],
        ['icon' => 'star', 'title' => __('shop.home_trust_quality'), 'desc' => __('shop.home_trust_quality_desc')],
    ];
@endphp

<section class="shop-home-credibility" aria-labelledby="home-credibility-heading">
    <div class="shop-home-credibility__shell">
        <h2 id="home-credibility-heading" class="shop-home-credibility__eyebrow">{{ __('shop.home_credibility_title') }}</h2>

        <dl class="shop-home-credibility__stats">
            <div class="shop-home-credibility__stat">
                <dt class="shop-home-credibility__stat-label">{{ __('shop.home_stats_products') }}</dt>
                <dd class="shop-home-credibility__stat-value">{{ number_format($siteStats['products'], 0, ',', '.') }}+</dd>
            </div>
            <div class="shop-home-credibility__stat">
                <dt class="shop-home-credibility__stat-label">{{ __('shop.home_stats_brands') }}</dt>
                <dd class="shop-home-credibility__stat-value">{{ number_format($siteStats['brands'], 0, ',', '.') }}+</dd>
            </div>
            <div class="shop-home-credibility__stat">
                <dt class="shop-home-credibility__stat-label">{{ __('shop.home_stats_categories') }}</dt>
                <dd class="shop-home-credibility__stat-value">{{ number_format($siteStats['categories'], 0, ',', '.') }}+</dd>
            </div>
            <div class="shop-home-credibility__stat">
                <dt class="shop-home-credibility__stat-label">{{ __('shop.home_stats_support') }}</dt>
                <dd class="shop-home-credibility__stat-value">{{ __('shop.home_stats_support_value') }}</dd>
            </div>
        </dl>

        <ul class="shop-home-credibility__trust">
            @foreach($trustItems as $item)
                <li class="shop-home-credibility__trust-item">
                    <span class="shop-home-credibility__trust-icon" aria-hidden="true">
                        <x-shop.icon :name="$item['icon']" class="w-[1.125rem] h-[1.125rem]" />
                    </span>
                    <div class="shop-home-credibility__trust-copy">
                        <p class="shop-home-credibility__trust-title">{{ $item['title'] }}</p>
                        <p class="shop-home-credibility__trust-desc">{{ $item['desc'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
