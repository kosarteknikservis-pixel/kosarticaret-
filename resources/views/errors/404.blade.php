@extends('layouts.shop')
@section('title', 'Sayfa Bulunamadı')

@section('content')
    <section class="shop-error-page shop-error-page--404" aria-labelledby="error-404-title">
        <div class="shop-error-page__bg" aria-hidden="true">
            <span class="shop-error-page__orb shop-error-page__orb--1"></span>
            <span class="shop-error-page__orb shop-error-page__orb--2"></span>
        </div>

        <div class="shop-error-page__inner">
            <p class="shop-error-page__code" aria-hidden="true">404</p>

            <div class="shop-error-page__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>

            <h1 id="error-404-title" class="shop-error-page__title">Sayfa bulunamadı</h1>
            <p class="shop-error-page__lead">Aradığınız adres taşınmış, silinmiş veya hiç var olmamış olabilir. Ana sayfadan veya arama ile devam edebilirsiniz.</p>

            <form action="{{ route('search') }}" method="get" class="shop-error-page__search">
                <label class="sr-only" for="error-404-search">{{ __('shop.search_placeholder') }}</label>
                <input id="error-404-search" type="search" name="q" placeholder="{{ __('shop.search_placeholder') }}" class="shop-search-input shop-error-page__search-input" required>
                <button type="submit" class="btn-primary shop-error-page__search-btn">{{ __('shop.search_btn') }}</button>
            </form>

            <nav class="shop-error-page__links" aria-label="Yardımcı bağlantılar">
                <a href="{{ route('home') }}" class="shop-error-page__link shop-error-page__link--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Ana sayfa
                </a>
                <a href="{{ route('products.index') }}" class="shop-error-page__link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    Ürünler
                </a>
                <a href="{{ route('contact.show') }}" class="shop-error-page__link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    {{ __('shop.contact') }}
                </a>
            </nav>
        </div>
    </section>
@endsection
