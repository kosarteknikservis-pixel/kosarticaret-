@php
    $cards = \App\Support\FooterTrust::cards();
    $compliance = \App\Support\FooterTrust::compliance();
    $payments = \App\Support\FooterTrust::paymentMethods();
@endphp

@if($cards !== [] || $payments !== [] || $compliance !== [])
    <div class="shop-footer__trust">
        <div class="shop-container shop-footer-trust">
            @if($cards !== [])
                <div class="shop-footer-trust__group shop-footer-trust__group--cards">
                    <p class="shop-footer-trust__label">{{ __('shop.footer_cards_title') }}</p>
                    <ul class="shop-footer-pay-icons" role="list" aria-label="{{ __('shop.footer_cards_title') }}">
                        @foreach($cards as $card)
                            <li>
                                <x-shop.payment-card-icon
                                    :brand="$card['brand']"
                                    :label="$card['label']"
                                    :image="$card['image'] ?? null"
                                />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($payments !== [])
                <div class="shop-footer-trust__group">
                    <p class="shop-footer-trust__label">{{ __('shop.footer_payments_title') }}</p>
                    <ul class="shop-footer-payments" role="list">
                        @foreach($payments as $method)
                            <li>
                                <span class="shop-footer-payment">
                                    <x-shop.icon :name="$method['icon']" class="w-4 h-4 shrink-0" />
                                    <span>
                                        <span class="shop-footer-payment__name">{{ $method['name'] }}</span>
                                        @if(!empty($method['desc']))
                                            <span class="shop-footer-payment__desc">{{ $method['desc'] }}</span>
                                        @endif
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($compliance !== [])
                <div class="shop-footer-trust__group shop-footer-trust__group--wide">
                    <p class="shop-footer-trust__label">{{ __('shop.footer_compliance_title') }}</p>
                    <ul class="shop-footer-compliance" role="list">
                        @foreach($compliance as $item)
                            <li>
                                @if(($item['special'] ?? null) === 'etbis')
                                    @if($item['url'])
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" class="shop-footer-etbis shop-footer-etbis--linked" title="{{ $item['hint'] }}">
                                            <span class="shop-footer-etbis__mark">ETBİS</span>
                                            <span class="shop-footer-etbis__sub">{{ __('shop.footer_etbis_registered') }}</span>
                                        </a>
                                    @else
                                        <span class="shop-footer-etbis shop-footer-etbis--pending" title="{{ __('shop.footer_etbis_pending_hint') }}">
                                            <span class="shop-footer-etbis__mark">ETBİS</span>
                                            <span class="shop-footer-etbis__sub">{{ __('shop.footer_etbis_pending') }}</span>
                                        </span>
                                    @endif
                                @elseif(($item['special'] ?? null) === 'kvkk' && $item['url'])
                                    <a href="{{ $item['url'] }}" class="shop-footer-badge shop-footer-badge--link">
                                        @if($item['icon'])
                                            <x-shop.icon :name="$item['icon']" class="w-4 h-4 shrink-0" />
                                        @endif
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @else
                                    <span class="shop-footer-badge" title="{{ $item['hint'] ?? '' }}">
                                        @if(!empty($item['icon']))
                                            <x-shop.icon :name="$item['icon']" class="w-4 h-4 shrink-0" />
                                        @endif
                                        <span>{{ $item['label'] }}</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
