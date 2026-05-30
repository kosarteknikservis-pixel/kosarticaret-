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
            <form method="post" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div><label class="shop-label">{{ __('shop.full_name') }}</label><input name="name" value="{{ old('name') }}" required class="shop-input mt-1" autocomplete="name"></div>
                <div><label class="shop-label">E-posta</label><input type="email" name="email" value="{{ old('email') }}" required class="shop-input mt-1" autocomplete="email"></div>
                <div><label class="shop-label">{{ __('shop.password') }}</label><input type="password" name="password" required minlength="8" class="shop-input mt-1" autocomplete="new-password"></div>
                <div><label class="shop-label">{{ __('shop.password_confirm') }}</label><input type="password" name="password_confirmation" required class="shop-input mt-1" autocomplete="new-password"></div>
                <button type="submit" class="btn-primary w-full py-3">{{ __('shop.register') }}</button>
            </form>
            <p class="mt-6 text-center text-sm text-slate-500">{{ __('shop.have_account') }} <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('shop.login') }}</a></p>
        </div>
    </div>
@endsection
