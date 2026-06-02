@extends('layouts.shop')
@section('title', __('shop.login'))

@section('content')
    <div class="shop-page shop-page--narrow">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.login')],
        ]])
        <div class="shop-auth-card">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">{{ __('shop.login') }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ __('shop.login_sub') }}</p>
            </div>
            @include('shop.auth.partials.login-form', ['prefix' => 'page-login', 'showRegisterLink' => true])
        </div>
    </div>
@endsection
