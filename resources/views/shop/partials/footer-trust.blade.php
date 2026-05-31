@php
    $cards       = \App\Support\FooterTrust::cards();
    $compliance  = \App\Support\FooterTrust::compliance();
    $payments    = \App\Support\FooterTrust::paymentMethods();
    $hasContent  = $cards !== [] || $compliance !== [] || $payments !== [];
@endphp

@if($hasContent)
<div class="kft shop-reveal" role="contentinfo" aria-label="{{ __('shop.footer_compliance_title') }}">
    <div class="shop-container kft__inner">

        {{-- Kart logoları --}}
        @if($cards !== [])
            <ul class="kft__cards" role="list" aria-label="{{ __('shop.footer_cards_title') }}">
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
        @endif

        {{-- Dikey ayırıcı --}}
        @if($cards !== [] && ($compliance !== [] || $payments !== []))
            <span class="kft__sep" aria-hidden="true"></span>
        @endif

        {{-- Güven rozetleri --}}
        <ul class="kft__badges" role="list">
            @foreach($compliance as $item)
                <li>
                    @if(($item['special'] ?? null) === 'etbis')
                        @if($item['url'])
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer"
                               class="kft__badge kft__badge--etbis kft__badge--link" title="{{ $item['hint'] ?? '' }}">
                                <span class="kft__badge-mark">ETBİS</span>
                                <span class="kft__badge-sub">{{ __('shop.footer_etbis_registered') }}</span>
                            </a>
                        @else
                            <span class="kft__badge kft__badge--etbis kft__badge--pending"
                                  title="{{ __('shop.footer_etbis_pending_hint') }}">
                                <span class="kft__badge-mark">ETBİS</span>
                                <span class="kft__badge-sub">{{ __('shop.footer_etbis_pending') }}</span>
                            </span>
                        @endif
                    @elseif(($item['special'] ?? null) === 'kvkk' && $item['url'])
                        <a href="{{ $item['url'] }}" class="kft__badge kft__badge--link">
                            @if($item['icon'])
                                <x-shop.icon :name="$item['icon']" class="kft__badge-icon" />
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @else
                        <span class="kft__badge" title="{{ $item['hint'] ?? '' }}">
                            @if(!empty($item['icon']))
                                <x-shop.icon :name="$item['icon']" class="kft__badge-icon" />
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </span>
                    @endif
                </li>
            @endforeach

            {{-- Ödeme yöntemleri rozet olarak --}}
            @foreach($payments as $method)
                <li>
                    <span class="kft__badge">
                        <x-shop.icon :name="$method['icon']" class="kft__badge-icon" />
                        <span>{{ $method['name'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>

    </div>
</div>
@endif
