@php
    $whatsapp = preg_replace('/\D/', '', \App\Models\SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp')));
    $address = \App\Models\SiteSetting::get('contact_address', config('kosar.contact.address'));
    $tagline = app(\App\Services\StoreConfig::class)->vitrin('tagline', __('shop.tagline'));
    $promoText = \App\Models\SiteSetting::get('promo_text', config('kosar.defaults.promo_text'));
@endphp
<footer class="shop-footer mt-auto" role="contentinfo">
    <div class="shop-footer__main">
        <div class="shop-container shop-footer__main-inner">
            <div class="shop-footer__grid">
                <div class="shop-footer__brand">
                    <div class="shop-footer__brand-inner">
                        <div class="shop-footer__brand-head">
                            <x-shop.brand-lockup variant="footer" />
                            @if(filled($tagline))
                                <p class="shop-footer__brand-tagline">{{ $tagline }}</p>
                            @endif
                        </div>

                        <p class="shop-footer__desc">{{ \App\Models\SiteSetting::get('site_description', config('kosar.description')) }}</p>

                        @if(filled($promoText))
                            <p class="shop-footer__promo">
                                <x-shop.icon name="truck" class="w-4 h-4 shrink-0" />
                                <span>{{ $promoText }}</span>
                            </p>
                        @endif

                        @if($address)
                            <p class="shop-footer__address">
                                <span class="shop-footer__address-icon" aria-hidden="true">
                                    <x-shop.icon name="map-pin" class="w-4 h-4" />
                                </span>
                                <span>{{ $address }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="shop-footer__col shop-footer__col--links">
                    <p class="shop-footer__heading">{{ __('shop.footer_shop') }}</p>
                    <ul class="shop-footer__links">
                        <li><a href="{{ route('products.index') }}" class="shop-footer__link">{{ __('shop.all_products') }}</a></li>
                        <li><a href="{{ route('categories.index') }}" class="shop-footer__link">{{ __('shop.categories') }}</a></li>
                        <li><a href="{{ route('brands.index') }}" class="shop-footer__link">{{ __('shop.brands') }}</a></li>
                        <li><a href="{{ route('favorites.index') }}" class="shop-footer__link">{{ __('shop.favorites') }}</a></li>
                        <li><a href="{{ route('blog.index') }}" class="shop-footer__link">{{ __('shop.blog') }}</a></li>
                    </ul>
                </div>

                <div class="shop-footer__col shop-footer__col--links">
                    <p class="shop-footer__heading">{{ __('shop.footer_service') }}</p>
                    <ul class="shop-footer__links">
                        <li><a href="{{ route('tracking.show') }}" class="shop-footer__link">{{ __('shop.tracking') }}</a></li>
                        <li><a href="{{ route('contact.show') }}" class="shop-footer__link">{{ __('shop.contact') }}</a></li>
                        <li><a href="{{ route('cart.index') }}" class="shop-footer__link">{{ __('shop.cart') }}</a></li>
                        @foreach(($footerNavItems ?? []) as $link)
                            <li>
                                <a href="{{ $link->url }}" class="shop-footer__link" @if($link->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="shop-footer__col shop-footer__col--links">
                    <p class="shop-footer__heading">{{ __('shop.footer_legal') }}</p>
                    <ul class="shop-footer__links">
                        @foreach(\App\Models\Page::query()->where('published', true)->orderBy('sort_order')->get() as $footerPage)
                            <li><a href="{{ route('pages.show', $footerPage) }}" class="shop-footer__link">{{ $footerPage->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @include('shop.partials.footer-trust')

    <div class="shop-footer__bar">
        <div class="shop-container shop-footer__bar-inner">
            <div class="shop-footer__contacts">
                <a href="mailto:{{ \App\Models\SiteSetting::get('contact_email', config('kosar.contact.email')) }}" class="shop-footer__contact">
                    <x-shop.icon name="mail" class="w-4 h-4 shrink-0" />
                    <span>{{ \App\Models\SiteSetting::get('contact_email', config('kosar.contact.email')) }}</span>
                </a>
                <a href="tel:{{ preg_replace('/\D/', '', \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone'))) }}" class="shop-footer__contact">
                    <x-shop.icon name="phone" class="w-4 h-4 shrink-0" />
                    <span>{{ \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone')) }}</span>
                </a>
                @if(strlen($whatsapp) >= 10)
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="shop-footer__contact shop-footer__contact--wa">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>WhatsApp</span>
                    </a>
                @endif
            </div>
            <p class="shop-footer__copy">
                © {{ date('Y') }} {{ app(\App\Services\StoreConfig::class)->vitrin('legal_name', config('kosar.legal_name')) }}.
                <span class="shop-footer__rights">{{ __('shop.footer_rights') }}</span>
            </p>
        </div>
    </div>
</footer>
