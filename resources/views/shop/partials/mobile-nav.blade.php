<div id="mobile-nav-overlay" class="shop-mobile-nav fixed inset-0 z-50 hidden lg:hidden" aria-hidden="true">
    <div id="mobile-nav-panel" class="shop-mobile-nav__panel absolute left-0 top-0 h-full w-[min(100%,20rem)] bg-white shadow-2xl flex flex-col" role="dialog" aria-modal="true" aria-label="{{ __('shop.menu_open') }}">
        <div class="shop-mobile-nav__head flex items-center justify-between gap-3">
            <x-shop.brand-lockup variant="mobile" class="min-w-0 flex-1" />
            <button type="button" id="mobile-menu-close" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="{{ __('shop.menu_close') }}">
                <x-shop.icon name="x" class="w-6 h-6" />
            </button>
        </div>

        <nav class="shop-mobile-nav__body" aria-label="{{ __('shop.main_nav') }}">
            <a href="{{ route('home') }}" class="shop-mobile-nav__link">
                <x-shop.icon name="grid" class="w-5 h-5 text-brand-600 shrink-0" />
                {{ __('shop.home') }}
            </a>
            <a href="{{ route('products.index') }}" class="shop-mobile-nav__link">{{ __('shop.all_products') }}</a>

            <p class="shop-mobile-nav__section">{{ __('shop.categories') }}</p>
            @foreach(($menuCategories ?? []) as $cat)
                <details class="shop-mobile-nav__category">
                    <summary class="shop-mobile-nav__category-summary">
                        <span>{{ $cat->name }}</span>
                        <x-shop.icon name="chevron-down" class="shop-mobile-nav__category-icon" />
                    </summary>
                    <div class="shop-mobile-nav__category-panel">
                        <a href="{{ $cat->storefrontUrl() }}" class="shop-mobile-nav__category-all">Tümünü Gör</a>
                        @foreach($cat->activeChildren as $child)
                            @if($child->activeChildren->isNotEmpty())
                                <details class="shop-mobile-nav__subcategory">
                                    <summary class="shop-mobile-nav__subcategory-summary">
                                        <span>{{ $child->name }}</span>
                                        <x-shop.icon name="chevron-down" class="shop-mobile-nav__subcategory-icon" />
                                    </summary>
                                    <div class="shop-mobile-nav__subcategory-panel">
                                        <a href="{{ $child->storefrontUrl() }}" class="shop-mobile-nav__link--child shop-mobile-nav__link--child-all">Tüm {{ $child->name }}</a>
                                        @foreach($child->activeChildren as $grandchild)
                                            <a href="{{ $grandchild->storefrontUrl() }}" class="shop-mobile-nav__link--grandchild">
                                                {{ $grandchild->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a href="{{ $child->storefrontUrl() }}" class="shop-mobile-nav__link--child">
                                    {{ $child->name }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </details>
            @endforeach
            <a href="{{ route('categories.index') }}" class="block py-2 text-sm font-semibold text-brand-700">{{ __('shop.view_all_categories') }}</a>

            <p class="shop-mobile-nav__section">{{ __('shop.menu_more') }}</p>
            <a href="{{ route('brands.index') }}" class="shop-mobile-nav__link--sub">{{ __('shop.brands') }}</a>
            <a href="{{ route('blog.index') }}" class="shop-mobile-nav__link--sub">{{ __('shop.blog') }}</a>
            <a href="{{ route('tracking.show') }}" class="shop-mobile-nav__link--sub">{{ __('shop.tracking') }}</a>
            <a href="{{ route('contact.show') }}" class="shop-mobile-nav__link--sub">{{ __('shop.contact') }}</a>
            @foreach(($headerNavItems ?? []) as $link)
                <a href="{{ $link->url }}" class="shop-mobile-nav__link--sub" @if($link->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
            @endforeach

            @if(count(config('kosar.locales', ['tr'])) > 1)
                <div class="mt-6 pt-4 border-t border-slate-100 flex gap-2">
                    @foreach(config('kosar.locales', ['tr']) as $locale)
                        <a href="{{ route('locale.switch', $locale) }}" class="shop-mobile-nav__locale {{ app()->getLocale() === $locale ? 'is-active' : '' }}">{{ strtoupper($locale) }}</a>
                    @endforeach
                </div>
            @endif
        </nav>

        <div class="shop-mobile-nav__foot space-y-2">
            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}" class="btn-primary w-full text-center">{{ __('shop.admin_panel') }}</a>
                @else
                    <a href="{{ route('account.index') }}" class="btn-outline w-full text-center">{{ __('shop.account') }}</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="btn-outline w-full text-center" data-open-auth-modal data-auth-mode="login">{{ __('shop.login_cta') }}</a>
                <a href="{{ route('register') }}" class="btn-primary w-full text-center" data-open-auth-modal data-auth-mode="register">{{ __('shop.register') }}</a>
            @endauth
        </div>
    </div>
</div>
