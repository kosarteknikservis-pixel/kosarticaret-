<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') | Kosar Panel</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) ?: 1 }}">
    @stack('head')
</head>
<body class="admin-body text-slate-900 min-h-screen">
    <div class="flex min-h-screen">
        {{-- Masaüstü sidebar --}}
        <aside class="admin-sidebar admin-sidebar-desktop shrink-0 hidden lg:flex flex-col min-h-screen sticky top-0">
            @include('admin.partials.sidebar-brand')
            @include('admin.partials.sidebar-nav')
            <div class="p-3 border-t border-white/10 mt-auto">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-nav-link admin-nav-link--store">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    Mağazayı aç
                </a>
                <form method="post" action="{{ route('admin.logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="admin-nav-link w-full text-left text-slate-400 hover:text-red-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006.75 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                        Çıkış
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobil sidebar --}}
        <div id="admin-sidebar-overlay" class="fixed inset-0 z-50 hidden lg:hidden bg-slate-900/60" aria-hidden="true">
            <aside id="admin-sidebar-panel" class="admin-sidebar absolute left-0 top-0 h-full flex flex-col translate-x-[-100%]" style="width: var(--admin-sidebar)">
                @include('admin.partials.sidebar-brand')
                <button type="button" id="admin-sidebar-close" class="absolute top-4 right-3 p-2 text-slate-300 hover:text-white rounded-lg lg:hidden" aria-label="Menüyü kapat">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @include('admin.partials.sidebar-nav')
                <div class="p-3 border-t border-white/10">
                    <a href="{{ route('home') }}" target="_blank" class="admin-nav-link">Mağazayı aç</a>
                    <form method="post" action="{{ route('admin.logout') }}" class="mt-1">@csrf
                        <button type="submit" class="admin-nav-link w-full text-left">Çıkış</button>
                    </form>
                </div>
            </aside>
        </div>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" id="admin-sidebar-open" class="admin-sidebar-mobile-trigger lg:hidden p-2 rounded-lg border border-slate-200 hover:bg-slate-50" aria-label="Menü">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="admin-topbar-eyebrow">Kosar Panel</p>
                        <h1 class="admin-page-title truncate text-lg lg:text-xl">@yield('title', 'Panel')</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if(session('preview_settings'))
                        <span class="hidden sm:inline text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5">Önizleme aktif</span>
                    @endif
                    <a href="{{ route('admin.profile.edit') }}" class="hidden md:inline text-sm font-semibold text-slate-500 hover:text-teal-700 transition-colors">
                        {{ auth()->user()?->email }}
                    </a>
                    <a href="{{ route('home') }}" target="_blank" class="admin-btn admin-btn-secondary text-xs py-2">Mağaza</a>
                </div>
            </header>

            <div class="admin-content">
                @if(session('success'))
                    <p class="admin-alert-success mb-4" role="alert">{{ session('success') }}</p>
                @endif
                @if($errors->any())
                    <div class="admin-alert-error mb-4 space-y-1" role="alert">
                        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                    </div>
                @endif
                @yield('content')
                @stack('admin-form-delete')
            </div>
        </div>
    </div>
    <script>
        window.AdminAi = {
            routes: {
                slug: @json(route('admin.ai.slug')),
                meta: @json(route('admin.ai.meta')),
                generate: @json(route('admin.ai.generate')),
            },
            openai: @json(\App\Services\OpenAiService::isConfigured()),
        };
    </script>
    <script src="{{ asset('js/admin.js') }}" defer></script>
    <script src="{{ asset('js/admin-seo-rich.js') }}" defer></script>
    <script src="{{ asset('js/admin-ai.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
