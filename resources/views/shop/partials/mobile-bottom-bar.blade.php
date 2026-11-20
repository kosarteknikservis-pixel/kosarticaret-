@php
    $isHome = request()->routeIs('home');
    $isFavorites = request()->routeIs('favorites.*');
    $isCart = request()->routeIs('cart.*');
    $isAccount = request()->routeIs('account.*', 'login', 'register');
    $accountHref = auth()->check() && ! (auth()->user()->is_admin ?? false)
        ? route('account.index')
        : route('login');
@endphp
<nav class="shop-bottom-bar lg:hidden" aria-label="{{ __('shop.main_nav') }}">
    <a href="{{ route('home') }}" class="shop-bottom-bar__item {{ $isHome ? 'is-active' : '' }}">
        <span class="shop-bottom-bar__icon" aria-hidden="true">
            <x-shop.icon name="home" class="w-5 h-5" />
        </span>
        <span class="shop-bottom-bar__label">{{ __('shop.home') }}</span>
    </a>
    <a href="{{ route('favorites.index') }}" class="shop-bottom-bar__item {{ $isFavorites ? 'is-active' : '' }}">
        <span class="shop-bottom-bar__icon" aria-hidden="true">
            <x-shop.icon name="heart" class="w-5 h-5" />
        </span>
        <span class="shop-bottom-bar__label">{{ __('shop.favorites') }}</span>
        <span data-favorite-count class="shop-bottom-bar__badge {{ ($favoriteCount ?? 0) < 1 ? 'is-empty' : '' }}">{{ $favoriteCount ?? 0 }}</span>
    </a>
    <button type="button"
            class="shop-bottom-bar__item {{ $isCart ? 'is-active' : '' }}"
            data-open-cart-drawer
            aria-label="{{ __('shop.cart_open') }}">
        <span class="shop-bottom-bar__icon" aria-hidden="true">
            <x-shop.icon name="cart" class="w-5 h-5" />
        </span>
        <span class="shop-bottom-bar__label">{{ __('shop.cart') }}</span>
        <span data-cart-count class="shop-bottom-bar__badge {{ ($cartCount ?? 0) < 1 ? 'is-empty' : '' }}">{{ $cartCount ?? 0 }}</span>
    </button>
    @auth
        @if(auth()->user()->is_admin)
            <a href="{{ route('admin.dashboard') }}" class="shop-bottom-bar__item">
                <span class="shop-bottom-bar__icon" aria-hidden="true">
                    <x-shop.icon name="user" class="w-5 h-5" />
                </span>
                <span class="shop-bottom-bar__label">{{ __('shop.admin_panel') }}</span>
            </a>
        @else
            <a href="{{ route('account.index') }}" class="shop-bottom-bar__item {{ $isAccount ? 'is-active' : '' }}">
                <span class="shop-bottom-bar__icon" aria-hidden="true">
                    <x-shop.icon name="user" class="w-5 h-5" />
                </span>
                <span class="shop-bottom-bar__label">{{ __('shop.account') }}</span>
            </a>
        @endif
    @else
        <a href="{{ $accountHref }}"
           class="shop-bottom-bar__item {{ $isAccount ? 'is-active' : '' }}"
           data-open-auth-modal
           data-auth-mode="login">
            <span class="shop-bottom-bar__icon" aria-hidden="true">
                <x-shop.icon name="user" class="w-5 h-5" />
            </span>
            <span class="shop-bottom-bar__label">{{ __('shop.login') }}</span>
        </a>
    @endauth
</nav>
