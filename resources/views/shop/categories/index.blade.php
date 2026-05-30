@extends('layouts.shop')
@section('title', __('shop.categories'))

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.categories')],
        ]])
        <x-shop.page-hero :title="__('shop.categories')" :subtitle="__('shop.home_categories_sub')" />
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 shop-reveal-group">
            @foreach($categories as $cat)
                @include('shop.partials.category-card', ['category' => $cat])
            @endforeach
        </div>
    </div>
@endsection
