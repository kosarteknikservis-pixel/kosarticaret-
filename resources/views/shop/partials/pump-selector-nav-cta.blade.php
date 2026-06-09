@if(\App\Support\PumpSelectorUiConfig::isEnabled() && ! request()->routeIs('pump-selector.*'))
    <a href="{{ route('pump-selector.show') }}"
       class="shop-pump-cta shop-pump-cta--pill"
       aria-label="{{ __('shop.pump_selector_program') }}">
        <span class="shop-pump-cta__badge" aria-hidden="true">
            <x-shop.icon name="pump" class="w-4 h-4" />
        </span>
        <span class="shop-pump-cta__title">{{ __('shop.pump_selector_program') }}</span>
        <x-shop.icon name="chevron-right" class="shop-pump-cta__arrow w-3.5 h-3.5" />
    </a>
@endif
