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
            <form method="post" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div><label class="shop-label">E-posta</label><input type="email" name="email" value="{{ old('email') }}" required class="shop-input mt-1" autocomplete="email"></div>
                <div><label class="shop-label">{{ __('shop.password') }}</label><input type="password" name="password" required class="shop-input mt-1" autocomplete="current-password"></div>
                <label class="flex gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded text-brand-700 focus:ring-brand-500/30"> {{ __('shop.remember_me') }}</label>
                <button type="submit" class="btn-primary w-full py-3">{{ __('shop.login') }}</button>
            </form>
            <p class="mt-6 text-center text-sm text-slate-500">{{ __('shop.no_account') }} <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('shop.register') }}</a></p>
        </div>
    </div>
@endsection
