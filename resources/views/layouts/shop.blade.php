<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $gtmId = \App\Models\SiteSetting::get('google_tag_manager_id'); @endphp
    {{-- GTM + Meta Pixel: head’de anlık yükleme yok — çerez kabulü / idle (aşağıda) --}}
    @include('shop.partials.meta-pixel')
    <script>
        window.dataLayer = window.dataLayer || [];
        window.KosarGtmId = @json(filled($gtmId) ? $gtmId : null);
        window.kosarLoadGtm = function () {
            const i = window.KosarGtmId;
            if (!i || window.KosarGtmLoaded) {
                return;
            }
            window.KosarGtmLoaded = true;
            window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            const f = document.getElementsByTagName('script')[0];
            const j = document.createElement('script');
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i;
            f.parentNode.insertBefore(j, f);
        };
        window.kosarMetaFromGa4 = function (eventName, payload) {
            const items = Array.isArray(payload.items) ? payload.items : [];
            const contentIds = [];
            const contents = [];
            items.forEach(function (item) {
                if (!item || item.item_id === undefined || item.item_id === null || item.item_id === '') {
                    return;
                }
                const id = String(item.item_id);
                contentIds.push(id);
                contents.push({
                    id: id,
                    quantity: Math.max(1, parseInt(item.quantity, 10) || 1),
                    item_price: parseFloat(item.price) || 0,
                });
            });
            const data = {
                content_type: 'product',
                content_ids: contentIds,
                contents: contents,
                currency: payload.currency || 'TRY',
                value: parseFloat(payload.value) || 0,
            };
            if (eventName === 'purchase' && payload.transaction_id) {
                data.num_items = contents.reduce(function (sum, row) {
                    return sum + (row.quantity || 0);
                }, 0);
            }
            return data;
        };
        window.kosarTrackEcommerce = function (eventName, params) {
            if (!eventName) {
                return;
            }
            const payload = params && typeof params === 'object' ? params : {};
            try {
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: eventName,
                    ecommerce: payload,
                });
            } catch (e) {}
            if (typeof window.gtag === 'function') {
                try {
                    window.gtag('event', eventName, payload);
                } catch (e) {}
            }
            if (typeof window.fbq === 'function') {
                const metaMap = {
                    view_item: 'ViewContent',
                    add_to_cart: 'AddToCart',
                    begin_checkout: 'InitiateCheckout',
                    purchase: 'Purchase',
                };
                const metaEvent = metaMap[eventName];
                if (metaEvent) {
                    try {
                        const metaPayload = window.kosarMetaFromGa4(eventName, payload);
                        const options = {};
                        if (eventName === 'purchase' && payload.transaction_id) {
                            options.eventID = String(payload.transaction_id);
                        }
                        if (Object.keys(options).length) {
                            window.fbq('track', metaEvent, metaPayload, options);
                        } else {
                            window.fbq('track', metaEvent, metaPayload);
                        }
                    } catch (e) {}
                }
            }
        };
    </script>
    @include('shop.partials.meta')
    @include('partials.favicon-links')
    @php
        $themeValues = \App\Support\ThemeSettings::values();
        $themeClasses = implode(' ', \App\Support\ThemeSettings::bodyClasses($themeValues));
        $themeStyle = \App\Support\ThemeSettings::inlineStyle($themeValues);
        $themeCustomCss = \App\Support\ThemeSettings::customCss();
    @endphp
    <style>:root{ {!! $themeStyle !!} }</style>
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/urbanist-latin-ext-wght.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/urbanist-latin-wght.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/plus-jakarta-sans-latin-ext-wght.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/plus-jakarta-sans-latin-wght.woff2') }}" crossorigin>
    {{-- Urbanist self-host (Google Fonts stylesheet kaldırıldı — render-blocking) --}}
    {{-- Vite app.css: Blade’deki Tailwind utility’ler (hidden/flex/md:…) için gerekli --}}
    @vite(['resources/css/app.css'])
    @php
        $shopCssFull = public_path('css/shop.css');
        $shopCssMin = public_path('css/shop.min.css');
        $shopCssPath = 'css/shop.css';
        if (file_exists($shopCssMin) && file_exists($shopCssFull) && filemtime($shopCssMin) >= filemtime($shopCssFull)) {
            $shopCssPath = 'css/shop.min.css';
        }
        $shopCssVer = @filemtime(public_path($shopCssPath)) ?: time();
    @endphp
    <link rel="stylesheet" href="{{ asset($shopCssPath) }}?v={{ $shopCssVer }}">
    <noscript><style>.shop-reveal,.shop-reveal-group>*{opacity:1!important;transform:none!important}</style></noscript>
    @if($themeCustomCss !== '')
        <style id="theme-custom-css">{!! $themeCustomCss !!}</style>
    @endif
    @php
        $gaId = \App\Models\SiteSetting::get('google_analytics_id');
        // GTM varken GA4'ü GTM içinden yönetin; çift sayımı önlemek için doğrudan gtag yüklenmez.
        $loadDirectGa4 = filled($gaId) && blank($gtmId ?? \App\Models\SiteSetting::get('google_tag_manager_id'));
    @endphp
    @if($loadDirectGa4)
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            window.KosarAnalyticsId = @json($gaId);
        </script>
    @endif
    @stack('head')
</head>
<body class="shop-body text-slate-900 min-h-screen flex flex-col {{ $themeClasses }}">
    @if(filled($gtmId ?? null))
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
        height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif
    @if(session('preview_settings'))
        <div class="bg-amber-500 text-amber-950 text-center text-xs py-2 px-4 font-medium">{{ __('shop.preview_banner') }}
            <form method="post" action="{{ route('admin.preview.stop') }}" class="inline ml-2">@csrf<button type="submit" class="underline">Kapat</button></form>
        </div>
    @endif

    @include('shop.partials.header')
    @include('shop.partials.mobile-nav')
    @include('shop.partials.campaign-popup')

    @include('shop.partials.cart-drawer')
    <div id="shop-toast"
         class="shop-toast"
         role="status"
         aria-live="polite"
         aria-atomic="true"
         hidden></div>

    @if(session('success'))
        <div class="shop-container shop-flash-wrap">
            <p class="shop-flash shop-flash--success" role="alert">
                <x-shop.icon name="shield" class="w-5 h-5 shrink-0" />
                {{ session('success') }}
            </p>
        </div>
    @endif
    @if(session('error') || $errors->any())
        <div class="shop-container shop-flash-wrap space-y-2">
            @if(session('error'))
                <p class="shop-flash shop-flash--error" role="alert">{{ session('error') }}</p>
            @endif
            @foreach($errors->all() as $e)
                <p class="shop-flash shop-flash--error" role="alert">{{ $e }}</p>
            @endforeach
        </div>
    @endif

    <main class="flex-1 shop-container shop-main shop-main-enter w-full py-8 lg:py-10" id="main-content">
        @yield('content')
    </main>

    @include('shop.partials.footer')
    @include('shop.partials.mobile-bottom-bar')

    @php
        $wa = \App\Models\SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp'));
        $waDigits = $wa ? preg_replace('/\D/', '', (string) $wa) : '';
        $phoneRaw = (string) \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone'));
        $phoneDigits = preg_replace('/\D/', '', $phoneRaw);
        if (str_starts_with($phoneDigits, '0')) {
            $phoneDigits = substr($phoneDigits, 1);
        }
        if ($phoneDigits !== '' && ! str_starts_with($phoneDigits, '90')) {
            $phoneDigits = '90'.$phoneDigits;
        }
        $waFloatingEnabled = \App\Models\SiteSetting::get('floating_whatsapp_enabled', '1') === '1';
        $scrollTopEnabled = \App\Models\SiteSetting::get('scroll_top_enabled', '1') === '1';
        $supportChatEnabled = \App\Support\SupportAssistantConfig::isEnabled();
        $showWaSticky = $waDigits !== '' && $waFloatingEnabled;
        $showCallSticky = $phoneDigits !== '';
        $showFloatDock = $scrollTopEnabled || $supportChatEnabled;
    @endphp
    {{-- Aqua .sticky-whatsapp / .sticky-call: masaüstü kenar sekmeleri, mobilde alt bar üstü yan yana --}}
    @if($showWaSticky)
        <a href="https://wa.me/{{ $waDigits }}"
           target="_blank"
           rel="noopener"
           class="shop-contact-sticky shop-contact-sticky--wa"
           aria-label="WhatsApp Destek"
           data-wa-sticky>
            <span class="shop-contact-sticky__icon" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.004 4C9.377 4 4 9.373 4 15.996c0 2.11.55 4.17 1.596 5.986L4.05 27.95l6.115-1.603A11.94 11.94 0 0 0 16.004 28C22.63 28 28 22.627 28 16.004 28 9.373 22.63 4 16.004 4zm6.95 17.02c-.29.816-1.69 1.548-2.35 1.64-.63.09-1.43.128-2.31-.145-.53-.165-1.21-.39-2.08-.76-3.66-1.58-6.05-5.27-6.23-5.51-.18-.25-1.46-1.94-1.46-3.7 0-1.76.92-2.62 1.25-2.98.33-.36.72-.45.96-.45h.69c.22 0 .52-.08.81.62.3.72 1.01 2.48 1.1 2.66.09.18.15.39.03.62-.12.24-.18.39-.36.6-.18.21-.38.47-.54.63-.18.18-.36.37-.155.72.21.36.93 1.53 2 2.48 1.38 1.22 2.54 1.6 2.9 1.78.36.18.57.15.78-.09.21-.24.9-1.05 1.14-1.41.24-.36.48-.3.81-.18.33.12 2.1.99 2.46 1.17.36.18.6.27.69.42.09.15.09.87-.2 1.69z"/>
                </svg>
            </span>
            <span class="shop-contact-sticky__label">WhatsApp</span>
        </a>
    @endif
    @if($showCallSticky)
        <a href="tel:+{{ $phoneDigits }}"
           class="shop-contact-sticky shop-contact-sticky--call"
           aria-label="Bizi Arayın"
           data-call-sticky>
            <span class="shop-contact-sticky__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c1.2.34 1.99.57 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </span>
            <span class="shop-contact-sticky__label">Bizi Arayın</span>
        </a>
    @endif

    @if($showFloatDock)
    <div class="shop-float-dock{{ ($showWaSticky || $showCallSticky) ? ' shop-float-dock--above-contact' : '' }}" data-float-dock aria-hidden="false">
        @if($supportChatEnabled)
            @php
                $supportChatJsVer = @filemtime(public_path('js/support-chat.js')) ?: time();
                $supportChatCssVer = @filemtime(public_path('css/support-chat.css')) ?: time();
                $supportPushKey = app(\App\Services\SupportAssistant\SupportChatPushService::class)->publicKey();
            @endphp
            <button type="button" class="shop-ai-launcher" data-support-chat-open
                    data-endpoint="{{ route('support-chat.message') }}"
                    data-reset-endpoint="{{ route('support-chat.reset') }}"
                    data-replies-endpoint="{{ route('support-chat.replies') }}"
                    data-replies-seen-endpoint="{{ route('support-chat.replies.seen') }}"
                    @if($supportPushKey)
                    data-subscribe-endpoint="{{ route('support-chat.subscribe') }}"
                    data-push-key="{{ $supportPushKey }}"
                    data-sw="{{ asset('js/destek-bildirim-sw.js') }}"
                    @endif
                    data-cart-add="{{ route('cart.ajax.add', ['product' => '__slug__']) }}"
                    data-whatsapp="{{ \App\Support\SupportAssistantConfig::whatsappUrl('Merhaba, sitenizden yazıyorum.') }}"
                    data-privacy-url="{{ route('pages.show', 'kvkk') }}"
                    data-script="{{ asset('js/support-chat.js') }}?v={{ $supportChatJsVer }}"
                    data-style="{{ asset('css/support-chat.css') }}?v={{ $supportChatCssVer }}"
                    aria-haspopup="dialog" aria-expanded="false" aria-label="Destek asistanına soru sor">
                <span class="shop-ai-launcher__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 12.5c0 3.6-3.6 6.5-8 6.5-1.1 0-2.2-.2-3.1-.5L4 20l1.3-3.4C4.5 15.4 4 14 4 12.5 4 8.9 7.6 6 12 6s8 2.9 8 6.5Z" />
                        <path d="M8.5 12.5h.01M12 12.5h.01M15.5 12.5h.01" stroke-width="2.4" />
                    </svg>
                    <span class="shop-ai-launcher__dot"></span>
                </span>
                <span class="shop-ai-launcher__label">Asistana sor</span>
                <span class="shop-ai-launcher__badge" data-support-chat-badge hidden></span>
            </button>
        @endif

        @if($scrollTopEnabled)
            <button type="button" class="shop-scroll-top" data-scroll-top aria-label="Yukarı çık">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 19V5" />
                    <path d="M6.5 10.5 12 5l5.5 5.5" />
                </svg>
            </button>
        @endif
    </div>
    @endif

    @if($supportChatEnabled)
    <script>
        (() => {
            const load = (button) => {
                if (window.KosarSupportChat) {
                    return Promise.resolve();
                }
                if (button.kcLoading) {
                    return button.kcLoading;
                }
                button.setAttribute('data-loading', '');
                const style = new Promise((resolve) => {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = button.dataset.style;
                    link.onload = resolve;
                    link.onerror = resolve;
                    document.head.appendChild(link);
                });
                const script = new Promise((resolve, reject) => {
                    const el = document.createElement('script');
                    el.src = button.dataset.script;
                    el.onload = resolve;
                    el.onerror = reject;
                    document.head.appendChild(el);
                });
                button.kcLoading = Promise.all([style, script]).finally(() => button.removeAttribute('data-loading'));
                return button.kcLoading;
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-support-chat-open]');
                if (!button) {
                    return;
                }
                event.preventDefault();
                load(button).then(() => window.KosarSupportChat && window.KosarSupportChat.toggle(button)).catch(() => {
                    button.kcLoading = null;
                    if (button.dataset.whatsapp) {
                        window.open(button.dataset.whatsapp, '_blank', 'noopener');
                    }
                });
            });

            const launcher = document.querySelector('[data-support-chat-open]');
            if (!launcher) {
                return;
            }
            const openChat = () => load(launcher).then(() => window.KosarSupportChat && window.KosarSupportChat.open(launcher)).catch(() => {});

            const badge = launcher.querySelector('[data-support-chat-badge]');
            window.kcSupportChatBadge = (count) => {
                if (!badge) {
                    return;
                }
                badge.hidden = !(count > 0);
                badge.textContent = count > 9 ? '9+' : String(count || '');
                launcher.setAttribute('aria-label', count > 0 ? 'Destek asistanı: ' + count + ' yeni yanıt' : 'Destek asistanına soru sor');
            };

            const recentlyActive = () => {
                try {
                    const at = Number(localStorage.getItem('kc-chat-active'));
                    return at > 0 && Date.now() - at < 30 * 864e5;
                } catch (e) {
                    return false;
                }
            };
            let checks = 0;
            const checkReplies = (force) => {
                if (document.hidden || !launcher.dataset.repliesEndpoint || (!force && !recentlyActive())) {
                    return;
                }
                if (window.KosarSupportChat && window.KosarSupportChat.isOpen()) {
                    window.KosarSupportChat.refreshReplies();
                    return;
                }
                fetch(launcher.dataset.repliesEndpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                    .then((response) => (response.ok ? response.json() : null))
                    .then((data) => {
                        if (!data) {
                            return;
                        }
                        window.kcSupportChatBadge(data.unread || 0);
                        if ((data.unread || 0) > 0) {
                            try {
                                localStorage.setItem('kc-chat-active', String(Date.now()));
                            } catch (e) {}
                        }
                    })
                    .catch(() => {});
            };

            const fromNotification = () => {
                if (window.location.hash !== '#destek-asistani') {
                    return false;
                }
                history.replaceState(null, '', window.location.pathname + window.location.search);
                openChat();
                return true;
            };
            window.addEventListener('hashchange', fromNotification);
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.addEventListener('message', (event) => {
                    const type = event.data && event.data.type;
                    if (type === 'kc-support-open') {
                        openChat();
                    } else if (type === 'kc-support-reply') {
                        checkReplies();
                    }
                });
            }

            try {
                if (!fromNotification() && sessionStorage.getItem('kc-chat-open') === '1' && window.matchMedia('(min-width: 640px)').matches) {
                    load(launcher).then(() => window.KosarSupportChat && window.KosarSupportChat.open(launcher, { restore: true }));
                }
            } catch (e) {}

            window.setTimeout(() => checkReplies(true), 2500);
            window.setInterval(() => {
                if (++checks <= 20) {
                    checkReplies();
                }
            }, 90000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    checkReplies();
                }
            });
        })();
    </script>
    @endif

    @php $vitrin = app(\App\Services\StoreConfig::class); @endphp
    <div id="cookie-banner" class="shop-cookie-banner hidden" role="dialog" aria-labelledby="cookie-banner-title" aria-modal="true" aria-label="{{ __('shop.cookie_title') }}">
        <div class="shop-cookie-banner__backdrop" aria-hidden="true"></div>
        <div class="shop-cookie-banner__wrap">
            <div class="shop-cookie-banner__card">
                <div class="shop-cookie-banner__head">
                    <span class="shop-cookie-banner__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 2 2 0 0 1-3-3 2 2 0 0 1-3-3 2 2 0 0 1-5-5z" />
                            <circle cx="8.5" cy="9.5" r="0.75" fill="currentColor" stroke="none" />
                            <circle cx="12" cy="7.5" r="0.75" fill="currentColor" stroke="none" />
                            <circle cx="15" cy="11" r="0.75" fill="currentColor" stroke="none" />
                        </svg>
                    </span>
                    <div class="shop-cookie-banner__text">
                        <p id="cookie-banner-title" class="shop-cookie-banner__title">{{ __('shop.cookie_title') }}</p>
                        <p class="shop-cookie-banner__desc">
                            {{ $vitrin->vitrin('cookie_text', __('shop.cookie_text')) }}
                            <a href="{{ route('pages.show', 'cerez-politikasi') }}" class="shop-cookie-banner__link">{{ __('shop.cookie_privacy') }}</a>
                        </p>
                    </div>
                </div>
                <div class="shop-cookie-banner__actions">
                    <button type="button" data-cookie-reject class="shop-cookie-btn shop-cookie-btn--ghost">
                        {{ __('shop.cookie_reject') }}
                    </button>
                    <button type="button" data-cookie-accept class="shop-cookie-btn shop-cookie-btn--primary">
                        {{ $vitrin->vitrin('cookie_accept', __('shop.cookie_accept')) }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            if (navigator.webdriver) {
                return;
            }

            const runIdle = (callback, timeout = 4500) => {
                if ('requestIdleCallback' in window) {
                    window.requestIdleCallback(callback, { timeout });
                    return;
                }

                window.setTimeout(callback, timeout);
            };

            const loadGoogleAnalytics = () => {
                const id = window.KosarAnalyticsId;
                if (!id || window.KosarAnalyticsLoaded) {
                    return;
                }

                window.KosarAnalyticsLoaded = true;
                const script = document.createElement('script');
                script.async = true;
                script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
                script.onload = () => {
                    window.gtag('js', new Date());
                    window.gtag('config', id);
                    window.dispatchEvent(new CustomEvent('kosar:analytics-ready'));
                };
                document.head.appendChild(script);
            };

            const scheduleAnalytics = () => {
                runIdle(loadGoogleAnalytics, 6500);
                ['pointerdown', 'keydown', 'scroll'].forEach((eventName) => {
                    window.addEventListener(eventName, loadGoogleAnalytics, { once: true, passive: true });
                });
            };

            const endpoint = @json(route('analytics.heartbeat'));

            const ping = () => {
                const xsrfMatch = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
                const token = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : '';
                if (!token || document.visibilityState === 'hidden') {
                    return;
                }

                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ url: window.location.href }),
                    credentials: 'same-origin',
                    keepalive: true,
                }).catch(() => {});
            };

            const loadMarketingTags = () => {
                if (typeof window.kosarLoadGtm === 'function') {
                    window.kosarLoadGtm();
                }
                if (typeof window.kosarLoadMetaPixel === 'function') {
                    window.kosarLoadMetaPixel();
                }
            };

            const scheduleMarketingAfterConsent = () => {
                let consent = null;
                try {
                    consent = localStorage.getItem('kosar-cerez-onay');
                } catch (e) {}

                if (consent === 'rejected') {
                    return;
                }

                if (consent === 'accepted') {
                    runIdle(loadMarketingTags, 4500);
                    return;
                }

                window.addEventListener('kosar:cookie-preference', (event) => {
                    if (event?.detail?.value === 'accepted') {
                        runIdle(loadMarketingTags, 1200);
                    }
                });
            };

            scheduleAnalytics();
            scheduleMarketingAfterConsent();
            runIdle(ping, 8000);
            window.setInterval(ping, 60000);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    ping();
                }
            });
        })();
    </script>
    @php
        $shopJsFull = public_path('js/shop.js');
        $shopJsMin = public_path('js/shop.min.js');
        $shopJsPath = 'js/shop.js';
        if (file_exists($shopJsMin) && file_exists($shopJsFull) && filemtime($shopJsMin) >= filemtime($shopJsFull)) {
            $shopJsPath = 'js/shop.min.js';
        }
        $shopJsVer = @filemtime(public_path($shopJsPath)) ?: time();
    @endphp
    <script src="{{ asset($shopJsPath) }}?v={{ $shopJsVer }}" defer></script>
    @stack('scripts')
</body>
</html>
