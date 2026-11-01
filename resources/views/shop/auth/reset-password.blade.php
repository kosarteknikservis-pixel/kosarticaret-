@extends('layouts.shop')
@section('title', 'Yeni şifre belirle')

@section('content')
    <div class="shop-page shop-page--narrow">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => 'Yeni şifre'],
        ]])
        <div class="shop-auth-card">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Yeni şifre belirle</h1>
                <p class="text-sm text-slate-500 mt-1">En az 8 karakterli yeni şifrenizi girin.</p>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="reset-email" class="shop-label">E-posta</label>
                    <input id="reset-email" type="email" name="email" value="{{ old('email', $email) }}" required class="shop-input mt-1" autocomplete="email">
                    @error('email')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="reset-password" class="shop-label">Yeni şifre</label>
                    <input id="reset-password" type="password" name="password" required class="shop-input mt-1" autocomplete="new-password">
                    @error('password')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="reset-password-confirm" class="shop-label">Yeni şifre (tekrar)</label>
                    <input id="reset-password-confirm" type="password" name="password_confirmation" required class="shop-input mt-1" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-primary w-full py-3">Şifreyi güncelle</button>
            </form>
        </div>
    </div>
@endsection
