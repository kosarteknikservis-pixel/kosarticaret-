@extends('layouts.shop')
@section('title', __('shop.favorites'))

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.favorites')],
        ]])

        <x-shop.page-hero
            :title="__('shop.favorites')"
            :subtitle="!$products->isEmpty() ? $products->count().' '.__('shop.results_count') : null"
        />

        @if($products->isEmpty())
            <x-shop.empty-state
                icon="heart"
                :title="__('shop.favorites_empty')"
                class="max-w-lg mx-auto"
            >
                <x-slot:action>
                    <a href="{{ route('products.index') }}" class="btn-primary">{{ __('shop.browse_all') }}</a>
                </x-slot:action>
            </x-shop.empty-state>
        @else
            <div class="grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 shop-reveal-group">
                @foreach($products as $product)
                    @include('shop.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </div>
@endsection
