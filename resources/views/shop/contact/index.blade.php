@extends('layouts.shop')
@section('title', __('shop.contact'))

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.contact')],
        ]])

        <x-shop.page-hero
            :title="__('shop.contact')"
            :subtitle="\App\Models\SiteSetting::get('contact_page_intro', 'Sorularınız için formu doldurun veya doğrudan bize ulaşın.')"
        />

        <div class="shop-contact-grid shop-reveal-group">
            <form method="post" action="{{ route('contact.store') }}" class="shop-panel space-y-4">
                @csrf
                <x-shop.spam-fields context="contact" />
                <h2 class="shop-panel__title">{{ __('shop.send_message') }}</h2>
                @if($errors->has('spam'))
                    <p class="shop-form-error">{{ $errors->first('spam') }}</p>
                @endif
                <div><label class="shop-label">{{ __('shop.full_name') }}</label><input name="ad_soyad" value="{{ old('ad_soyad') }}" required class="shop-input mt-1"></div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="shop-label">E-posta</label><input type="email" name="eposta" value="{{ old('eposta') }}" required class="shop-input mt-1"></div>
                    <div><label class="shop-label">{{ __('shop.phone') }}</label><input name="telefon" value="{{ old('telefon') }}" class="shop-input mt-1"></div>
                </div>
                <div><label class="shop-label">Konu</label><input name="konu" value="{{ old('konu') }}" required class="shop-input mt-1"></div>
                <div><label class="shop-label">Mesaj</label><textarea name="mesaj" rows="5" required class="shop-input mt-1">{{ old('mesaj') }}</textarea></div>
                <button type="submit" class="btn-primary px-8 py-3">{{ __('shop.send_message') }}</button>
            </form>

            <aside class="space-y-4">
                <div class="shop-panel space-y-3">
                    <h2 class="shop-panel__title">{{ __('shop.contact_info') }}</h2>
                    <a href="tel:{{ preg_replace('/\D/', '', \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone'))) }}" class="shop-contact-link">
                        <span class="shop-contact-link__icon"><x-shop.icon name="phone" class="w-5 h-5" /></span>
                        {{ \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone')) }}
                    </a>
                    <a href="mailto:{{ \App\Models\SiteSetting::get('contact_email', config('kosar.contact.email')) }}" class="shop-contact-link break-all">
                        <span class="shop-contact-link__icon"><x-shop.icon name="mail" class="w-5 h-5" /></span>
                        {{ \App\Models\SiteSetting::get('contact_email', config('kosar.contact.email')) }}
                    </a>
                    @php $addr = \App\Models\SiteSetting::get('contact_address', config('kosar.contact.address')); @endphp
                    @if($addr)
                        <p class="shop-contact-link items-start">
                            <span class="shop-contact-link__icon"><x-shop.icon name="grid" class="w-5 h-5" /></span>
                            <span>{{ $addr }}</span>
                        </p>
                    @endif
                </div>
                @include('shop.partials.pdp-trust')
            </aside>
        </div>
    </div>
@endsection
