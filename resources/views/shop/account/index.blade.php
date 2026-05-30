@extends('layouts.shop')
@section('title', __('shop.account'))

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.account')],
        ]])

        <x-shop.page-hero :title="__('shop.hello', ['name' => $user->name])" :subtitle="$user->email">
            <x-slot:actions>
                <form method="post" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="btn-outline text-sm py-2.5 px-4">{{ __('shop.logout') }}</button>
                </form>
            </x-slot:actions>
        </x-shop.page-hero>

        <div class="grid gap-4 sm:grid-cols-3">
            <a href="{{ route('account.index') }}" class="shop-account-card is-active">
                <span class="shop-account-card__icon-wrap">
                    <x-shop.icon name="cart" class="w-6 h-6" />
                </span>
                <p class="mt-3 font-bold text-slate-900">{{ __('shop.my_orders') }}</p>
                <p class="text-sm text-slate-500">{{ $orders->total() }} {{ __('shop.orders_count') }}</p>
            </a>
            <a href="{{ route('favorites.index') }}" class="shop-account-card">
                <span class="shop-account-card__icon-wrap">
                    <x-shop.icon name="heart" class="w-6 h-6" />
                </span>
                <p class="mt-3 font-bold text-slate-900">{{ __('shop.favorites') }}</p>
            </a>
            <a href="{{ route('tracking.show') }}" class="shop-account-card">
                <span class="shop-account-card__icon-wrap">
                    <x-shop.icon name="truck" class="w-6 h-6" />
                </span>
                <p class="mt-3 font-bold text-slate-900">{{ __('shop.tracking') }}</p>
            </a>
        </div>

        <section class="mt-10" aria-labelledby="orders-heading">
            <h2 id="orders-heading" class="shop-section-title">{{ __('shop.my_orders') }}</h2>
            @if($orders->isEmpty())
                <x-shop.empty-state :title="__('shop.no_orders')" class="max-w-lg">
                    <x-slot:action>
                        <a href="{{ route('products.index') }}" class="btn-primary">{{ __('shop.hero_cta_shop') }}</a>
                    </x-slot:action>
                </x-shop.empty-state>
            @else
                <ul class="space-y-3">
                    @foreach($orders as $order)
                        <li>
                            <a href="{{ route('account.order', $order->order_number) }}" class="shop-order-row group">
                                <div>
                                    <p class="font-bold text-brand-700 group-hover:text-brand-800">{{ $order->order_number }}</p>
                                    <p class="text-sm text-slate-500 mt-1">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                                    <span class="shop-status-badge mt-2">{{ \App\Support\OrderStatus::label($order->status) }}</span>
                                </div>
                                <p class="text-xl font-bold text-slate-900">{{ number_format($order->total, 2, ',', '.') }} ₺</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6 shop-pagination">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>
@endsection
