<div id="cart-drawer" class="fixed inset-0 z-50 hidden" aria-hidden="true" role="dialog" aria-label="{{ __('shop.cart') }}">
    <div class="shop-cart-drawer__overlay absolute inset-0" data-cart-drawer-close></div>
    <aside class="shop-cart-drawer__panel absolute right-0 top-0 h-full w-full max-w-md bg-white flex flex-col">
        <div class="shop-cart-drawer__head flex items-center justify-between">
            <h2 class="font-bold text-lg text-slate-900 flex items-center gap-2">
                <x-shop.icon name="cart" class="w-6 h-6 text-brand-600" />
                {{ __('shop.cart_drawer_title') }}
            </h2>
            <button type="button" data-cart-drawer-close class="p-2 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="{{ __('shop.menu_close') }}">
                <x-shop.icon name="x" class="w-6 h-6" />
            </button>
        </div>
        <div id="cart-drawer-body" class="flex-1 overflow-y-auto px-5 py-2 text-sm text-slate-500">
            <p class="py-8 text-center">{{ __('shop.cart_loading') }}</p>
        </div>
        <div class="shop-cart-drawer__foot space-y-3">
            <p class="flex justify-between items-center">
                <span class="text-slate-600">{{ __('shop.subtotal') }}</span>
                <span id="cart-drawer-subtotal" class="font-bold text-lg text-brand-700">—</span>
            </p>
            <a href="{{ route('cart.index') }}" class="shop-cart-drawer__secondary">
                {{ __('shop.cart_view_full') }}
            </a>
            <a href="{{ route('checkout.show') }}" id="cart-drawer-checkout" class="btn-primary w-full py-3">
                {{ __('shop.checkout_cta') }}
            </a>
        </div>
    </aside>
</div>
