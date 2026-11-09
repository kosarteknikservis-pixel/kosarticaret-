@extends('layouts.shop')
@section('title', 'E-posta doğrulama')

@section('content')
    <div class="shop-page shop-page--narrow">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.account'), 'url' => route('account.index')],
            ['name' => 'E-posta doğrulama'],
        ]])
        <div class="shop-auth-card">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">E-posta doğrulama</h1>
                <p class="text-sm text-slate-500 mt-1">E-posta adresinize gönderilen bağlantıyla hesabınızı doğrulayın. Doğruladıktan sonra bu adresle verdiğiniz önceki siparişler hesabınızda görünür.</p>
            </div>

            @if(session('success'))
                <p class="shop-alert shop-alert--success mb-4">{{ session('success') }}</p>
            @endif

            <form method="post" action="{{ route('verification.send') }}" class="space-y-4">
                @csrf
                <button type="submit" class="btn-primary w-full py-3">Doğrulama bağlantısını tekrar gönder</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="{{ route('account.index') }}" class="font-semibold text-brand-700 hover:text-brand-800">Hesabıma dön</a>
            </p>
        </div>
    </div>
@endsection
