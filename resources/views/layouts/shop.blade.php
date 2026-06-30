<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('shop.partials.meta')
    @include('partials.favicon-links')
    @php
        $themeValues = \App\Support\ThemeSettings::values();
        $themeClasses = implode(' ', \App\Support\ThemeSettings::bodyClasses($themeValues));
        $themeStyle = \App\Support\ThemeSettings::inlineStyle($themeValues);
        $themeCustomCss = \App\Support\ThemeSettings::customCss();
    @endphp
    <style>:root{ {!! $themeStyle !!} }</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"></noscript>
    @vite(['resources/css/app.css'])
    @php $shopCssVer = @filemtime(public_path('css/shop.css')) ?: time(); @endphp
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v={{ $shopCssVer }}">
    @if($themeCustomCss !== '')
        <style id="theme-custom-css">{!! $themeCustomCss !!}</style>
    @endif
    @php $gaId = \App\Models\SiteSetting::get('google_analytics_id'); @endphp
    @if(filled($gaId))
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            window.KosarAnalyticsId = @json($gaId);
        </script>
    @endif
    @stack('head')
</head>
<body class="shop-body text-slate-900 min-h-screen flex flex-col {{ $themeClasses }}">
    @php $promo = \App\Models\SiteSetting::get('promo_text', config('kosar.defaults.promo_text')); @endphp
    @if(session('preview_settings'))
        <div class="bg-amber-500 text-amber-950 text-center text-xs py-2 px-4 font-medium">{{ __('shop.preview_banner') }}
            <form method="post" action="{{ route('admin.preview.stop') }}" class="inline ml-2">@csrf<button type="submit" class="underline">Kapat</button></form>
        </div>
    @elseif($promo)
        <div class="shop-promo-bar" role="region" aria-label="Kampanya">{{ $promo }}</div>
    @endif

    @include('shop.partials.header')
    @include('shop.partials.mobile-nav')
    @include('shop.partials.auth-modal')

    @include('shop.partials.cart-drawer')
    <div id="shop-toast" class="hidden fixed bottom-24 right-4 z-[60] rounded-xl bg-brand-800 text-white text-sm px-4 py-3 shadow-xl max-w-xs" role="status"></div>

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
    @include('shop.partials.compare-bar')

    @php
        $wa = \App\Models\SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp'));
        $waFloatingEnabled = \App\Models\SiteSetting::get('floating_whatsapp_enabled', '1') === '1';
        $scrollTopEnabled = \App\Models\SiteSetting::get('scroll_top_enabled', '1') === '1';
        $pumpPillEnabled = \App\Support\PumpSelectorUiConfig::isEnabled() && ! request()->routeIs('pump-selector.*');
        $showFloatDock = ($wa && $waFloatingEnabled) || $pumpPillEnabled || $scrollTopEnabled;
    @endphp
    @if($showFloatDock)
    <div class="shop-float-dock" data-float-dock aria-hidden="false">
        @if($wa && $waFloatingEnabled)
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $wa) }}" target="_blank" rel="noopener"
               class="shop-wa-float"
               aria-label="WhatsApp">
                <span class="shop-wa-float__icon" aria-hidden="true">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </span>
            </a>
        @endif

        @if($pumpPillEnabled)
            <div class="shop-float-dock__pump-slot">
                <a href="{{ route('pump-selector.show') }}"
                   class="shop-pump-scroll-pill"
                   aria-label="{{ __('shop.pump_selector_program') }}">
                    <span class="shop-pump-scroll-pill__text">{{ __('shop.pump_selector_program') }}</span>
                    <x-shop.icon name="chevron-right" class="shop-pump-scroll-pill__icon" />
                </a>
            </div>
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
                        <h2 id="cookie-banner-title" class="shop-cookie-banner__title">{{ __('shop.cookie_title') }}</h2>
                        <p class="shop-cookie-banner__desc">
                            {{ $vitrin->vitrin('cookie_text', __('shop.cookie_text')) }}
                            <a href="{{ route('pages.show', 'gizlilik-politikasi') }}" class="shop-cookie-banner__link">{{ __('shop.cookie_privacy') }}</a>
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
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const ping = () => {
                if (!token || document.visibilityState === 'hidden') {
                    return;
                }

                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ url: window.location.href }),
                    credentials: 'same-origin',
                    keepalive: true,
                }).catch(() => {});
            };

            scheduleAnalytics();
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
