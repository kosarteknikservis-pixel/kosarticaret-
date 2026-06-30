@extends('layouts.shop')
@section('title', __('shop.tracking'))

@section('content')
    <div class="shop-page shop-page--tracking">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.tracking')],
        ]])

        <x-shop.page-hero :title="__('shop.tracking')" :subtitle="__('shop.tracking_sub')" />

        <div class="shop-tracking-layout">
            <section class="shop-tracking-layout__main" aria-labelledby="tracking-form-heading">
                <form method="post" action="{{ route('tracking.lookup') }}" class="shop-panel shop-tracking-form">
                    @csrf
                    <h2 id="tracking-form-heading" class="shop-panel__title">{{ __('shop.tracking_form_title') }}</h2>
                    <p class="shop-tracking-form__hint">{{ __('shop.tracking_sub') }}</p>
                    <div class="shop-tracking-form__fields space-y-4">
                        <div>
                            <label class="shop-label" for="tracking-order-number">{{ __('shop.order_number') }}</label>
                            <input
                                id="tracking-order-number"
                                name="order_number"
                                value="{{ old('order_number') }}"
                                required
                                placeholder="KOS-..."
                                class="shop-input mt-1 font-mono"
                                autocomplete="off"
                            >
                        </div>
                        <div>
                            <label class="shop-label" for="tracking-email">E-posta</label>
                            <input
                                id="tracking-email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                class="shop-input mt-1"
                                autocomplete="email"
                            >
                        </div>
                    </div>
                    <button type="submit" class="btn-primary w-full py-3.5 mt-6">
                        <x-shop.icon name="search" class="w-5 h-5 inline-block mr-1 -mt-0.5" />
                        {{ __('shop.track_order') }}
                    </button>
                </form>

                @if(!empty($searched))
                    <div class="shop-tracking-result" aria-live="polite">
                        @if($order)
                            <article class="shop-panel shop-tracking-order">
                                <header class="shop-tracking-order__head">
                                    <div>
                                        <p class="shop-tracking-order__label">{{ __('shop.tracking_result_title') }}</p>
                                        <h2 class="shop-tracking-order__number">{{ $order->order_number }}</h2>
                                        <p class="shop-tracking-order__date">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                    <span class="shop-status-badge shop-tracking-order__status">
                                        {{ \App\Support\OrderStatus::label($order->status) }}
                                    </span>
                                </header>

                                @php
                                    $timelineSteps = [
                                        'odeme_bekliyor' => ['icon' => 'credit-card', 'label' => \App\Support\OrderStatus::label('odeme_bekliyor')],
                                        'hazirlaniyor' => ['icon' => 'grid', 'label' => \App\Support\OrderStatus::label('hazirlaniyor')],
                                        'kargoda' => ['icon' => 'truck', 'label' => \App\Support\OrderStatus::label('kargoda')],
                                        'teslim_edildi' => ['icon' => 'shield', 'label' => \App\Support\OrderStatus::label('teslim_edildi')],
                                    ];
                                    $statusOrder = array_keys($timelineSteps);
                                    $currentIndex = array_search($order->status, $statusOrder, true);
                                    if ($currentIndex === false) {
                                        $currentIndex = 0;
                                    }
                                @endphp

                                <div class="shop-tracking-timeline" aria-label="{{ __('shop.tracking_timeline_title') }}">
                                    <p class="shop-tracking-timeline__title">{{ __('shop.tracking_timeline_title') }}</p>
                                    <ol class="shop-tracking-timeline__list">
                                        @foreach($timelineSteps as $key => $step)
                                            @php
                                                $stepIndex = array_search($key, $statusOrder, true);
                                                $isDone = $stepIndex !== false && $stepIndex <= $currentIndex && $order->status !== 'iptal';
                                                $isCurrent = $key === $order->status;
                                            @endphp
                                            <li class="shop-tracking-timeline__item {{ $isDone ? 'is-done' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
                                                <span class="shop-tracking-timeline__dot" aria-hidden="true">
                                                    <x-shop.icon :name="$step['icon']" class="w-4 h-4" />
                                                </span>
                                                <span class="shop-tracking-timeline__label">{{ $step['label'] }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                    @if($order->status === 'iptal')
                                        <p class="shop-tracking-timeline__cancelled">{{ \App\Support\OrderStatus::label('iptal') }}</p>
                                    @endif
                                </div>

                                <dl class="shop-tracking-order__stats">
                                    <div class="shop-tracking-stat shop-tracking-stat--accent">
                                        <dt>{{ __('shop.payment') }}</dt>
                                        <dd>{{ $order->payment_status === 'basarili' ? __('shop.paid') : $order->payment_status }}</dd>
                                    </div>
                                    <div class="shop-tracking-stat">
                                        <dt>{{ __('shop.total_est') }}</dt>
                                        <dd>{{ number_format($order->total, 2, ',', '.') }} ₺</dd>
                                    </div>
                                    @if($order->shipping_tracking)
                                        <div class="shop-tracking-stat shop-tracking-stat--wide shop-tracking-stat--accent">
                                            <dt>{{ __('shop.tracking_number') }}</dt>
                                            <dd class="font-mono">{{ $order->shipping_tracking }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                <div class="shop-tracking-order__items">
                                    <h3 class="shop-section-title !mb-3">{{ __('shop.tracking_items_title') }}</h3>
                                    <x-shop.order-items-list :items="$order->items" class="shop-order-items--tracking" />
                                </div>
                            </article>
                        @else
                            <x-shop.empty-state
                                icon="search"
                                :title="__('shop.order_not_found')"
                                class="shop-tracking-not-found"
                            >
                                <x-slot:action>
                                    <a href="{{ route('contact.show') }}" class="btn-outline">{{ __('shop.contact') }}</a>
                                </x-slot:action>
                            </x-shop.empty-state>
                        @endif
                    </div>
                @endif
            </section>

            <aside class="shop-tracking-layout__aside" aria-labelledby="tracking-help-heading">
                <div class="shop-panel shop-tracking-help">
                    <h2 id="tracking-help-heading" class="shop-panel__title">{{ __('shop.tracking_help_title') }}</h2>
                    <ol class="shop-tracking-help__list">
                        <li class="shop-tracking-help__item">
                            <span class="shop-tracking-help__num">1</span>
                            <span>{{ __('shop.tracking_help_1') }}</span>
                        </li>
                        <li class="shop-tracking-help__item">
                            <span class="shop-tracking-help__num">2</span>
                            <span>{{ __('shop.tracking_help_2') }}</span>
                        </li>
                        <li class="shop-tracking-help__item">
                            <span class="shop-tracking-help__num">3</span>
                            <span>{{ __('shop.tracking_help_3') }}</span>
                        </li>
                    </ol>
                </div>
                <div class="shop-panel shop-tracking-aside-cta">
                    <x-shop.icon name="truck" class="w-8 h-8 text-brand-600" />
                    <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ __('shop.tracking_sub') }}</p>
                    <a href="{{ route('contact.show') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800">
                        {{ __('shop.contact') }}
                        <x-shop.icon name="chevron-right" class="w-4 h-4" />
                    </a>
                </div>
            </aside>
        </div>
    </div>
@endsection
