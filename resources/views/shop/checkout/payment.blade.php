@extends('layouts.shop')

@section('title', __('shop.secure_payment'))



@section('content')

    <div class="shop-page shop-page--narrow">

        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [

            ['name' => __('shop.home'), 'url' => route('home')],

            ['name' => __('shop.cart'), 'url' => route('cart.index')],

            ['name' => __('shop.secure_payment')],

        ]])

        @include('shop.partials.checkout-steps', ['step' => 2])



        <x-shop.page-hero

            :title="__('shop.secure_payment')"

            :subtitle="$isDemo ? __('shop.payment_demo_note') : __('shop.payment_gateway_note', ['gateway' => $gatewayLabel])"

            class="!mb-6"

        />



        @if(session('error'))

            <p class="shop-alert shop-alert--error mb-4">{{ session('error') }}</p>

        @endif

        @if(request('durum') === 'hata')

            <p class="shop-alert shop-alert--error mb-4">{{ __('shop.payment_failed') }}</p>

        @endif



        <div class="shop-payment-card shop-panel py-10 px-6 sm:px-10">

            <x-shop.icon name="credit-card" class="w-12 h-12 text-brand-600 mx-auto" />

            <p class="mt-4 text-slate-600">{{ __('shop.order_number') }}: <strong class="text-brand-700">{{ $order->order_number }}</strong></p>

            <p class="mt-6 text-3xl font-extrabold text-brand-700 tracking-tight">{{ number_format($order->total, 2, ',', '.') }} ₺</p>

            @include('shop.partials.pdp-trust')



            @if($retryUrl)

                <a href="{{ $retryUrl }}" class="btn-primary shop-btn-premium w-full py-3.5 mt-8 inline-block text-center">

                    {{ __('shop.retry_payment', ['gateway' => $gatewayLabel]) }}

                </a>

            @elseif($isDemo)

                <form method="post" action="{{ route('checkout.payment.complete', $order->order_number) }}" class="mt-8">

                    @csrf

                    <input type="hidden" name="demo" value="1">

                    <button type="submit" class="btn-primary shop-btn-premium w-full py-3.5">{{ __('shop.complete_demo_payment') }}</button>

                </form>

            @else

                <p class="mt-6 text-sm text-slate-500 text-center">{{ __('shop.payment_contact_support') }}</p>

            @endif

        </div>

    </div>

@endsection

