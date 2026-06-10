<div class="shop-topbar hidden md:block">
    <div class="shop-container shop-topbar__inner">
        <div class="shop-topbar__links">
            <a href="{{ route('tracking.show') }}">{{ __('shop.tracking') }}</a>
            <a href="{{ route('contact.show') }}">{{ __('shop.contact') }}</a>
            <a href="{{ route('blog.index') }}">{{ __('shop.blog') }}</a>
        </div>
        <div class="shop-topbar__meta">
            <span class="shop-topbar__hint">{{ __('shop.help_line') }}:</span>
            <a href="tel:{{ preg_replace('/\D/', '', \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone'))) }}" class="shop-topbar__phone">
                {{ \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone')) }}
            </a>
            @auth
                @if(auth()->user()->is_admin)
                    <span class="shop-topbar__sep" aria-hidden="true"></span>
                    <a href="{{ route('admin.dashboard') }}" class="shop-topbar__auth shop-topbar__auth--admin-top">{{ __('shop.admin_panel') }}</a>
                @endif
            @endauth
            @if(count(config('kosar.locales', ['tr'])) > 1)
                <span class="shop-topbar__sep" aria-hidden="true"></span>
                <div class="shop-topbar__locale" role="group" aria-label="Dil">
                    @foreach(config('kosar.locales', ['tr']) as $locale)
                        <a href="{{ route('locale.switch', $locale) }}" class="{{ app()->getLocale() === $locale ? 'is-active' : '' }}">{{ strtoupper($locale) }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<header id="shop-site-header" class="shop-site-header sticky top-0 z-40">
    <div class="shop-container">
        <div class="shop-site-header__row">
            <button type="button" id="mobile-menu-open" class="shop-header-icon lg:hidden" aria-label="{{ __('shop.menu_open') }}" aria-controls="mobile-nav-panel" aria-expanded="false">
                <span class="shop-header-icon__halo" aria-hidden="true"></span>
                <x-shop.icon name="menu" class="shop-header-icon__svg" />
            </button>

            <x-shop.brand-lockup variant="header" class="shop-site-header__brand shrink-0" />

            <div class="shop-site-header__search hidden sm:block" id="search-autocomplete-desktop">
                <form action="{{ route('search') }}" method="get" role="search" class="shop-search-form">
                    <label class="sr-only" for="header-search">{{ __('shop.search_label') }}</label>
                    <span class="shop-search-form__icon" aria-hidden="true">
                        <x-shop.icon name="search" class="w-5 h-5" />
                    </span>
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('shop.search_placeholder') }}" class="shop-search-input" autocomplete="off" data-search-input>
                    <button type="submit" class="shop-search-submit">
                        <span class="hidden sm:inline">{{ __('shop.search_btn') }}</span>
                        <x-shop.icon name="search" class="w-4 h-4 sm:hidden" />
                    </button>
                </form>
                <div id="search-suggest-desktop" class="search-suggest hidden" role="listbox"></div>
            </div>

            <div class="shop-header-toolbar">
                @auth
                    @if(!auth()->user()->is_admin)
                        <a href="{{ route('account.index') }}" class="shop-header-auth shop-header-auth--cta">{{ __('shop.my_account') }}</a>
                    @endif
                @endauth

                @auth
                    <span class="shop-header-toolbar__divider" aria-hidden="true"></span>
                @endauth

                <div class="shop-header-toolbar__pump">
                    @include('shop.partials.pump-selector-nav-cta')
                </div>

                <div class="shop-header-toolbar__icons">
                    @unless(auth()->check() && auth()->user()->is_admin)
                        @auth
                            <x-shop.header-action
                                icon="user"
                                :href="route('account.index')"
                                :aria-label="__('shop.account')"
                            />
                        @else
                            <x-shop.header-action
                                icon="user"
                                :href="route('login')"
                                :aria-label="__('shop.login_cta')"
                                data-open-auth-modal
                                data-auth-mode="login"
                            />
                        @endauth
                    @endunless

                    <x-shop.header-action icon="heart" :href="route('favorites.index')" aria-label="{{ __('shop.favorites') }}">
                        <span data-favorite-count class="shop-header-icon__badge {{ ($favoriteCount ?? 0) < 1 ? 'is-empty' : '' }}">{{ $favoriteCount ?? 0 }}</span>
                    </x-shop.header-action>

                    <x-shop.header-action icon="cart" emphasis data-open-cart-drawer aria-label="{{ __('shop.cart_open') }}">
                        <span data-cart-count class="shop-header-icon__badge {{ ($cartCount ?? 0) < 1 ? 'is-empty' : '' }}">{{ $cartCount ?? 0 }}</span>
                    </x-shop.header-action>
                </div>
            </div>
        </div>

        <div class="shop-site-header__search-mobile sm:hidden pb-3" id="search-autocomplete-mobile">
            <form action="{{ route('search') }}" method="get" role="search" class="shop-search-form">
                <label class="sr-only" for="header-search-mobile">{{ __('shop.search_label') }}</label>
                <span class="shop-search-form__icon" aria-hidden="true">
                    <x-shop.icon name="search" class="w-5 h-5" />
                </span>
                <input id="header-search-mobile" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('shop.search_placeholder') }}" class="shop-search-input text-base" data-search-input>
                <button type="submit" class="shop-search-submit">{{ __('shop.search_btn') }}</button>
            </form>
            <div id="search-suggest-mobile" class="search-suggest hidden" role="listbox"></div>
        </div>

        <nav class="shop-main-nav hidden lg:flex" aria-label="{{ __('shop.main_nav') }}" data-header-nav>
            <a href="{{ route('products.index') }}" class="shop-nav-link {{ request()->routeIs('products.index', 'search') ? 'shop-nav-link--active' : '' }}">
                {{ __('shop.all_products') }}
            </a>
            <div class="shop-mega-wrap">
                <a href="{{ route('categories.index') }}" class="shop-nav-link {{ request()->routeIs('categories.*') ? 'shop-nav-link--active' : '' }}">
                    {{ __('shop.categories') }}
                    <x-shop.icon name="chevron-down" class="w-4 h-4 shop-nav-link__chevron" />
                </a>
                @if(($menuCategories ?? collect())->isNotEmpty())
                    <div class="shop-mega-panel">
                        <div class="shop-mega-panel__columns">
                            @foreach($menuCategories as $cat)
                                <div class="shop-mega-panel__col">
                                    <a href="{{ $cat->storefrontUrl() }}" class="shop-mega-panel__parent">{{ $cat->name }}</a>
                                    @if($cat->activeChildren->isNotEmpty())
                                        <ul class="shop-mega-panel__children">
                                            @foreach($cat->activeChildren as $child)
                                                <li class="shop-mega-panel__child-item">
                                                    <a href="{{ $child->storefrontUrl() }}" class="shop-mega-panel__child {{ $child->activeChildren->isNotEmpty() ? 'shop-mega-panel__child--has-children' : '' }}">{{ $child->name }}</a>
                                                    @if($child->activeChildren->isNotEmpty())
                                                        <ul class="shop-mega-panel__grandchildren" aria-label="{{ $child->name }} alt kategorileri">
                                                            @foreach($child->activeChildren as $grandchild)
                                                                <li>
                                                                    <a href="{{ $grandchild->storefrontUrl() }}" class="shop-mega-panel__grandchild">{{ $grandchild->name }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('categories.index') }}" class="shop-mega-panel__all">
                            {{ __('shop.view_all_categories') }}
                            <x-shop.icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    </div>
                @endif
            </div>
            <a href="{{ route('brands.index') }}" class="shop-nav-link {{ request()->routeIs('brands.*') ? 'shop-nav-link--active' : '' }}">{{ __('shop.brands') }}</a>
            @foreach(($headerNavItems ?? []) as $link)
                <a href="{{ $link->url }}" class="shop-nav-link" @if($link->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
            @endforeach
        </nav>
    </div>
</header>
