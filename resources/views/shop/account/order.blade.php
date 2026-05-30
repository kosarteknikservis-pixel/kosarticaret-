@extends('layouts.shop')
@section('title', $order->order_number)

@section('content')
    <div class="shop-page">
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

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <section class="shop-panel overflow-hidden !p-0">
                    <h2 class="px-5 py-4 font-bold border-b border-slate-100 bg-gradient-to-r from-brand-50/80 to-white text-slate-900">{{ __('shop.order_items') }}</h2>
                    <ul class="divide-y divide-slate-100">
                        @foreach($order->items as $item)
                            <li class="px-5 py-4 flex justify-between gap-4 text-sm">
                                <span class="font-medium text-slate-800">{{ $item->product_name }} <span class="text-slate-500">× {{ $item->quantity }}</span></span>
                                <span class="font-bold shrink-0 text-brand-700">{{ number_format($item->line_total, 2, ',', '.') }} ₺</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>
            <aside class="space-y-4">
                <div class="shop-panel shop-panel--sticky">
                    <h2 class="shop-panel__title">{{ __('shop.order_summary') }}</h2>
                    <p class="mt-4 text-2xl font-extrabold text-brand-700 tracking-tight">{{ number_format($order->total, 2, ',', '.') }} ₺</p>
                    @if($order->shipping_tracking)
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide">{{ __('shop.tracking_number') }}</p>
                            <p class="mt-1 font-mono font-bold text-slate-900">{{ $order->shipping_tracking }}</p>
                        </div>
                    @endif
                </div>
                <a href="{{ route('account.index') }}" class="block text-center text-sm font-semibold text-brand-700 hover:text-brand-800">← {{ __('shop.back_to_orders') }}</a>
            </aside>
        </div>
    </div>
@endsection
