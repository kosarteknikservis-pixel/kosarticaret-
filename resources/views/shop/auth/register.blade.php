@extends('layouts.shop')
@section('title', __('shop.register'))

@section('content')
    <div class="shop-page shop-page--narrow">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.register')],
        ]])
        <div class="shop-auth-card">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">{{ __('shop.register') }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ __('shop.register_sub') }}</p>
            </div>
            @include('shop.auth.partials.register-form', ['prefix' => 'page-register', 'showLoginLink' => true])
        </div>
    </div>
@endsection
