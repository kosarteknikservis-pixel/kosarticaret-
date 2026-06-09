@extends('layouts.shop')

@section('title', $metaTitle)

@section('content')
    <div class="shop-page shop-page--pump-selector">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.pump_selector_page_title')],
        ]])

        <header class="shop-pump-page-hero shop-reveal">
            <div class="shop-pump-page-hero__inner">
                <p class="shop-pump-page-hero__eyebrow">{{ __('shop.pump_selector_eyebrow') }}</p>
                <h1 class="shop-pump-page-hero__title">{{ __('shop.pump_selector_title') }}</h1>
                <p class="shop-pump-page-hero__sub">{{ __('shop.pump_selector_sub') }}</p>
                <ul class="shop-pump-page-hero__badges" aria-label="{{ __('shop.pump_selector_highlights') }}">
                    <li>{{ __('shop.pump_selector_badge_steps') }}</li>
                    <li>{{ __('shop.pump_selector_badge_free') }}</li>
                    <li>{{ __('shop.pump_selector_badge_catalog') }}</li>
                </ul>
            </div>
        </header>

        <div class="shop-pump-page">
            <aside class="shop-pump-page__rail shop-reveal" aria-label="{{ __('shop.pump_selector_steps') }}">
                <ol class="shop-pump-page__progress">
                    @foreach([
                        __('shop.pump_selector_step_label_1'),
                        __('shop.pump_selector_step_label_2'),
                        __('shop.pump_selector_step_label_3'),
                    ] as $index => $label)
                        <li class="shop-pump-page__progress-item" data-pump-rail-step="{{ $index + 1 }}">
                            <span class="shop-pump-page__progress-num">{{ $index + 1 }}</span>
                            <span class="shop-pump-page__progress-label">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
                <ul class="shop-pump-page__trust">
                    <li>{{ __('shop.pump_selector_trust_1') }}</li>
                    <li>{{ __('shop.pump_selector_trust_2') }}</li>
                    <li>{{ __('shop.pump_selector_trust_3') }}</li>
                </ul>
            </aside>

            <div class="shop-pump-page__workspace">
                @include('shop.partials.pump-selector-widget', [
                    'preselectedApplication' => $preselectedApplication ?? null,
                ])
            </div>
        </div>
    </div>
@endsection
