@php
    $promoMessages = $promoMessages ?? app(\App\Services\StoreConfig::class)->promoTickerMessages();
@endphp
@if($promoMessages !== [])
    <div class="shop-promo-bar" data-promo-ticker role="region" aria-label="Kampanya">
        <div class="shop-promo-bar__viewport" aria-live="polite">
            @foreach($promoMessages as $index => $message)
                <p class="shop-promo-bar__item{{ $index === 0 ? ' is-active' : '' }}" data-promo-ticker-item>
                    {{ $message }}
                </p>
            @endforeach
        </div>
    </div>
    @once
        @push('scripts')
            <script>
                (function () {
                    const root = document.querySelector('[data-promo-ticker]');
                    if (!root) return;
                    const items = Array.from(root.querySelectorAll('[data-promo-ticker-item]'));
                    if (items.length < 2) return;
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                    let index = 0;
                    window.setInterval(function () {
                        items[index].classList.remove('is-active');
                        index = (index + 1) % items.length;
                        items[index].classList.add('is-active');
                    }, 3200);
                })();
            </script>
        @endpush
    @endonce
@endif
