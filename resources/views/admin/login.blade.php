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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            @if($logoUrl = \App\Support\SiteLogo::url())
                <img src="{{ $logoUrl }}" alt="Kosar" class="mx-auto h-14 max-w-[180px] object-contain rounded-xl bg-white/10 p-2">
            @else
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-500 text-white font-bold text-2xl shadow-xl shadow-teal-900/50">K</span>
            @endif
            <h1 class="mt-4 text-2xl font-bold text-white">Kosar Panel</h1>
            <p class="text-teal-200/70 text-sm mt-1">Mağaza ve sipariş yönetimi</p>
        </div>

        <form method="post" action="{{ route('admin.login') }}" class="admin-card p-8 shadow-2xl">
            @csrf
            @if($errors->any())
                <p class="admin-alert-error mb-4">{{ $errors->first() }}</p>
            @endif
            <div class="space-y-4">
                <div>
                    <label class="admin-label">E-posta</label>
                    <input type="email" name="email" value="{{ old('email', 'admin@kosar.com.tr') }}" required class="admin-input" autocomplete="username">
                </div>
                <div>
                    <label class="admin-label">Şifre</label>
                    <input type="password" name="password" required class="admin-input" autocomplete="current-password">
                </div>
                <button type="submit" class="admin-btn admin-btn-primary w-full py-3 text-base mt-2">Giriş yap</button>
            </div>
        </form>

        <p class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-sm text-teal-300/80 hover:text-white transition-colors">← Mağazaya dön</a>
        </p>
    </div>
</body>
</html>
