@extends('layouts.shop')
@section('title', 'Profilim')

@section('content')
    <div class="shop-page shop-page--account">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.account'), 'url' => route('account.index')],
            ['name' => 'Profilim'],
        ]])

        <x-shop.page-hero title="Profilim" subtitle="İletişim ve teslimat bilgilerinizi güncelleyin." />

        @if(session('success'))
            <p class="shop-alert shop-alert--success mb-4">{{ session('success') }}</p>
        @endif

        <form method="post" action="{{ route('account.profile.update') }}" class="shop-auth-card max-w-2xl space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="shop-label">Ad soyad</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="shop-input mt-1">
                    @error('name')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="shop-label">E-posta</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="shop-input mt-1">
                    @error('email')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="shop-label">Telefon</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" class="shop-input mt-1" autocomplete="tel">
                    @error('phone')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="border-t border-slate-100 pt-5 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Varsayılan teslimat adresi</h2>
                <div>
                    <label for="adres" class="shop-label">Adres</label>
                    <textarea id="adres" name="adres" rows="3" class="shop-input mt-1">{{ old('adres', $address['adres'] ?? '') }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="il" class="shop-label">İl</label>
                        <input id="il" name="il" type="text" value="{{ old('il', $address['il'] ?? '') }}" class="shop-input mt-1">
                    </div>
                    <div>
                        <label for="ilce" class="shop-label">İlçe</label>
                        <input id="ilce" name="ilce" type="text" value="{{ old('ilce', $address['ilce'] ?? '') }}" class="shop-input mt-1">
                    </div>
                    <div>
                        <label for="posta_kodu" class="shop-label">Posta kodu</label>
                        <input id="posta_kodu" name="posta_kodu" type="text" value="{{ old('posta_kodu', $address['posta_kodu'] ?? '') }}" class="shop-input mt-1">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-5 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Şifre değiştir</h2>
                <p class="text-sm text-slate-500">Boş bırakırsanız şifre değişmez.</p>
                <div>
                    <label for="current_password" class="shop-label">Mevcut şifre</label>
                    <input id="current_password" name="current_password" type="password" class="shop-input mt-1" autocomplete="current-password">
                    @error('current_password')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="shop-label">Yeni şifre</label>
                        <input id="password" name="password" type="password" class="shop-input mt-1" autocomplete="new-password">
                        @error('password')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="shop-label">Yeni şifre (tekrar)</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="shop-input mt-1" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="btn-primary py-3 px-6">Kaydet</button>
                <a href="{{ route('account.index') }}" class="btn-outline py-3 px-6">Siparişlerime dön</a>
            </div>
        </form>
    </div>
@endsection
