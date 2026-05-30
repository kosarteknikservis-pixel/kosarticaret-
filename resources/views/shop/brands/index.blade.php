@extends('layouts.shop')
@section('title', __('shop.brands'))

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.brands')],
        ]])
        <x-shop.page-hero :title="__('shop.brands')" />
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 shop-reveal-group">
            @foreach($brands as $brand)
                <li>
                    <a href="{{ route('brands.show', $brand) }}" class="shop-brand-tile shop-brand-tile--logo flex flex-col items-center justify-center gap-3 min-h-[7rem]">
                        @include('shop.partials.brand-logo', ['brand' => $brand, 'class' => 'shop-brand-tile__logo max-h-12 w-auto object-contain', 'fallbackClass' => 'shop-brand-tile__name text-center'])
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
