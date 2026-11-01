@extends('layouts.shop')
@section('title', 'Şifremi unuttum')

@section('content')
    <div class="shop-page shop-page--narrow">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.login'), 'url' => route('login')],
            ['name' => 'Şifremi unuttum'],
        ]])
        <div class="shop-auth-card">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Şifremi unuttum</h1>
                <p class="text-sm text-slate-500 mt-1">Kayıtlı e-posta adresinize sıfırlama bağlantısı gönderilir.</p>
            </div>

            @if(session('success'))
                <p class="shop-alert shop-alert--success mb-4">{{ session('success') }}</p>
            @endif

            <form method="post" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="forgot-email" class="shop-label">E-posta</label>
                    <input id="forgot-email" type="email" name="email" value="{{ old('email') }}" required class="shop-input mt-1" autocomplete="email">
                    @error('email')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary w-full py-3">Bağlantı gönder</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">Girişe dön</a>
            </p>
        </div>
    </div>
@endsection
