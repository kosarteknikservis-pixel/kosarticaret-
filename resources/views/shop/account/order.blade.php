@extends('layouts.shop')
@section('title', $order->order_number)

@section('content')
    <div class="shop-page shop-page--account-order">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.account'), 'url' => route('account.index')],
            ['name' => $order->order_number],
        ]])

        <x-shop.page-hero :title="$order->order_number" :subtitle="$order->created_at->format('d.m.Y H:i')">
            <x-slot:actions>
                <span class="shop-status-badge text-sm px-4 py-2 text-brand-800 border-brand-200 bg-brand-50">
                    {{ \App\Support\OrderStatus::label($order->status) }}
                </span>
            </x-slot:actions>
        </x-shop.page-hero>

        <div class="shop-account-order-grid">
            <section class="shop-panel shop-panel--flush min-w-0">
                <div class="shop-panel__header-row">
                    <h2 class="shop-panel__title !mb-0 !pb-0 !border-0">{{ __('shop.order_items') }}</h2>
                    <span class="shop-order-items-count">{{ $order->items->count() }} kalem</span>
                </div>
                <x-shop.order-items-list :items="$order->items" :show-unit-price="true" />
            </section>

            <aside class="shop-account-order-aside">
                <div class="shop-panel shop-panel--sticky shop-panel--summary min-w-0">
                    <h2 class="shop-panel__title">{{ __('shop.order_summary') }}</h2>
                    <dl class="shop-order-summary-stats">
                        <div class="shop-order-summary-stats__row">
                            <dt>Ara toplam</dt>
                            <dd>{{ number_format($order->subtotal, 2, ',', '.') }} ₺</dd>
                        </div>
                        @if((float) $order->shipping_cost > 0)
                            <div class="shop-order-summary-stats__row">
                                <dt>Kargo</dt>
                                <dd>{{ number_format($order->shipping_cost, 2, ',', '.') }} ₺</dd>
                            </div>
                        @endif
                        @if((float) $order->discount > 0)
                            <div class="shop-order-summary-stats__row">
                                <dt>İndirim</dt>
                                <dd>-{{ number_format($order->discount, 2, ',', '.') }} ₺</dd>
                            </div>
                        @endif
                    </dl>
                    <p class="shop-order-summary-total">{{ number_format($order->total, 2, ',', '.') }} ₺</p>
                    @if($order->shipping_tracking)
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide">{{ __('shop.tracking_number') }}</p>
                            <p class="mt-1 font-mono font-bold text-slate-900 break-all">{{ $order->shipping_tracking }}</p>
                        </div>
                    @endif
                </div>
                <a href="{{ route('account.index') }}" class="shop-account-back-link">← {{ __('shop.back_to_orders') }}</a>
            </aside>
        </div>
    </div>
@endsection
