@extends('layouts.shop')
@section('title', __('shop.step_done'))

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.step_done')],
        ]])
        @include('shop.partials.checkout-steps', ['step' => 3])

        <x-shop.page-hero :title="__('shop.order_success_title')" :subtitle="__('shop.order_success_sub')" class="!mb-8" />

        <div class="shop-success-wrap">
            <div class="shop-success-icon mx-auto" aria-hidden="true">
                <x-shop.icon name="shield" class="w-10 h-10" />
            </div>

            <div class="shop-panel mt-8 text-left max-w-2xl mx-auto">
                <h2 class="shop-panel__title">{{ __('shop.order_summary') }}</h2>
                <dl class="shop-tracking-order__stats mt-4 !mb-0">
                    <div class="shop-tracking-stat shop-tracking-stat--accent">
                        <dt>{{ __('shop.order_number') }}</dt>
                        <dd>{{ $order->order_number }}</dd>
                    </div>
                    <div class="shop-tracking-stat">
                        <dt>E-posta</dt>
                        <dd class="!font-medium">{{ $order->email }}</dd>
                    </div>
                    <div class="shop-tracking-stat shop-tracking-stat--accent">
                        <dt>{{ __('shop.total_est') }}</dt>
                        <dd>{{ number_format($order->total, 2, ',', '.') }} ₺</dd>
                    </div>
                    <div class="shop-tracking-stat">
                        <dt>{{ __('shop.status') }}</dt>
                        <dd class="!font-medium">{{ \App\Support\OrderStatus::label($order->status) }}</dd>
                    </div>
                </dl>
                @if($order->payment_method === 'havale')
                    <p class="mt-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 p-4 text-sm leading-relaxed">
                        {{ __('shop.bank_transfer_note') }}
                    </p>
                @endif
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('tracking.show') }}" class="btn-outline px-6 py-3">{{ __('shop.tracking') }}</a>
                @auth
                    @if(!auth()->user()->is_admin)
                        <a href="{{ route('account.index') }}" class="btn-primary px-6 py-3">{{ __('shop.account') }}</a>
                    @endif
                @endauth
                <a href="{{ route('home') }}" class="btn-outline px-6 py-3">{{ __('shop.home') }}</a>
            </div>
        </div>
    </div>

    @php
        $gaItems = $order->items->map(fn ($item) => [
            'item_id' => $item->sku ?: 'KOS-'.$item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
        ])->values()->all();
    @endphp
    @include('shop.partials.ga4-ecommerce', [
        'ga4Payload' => [
            'event' => 'purchase',
            'params' => [
                'transaction_id' => $order->order_number,
                'currency' => 'TRY',
                'value' => (float) $order->total,
                'items' => $gaItems,
            ],
        ],
    ])
@endsection
