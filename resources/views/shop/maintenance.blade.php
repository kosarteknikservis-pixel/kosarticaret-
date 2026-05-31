@php
    use App\Support\SiteLogo;
    $logoUrl = SiteLogo::url();
    $siteName = SiteLogo::alt();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | {{ \App\Support\SiteName::get() }}</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @php $shopCssVer = @filemtime(public_path('css/shop.css')) ?: time(); @endphp
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v={{ $shopCssVer }}">
</head>
<body class="shop-maint-body">
    <div class="shop-maint" role="main">
        <div class="shop-maint__scene" aria-hidden="true">
            <div class="shop-maint__mesh"></div>
            <div class="shop-maint__orb shop-maint__orb--1"></div>
            <div class="shop-maint__orb shop-maint__orb--2"></div>
            <div class="shop-maint__orb shop-maint__orb--3"></div>
            <div class="shop-maint__grid"></div>
            <span class="shop-maint__spark shop-maint__spark--1"></span>
            <span class="shop-maint__spark shop-maint__spark--2"></span>
            <span class="shop-maint__spark shop-maint__spark--3"></span>
            <span class="shop-maint__spark shop-maint__spark--4"></span>
        </div>

        <article class="shop-maint__card">
            <div class="shop-maint__card-shine" aria-hidden="true"></div>

            <div class="shop-maint__logo-stage">
                <div class="shop-maint__logo-ring" aria-hidden="true"></div>
                <div class="shop-maint__logo-ring shop-maint__logo-ring--2" aria-hidden="true"></div>
                <div class="shop-maint__logo-core">
                    @if($logoUrl)
                        <img
                            src="{{ $logoUrl }}"
                            alt="{{ $siteName }}"
                            class="shop-maint__logo-img"
                            width="220"
                            height="64"
                            decoding="async"
                        >
                    @else
                        <span class="shop-maint__logo-fallback" aria-hidden="true">K</span>
                    @endif
                </div>
            </div>

            <div class="shop-maint__badge">
                <span class="shop-maint__badge-dot" aria-hidden="true"></span>
                <span>Bakım modu</span>
            </div>

            <h1 class="shop-maint__title">{{ $title }}</h1>
            <p class="shop-maint__message">{{ $message }}</p>

            <div class="shop-maint__loader" role="status" aria-label="Güncelleme devam ediyor">
                <div class="shop-maint__loader-track">
                    <div class="shop-maint__loader-bar"></div>
                </div>
                <div class="shop-maint__loader-dots" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>
            </div>

            <p class="shop-maint__hint">
                <svg class="shop-maint__hint-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                Yönetici hesabıyla giriş yaptıysanız mağazayı önizleyebilirsiniz.
            </p>
        </article>
    </div>
</body>
</html>
