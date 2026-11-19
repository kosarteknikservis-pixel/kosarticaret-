@php
    $code = $code ?? '404';
    $titleId = 'error-'.$code.'-title';
    $searchId = 'error-'.$code.'-search';
@endphp
<section class="shop-error-page shop-error-page--404" aria-labelledby="{{ $titleId }}">
    <div class="shop-error-page__bg" aria-hidden="true">
        <span class="shop-error-page__orb shop-error-page__orb--1"></span>
        <span class="shop-error-page__orb shop-error-page__orb--2"></span>
    </div>

    <div class="shop-error-page__inner">
        <p class="shop-error-page__code" aria-hidden="true">{{ $code }}</p>

        <div class="shop-error-page__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
        </div>

        <h1 id="{{ $titleId }}" class="shop-error-page__title">Sayfa bulunamadı</h1>
        <p class="shop-error-page__lead">Aradığınız sayfa artık bu adreste bulunmuyor. Ürün aramasını kullanabilir veya bizimle iletişime geçebilirsiniz.</p>

        <form action="{{ route('search') }}" method="get" class="shop-error-page__search">
            <label class="sr-only" for="{{ $searchId }}">{{ __('shop.search_placeholder') }}</label>
            <input id="{{ $searchId }}" type="search" name="q" placeholder="{{ __('shop.search_placeholder') }}" class="shop-search-input shop-error-page__search-input" required>
            <button type="submit" class="btn-primary shop-error-page__search-btn">{{ __('shop.search_btn') }}</button>
        </form>

        <nav class="shop-error-page__links" aria-label="Yardımcı bağlantılar">
            <a href="{{ route('contact.show') }}" class="shop-error-page__link shop-error-page__link--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                {{ __('shop.contact') }}
            </a>
            <a href="{{ route('home') }}" class="shop-error-page__link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                Ana sayfa
            </a>
        </nav>
    </div>
</section>
