@php
    $applications = \App\Support\PumpSelectorUiConfig::applications();
    $preselectedApplication = $preselectedApplication ?? null;
@endphp

<section class="shop-pump-selector shop-pump-selector--page"
         data-pump-selector
         data-pump-api="{{ route('pump-selector.recommend') }}"
         @if($preselectedApplication) data-pump-preselect="{{ $preselectedApplication }}" @endif
         aria-label="{{ __('shop.pump_selector_page_title') }}">
    <div class="shop-pump-selector__shell">
        <div class="shop-pump-selector__steps shop-pump-selector__steps--labeled" data-pump-steps aria-label="{{ __('shop.pump_selector_steps') }}">
            @foreach([
                __('shop.pump_selector_step_label_1'),
                __('shop.pump_selector_step_label_2'),
                __('shop.pump_selector_step_label_3'),
            ] as $index => $label)
                <div class="shop-pump-selector__step-wrap">
                    @if($index > 0)
                        <span class="shop-pump-selector__step-line" aria-hidden="true"></span>
                    @endif
                    <div class="shop-pump-selector__step-item">
                        <span class="shop-pump-selector__step {{ $index === 0 ? 'is-active' : '' }}" data-pump-step-indicator="{{ $index + 1 }}">{{ $index + 1 }}</span>
                        <span class="shop-pump-selector__step-text">{{ $label }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="shop-pump-selector__panel is-active" data-pump-panel="1">
            <p class="shop-pump-selector__lead">{{ __('shop.pump_selector_step1') }}</p>
            <div class="shop-pump-selector__apps" role="list">
                @foreach($applications as $app)
                    <button type="button"
                            class="shop-pump-selector__app"
                            data-pump-application="{{ $app['id'] }}"
                            role="listitem">
                        <span class="shop-pump-selector__app-icon" aria-hidden="true">
                            <x-shop.icon :name="$app['icon']" class="w-5 h-5" />
                        </span>
                        <span class="shop-pump-selector__app-label">{{ $app['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="shop-pump-selector__panel" data-pump-panel="2" hidden>
            <p class="shop-pump-selector__lead" data-pump-step2-title>{{ __('shop.pump_selector_step2') }}</p>
            <form class="shop-pump-selector__form" data-pump-form novalidate>
                <div class="shop-pump-selector__fields" data-pump-fields></div>
                <div class="shop-pump-selector__nav">
                    <button type="button" class="shop-pump-selector__btn shop-pump-selector__btn--ghost" data-pump-back>{{ __('shop.pump_selector_back') }}</button>
                    <button type="submit" class="shop-pump-selector__btn shop-pump-selector__btn--primary">{{ __('shop.pump_selector_calculate') }}</button>
                </div>
            </form>
        </div>

        <div class="shop-pump-selector__panel" data-pump-panel="3" hidden>
            <div class="shop-pump-selector__result-head">
                <div class="shop-pump-selector__requirements" data-pump-requirements aria-live="polite"></div>
                <button type="button" class="shop-pump-selector__btn shop-pump-selector__btn--ghost" data-pump-restart>{{ __('shop.pump_selector_restart') }}</button>
            </div>
            <ul class="shop-pump-selector__details" data-pump-details hidden></ul>
            <div class="shop-pump-selector__results" data-pump-results aria-live="polite"></div>
            <div class="shop-pump-selector__fallback" data-pump-empty hidden>
                <p>{{ __('shop.pump_selector_empty') }}</p>
                <a href="{{ route('contact.show') }}" class="shop-pump-selector__btn shop-pump-selector__btn--primary">{{ __('shop.footer_connect_cta') }}</a>
            </div>
            <div class="shop-pump-selector__nav shop-pump-selector__nav--end">
                <a href="{{ route('contact.show') }}" class="shop-pump-selector__link" data-pump-contact>{{ __('shop.pump_selector_expert') }}</a>
                <a href="#" class="shop-pump-selector__link" data-pump-category hidden>{{ __('shop.pump_selector_view_category') }}</a>
            </div>
        </div>

        <p class="shop-pump-selector__disclaimer">{{ __('shop.pump_selector_disclaimer') }}</p>
    </div>
</section>

<script type="application/json" id="pump-selector-i18n">{!! json_encode(\App\Support\PumpSelectorUiConfig::clientConfig(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
