@php
    $campaign = app(\App\Services\PromotionService::class);
    $onCheckout = request()->routeIs('checkout.*');
    $showKasimPopup = ! $onCheckout && $campaign->kasim2026IsActive();
@endphp
@if($showKasimPopup)
    <div id="shop-campaign-popup"
         class="shop-campaign-popup hidden"
         data-campaign-popup
         data-campaign-key="kosar_kasim_2026_popup"
         aria-hidden="true">
        <div class="shop-campaign-popup__backdrop" data-campaign-close tabindex="-1"></div>
        <section class="shop-campaign-popup__dialog"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="shop-campaign-popup-title"
                 tabindex="-1">
            <button type="button" class="shop-campaign-popup__close" data-campaign-close aria-label="Kapat">
                <x-shop.icon name="x" class="w-5 h-5" />
            </button>

            <p class="shop-campaign-popup__eyebrow">30 Kasım’a kadar</p>
            <h2 id="shop-campaign-popup-title" class="shop-campaign-popup__title">Sepette kademeli indirim</h2>
            <p class="shop-campaign-popup__lead">Kod gerekmez. Sepet tutarı eşiği geçince en yüksek kademe otomatik uygulanır; 30 Kasım 23:59’da biter.</p>

            <ul class="shop-campaign-popup__tiers">
                @foreach(\App\Services\PromotionService::kasim2026Tiers() as $tier)
                    <li>
                        <span>{{ number_format($tier['min'], 0, ',', '.') }} TL ve üzeri</span>
                        <strong>{{ number_format($tier['discount'], 0, ',', '.') }} TL indirim</strong>
                    </li>
                @endforeach
            </ul>

            <div class="shop-campaign-popup__actions">
                <a href="{{ route('products.index') }}" class="btn-primary btn--block" data-campaign-close>Alışverişe başla</a>
                <button type="button" class="shop-campaign-popup__dismiss" data-campaign-close>Şimdi değil</button>
            </div>
        </section>
    </div>
@endif
