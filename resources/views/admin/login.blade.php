<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Kosar Panel Giriş</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) ?: 1 }}">
</head>
<body class="admin-login-page">
    <div class="admin-login-bg" aria-hidden="true">
        <span class="admin-login-orb admin-login-orb--1"></span>
        <span class="admin-login-orb admin-login-orb--2"></span>
        <span class="admin-login-grid"></span>
    </div>

    <main class="admin-login-wrap">
        <header class="admin-login-brand admin-login-reveal">
            <div class="admin-login-logo" aria-hidden="false">
                <span class="admin-login-logo__glow" aria-hidden="true"></span>
                <span class="admin-login-logo__shine" aria-hidden="true"></span>
                @if($logoUrl = \App\Support\SiteLogo::url())
                    <img src="{{ $logoUrl }}" alt="{{ \App\Support\SiteLogo::alt() }}" class="admin-login-logo__img" width="160" height="48" decoding="async">
                @else
                    <span class="admin-login-logo-fallback">K</span>
                @endif
            </div>
            <p class="admin-login-eyebrow">Yönetim paneli</p>
            <h1 class="admin-login-title">Kosar Panel</h1>
            <p class="admin-login-sub">Mağaza, sipariş ve katalog yönetimi</p>
        </header>

        <form method="post" action="{{ route('admin.login') }}" class="admin-login-card admin-login-reveal admin-login-reveal--2">
            @csrf
            @if($errors->any())
                <p class="admin-alert-error admin-login-alert" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="admin-login-field">
                <label class="admin-label" for="admin-email">E-posta</label>
                <div class="admin-login-input-wrap">
                    <svg class="admin-login-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 6h16v12H4V6Z"/><path d="m4 7 8 6 8-6"/></svg>
                    <input id="admin-email" type="email" name="email" value="{{ old('email') }}" required class="admin-input admin-login-input" autocomplete="username" placeholder="ornek@kosar.com.tr">
                </div>
            </div>

            <div class="admin-login-field">
                <label class="admin-label" for="admin-password">Şifre</label>
                <div class="admin-login-input-wrap">
                    <svg class="admin-login-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 118 0v3"/></svg>
                    <input id="admin-password" type="password" name="password" required class="admin-input admin-login-input" autocomplete="current-password" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary admin-login-submit">
                <span>Giriş yap</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </form>

        <p class="admin-login-back admin-login-reveal admin-login-reveal--3">
            <a href="{{ route('home') }}">← Mağazaya dön</a>
        </p>
    </main>
</body>
</html>
