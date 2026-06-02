<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('shop.partials.meta')
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#f4f7fb',
                            100: '#e8eef6',
                            200: '#c5d4e8',
                            300: '#93a8c9',
                            400: '#6b84a8',
                            500: '#4a6489',
                            600: '#2d4a73',
                            700: '#1e3a5f',
                            800: '#1a3254',
                            900: '#152a47',
                        },
                    },
                    maxWidth: { '8xl': '80rem' },
                },
            },
        };
    </script>
    @php $shopCssVer = @filemtime(public_path('css/shop.css')) ?: time(); @endphp
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v={{ $shopCssVer }}">
    @php $gaId = \App\Models\SiteSetting::get('google_analytics_id'); @endphp
    @if(filled($gaId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
    @endif
    @stack('head')
</head>
<body class="shop-body text-slate-900 min-h-screen flex flex-col">
    @php $promo = \App\Models\SiteSetting::get('promo_text', config('kosar.defaults.promo_text')); @endphp
    @if(session('preview_settings'))
        <div class="bg-amber-500 text-amber-950 text-center text-xs py-2 px-4 font-medium">{{ __('shop.preview_banner') }}
            <form method="post" action="{{ route('admin.preview.stop') }}" class="inline ml-2">@csrf<button type="submit" class="underline">Kapat</button></form>
        </div>
    @elseif($promo)
        <div class="shop-promo-bar text-center text-xs py-2.5 px-4 font-medium" role="region" aria-label="Kampanya">{{ $promo }}</div>
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

    @php
        $wa = \App\Models\SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp'));
        $waFloatingEnabled = \App\Models\SiteSetting::get('floating_whatsapp_enabled', '1') === '1';
        $scrollTopEnabled = \App\Models\SiteSetting::get('scroll_top_enabled', '1') === '1';
    @endphp
    @if($wa && $waFloatingEnabled)
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $wa) }}" target="_blank" rel="noopener"
           class="shop-wa-float"
           aria-label="WhatsApp">
            <span class="shop-wa-float__icon" aria-hidden="true">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </span>
        </a>
    @endif

    @if($scrollTopEnabled)
    <button type="button" class="shop-scroll-top" data-scroll-top aria-label="Yukarı çık">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 19V5" />
            <path d="M6.5 10.5 12 5l5.5 5.5" />
        </svg>
    </button>
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

    <script src="{{ asset('js/shop.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
