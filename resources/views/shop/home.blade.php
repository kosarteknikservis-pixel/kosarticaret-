@extends('layouts.shop')



@section('title', 'Ana Sayfa')



@section('content')
    <div class="shop-page shop-page--home">
    @include('shop.partials.home-layout', ['homeRows' => $homeRows])



    @include('shop.partials.home-brands', ['brands' => $featuredBrands])



    @if(\App\Models\SiteSetting::get('newsletter_enabled', '1') === '1')

        <section class="shop-reveal shop-section shop-newsletter rounded-2xl lg:rounded-3xl p-8 md:p-12" aria-labelledby="newsletter-heading">

            <div class="shop-newsletter__inner max-w-xl">

                <span class="shop-section-head__eyebrow">{{ __('shop.newsletter_btn') }}</span>

                <h2 id="newsletter-heading" class="mt-2 text-2xl font-bold text-brand-900">{{ \App\Models\SiteSetting::get('newsletter_title', 'Kampanyalardan haberdar olun') }}</h2>

                <p class="mt-2 text-slate-600 text-sm leading-relaxed">{{ __('shop.newsletter_sub') }}</p>

                <form method="post" action="{{ route('newsletter.subscribe') }}" class="mt-6 flex flex-col sm:flex-row gap-2">

                    @csrf

                    <label class="sr-only" for="newsletter-email">E-posta</label>

                    <input id="newsletter-email" type="email" name="email" required placeholder="{{ __('shop.newsletter_placeholder') }}" class="shop-input flex-1 bg-white">

                    <button type="submit" class="btn-primary shrink-0 px-8 py-3">{{ __('shop.newsletter_btn') }}</button>

                </form>

            </div>

        </section>

    @endif
    </div>
@endsection

