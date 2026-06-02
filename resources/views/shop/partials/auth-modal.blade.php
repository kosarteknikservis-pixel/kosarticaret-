@guest
    @php
        $authMode = old('auth_mode') === 'register' ? 'register' : 'login';
        $shouldOpen = $errors->any() && in_array(old('auth_mode'), ['login', 'register'], true);
    @endphp

    <div id="shop-auth-modal"
         class="shop-auth-modal hidden"
         data-auth-default-mode="{{ $authMode }}"
         @if($shouldOpen) data-auth-open-on-load @endif
         aria-hidden="true">
        <div class="shop-auth-modal__backdrop" data-auth-close></div>

        <section class="shop-auth-modal__dialog"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="shop-auth-modal-title"
                 tabindex="-1">
            <button type="button" class="shop-auth-modal__close" data-auth-close aria-label="{{ __('shop.menu_close') }}">
                <x-shop.icon name="x" class="w-5 h-5" />
            </button>

            <div class="shop-auth-modal__brand">
                <x-shop.brand-lockup variant="header" />
            </div>

            <div class="shop-auth-modal__tabs" role="tablist" aria-label="Üyelik işlemleri">
                <button type="button" class="shop-auth-modal__tab" data-auth-switch="login" role="tab">{{ __('shop.login') }}</button>
                <button type="button" class="shop-auth-modal__tab" data-auth-switch="register" role="tab">{{ __('shop.register') }}</button>
            </div>

            <div class="shop-auth-modal__panel" data-auth-panel="login">
                <div class="text-center mb-6">
                    <h2 id="shop-auth-modal-title" class="shop-auth-modal__title">{{ __('shop.login') }}</h2>
                    <p class="shop-auth-modal__subtitle">{{ __('shop.login_sub') }}</p>
                </div>
                @include('shop.auth.partials.login-form', ['prefix' => 'modal-login', 'modalSwitch' => true])
            </div>

            <div class="shop-auth-modal__panel hidden" data-auth-panel="register">
                <div class="text-center mb-6">
                    <h2 class="shop-auth-modal__title">{{ __('shop.register') }}</h2>
                    <p class="shop-auth-modal__subtitle">{{ __('shop.register_sub') }}</p>
                </div>
                @include('shop.auth.partials.register-form', ['prefix' => 'modal-register', 'modalSwitch' => true])
            </div>
        </section>
    </div>
@endguest
