@extends('layouts.shop')
@section('title', __('shop.step_checkout'))

@section('content')
    <div class="shop-page">
    @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
        ['name' => __('shop.home'), 'url' => route('home')],
        ['name' => __('shop.cart'), 'url' => route('cart.index')],
        ['name' => __('shop.step_checkout')],
    ]])
    @include('shop.partials.checkout-steps', ['step' => 2])

    <x-shop.page-hero :title="__('shop.step_checkout')" />

    <form method="post" action="{{ route('checkout.store') }}" class="grid gap-8 lg:grid-cols-3">
        @csrf
        <div class="lg:col-span-2 space-y-6">
            <section class="shop-panel space-y-5">
                <h2 class="shop-checkout-section__head !mb-0">
                    <span class="shop-checkout-section__num">1</span>
                    {{ __('shop.delivery_info') }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="shop-label">{{ __('shop.first_name') }}</label><input name="ad" value="{{ old('ad', auth()->user()?->name) }}" required class="shop-input mt-1"></div>
                    <div><label class="shop-label">{{ __('shop.last_name') }}</label><input name="soyad" value="{{ old('soyad') }}" required class="shop-input mt-1"></div>
                    <div><label class="shop-label">E-posta</label><input type="email" name="eposta" value="{{ old('eposta', auth()->user()?->email) }}" required class="shop-input mt-1"></div>
                    <div><label class="shop-label">{{ __('shop.phone') }}</label><input name="telefon" value="{{ old('telefon') }}" required class="shop-input mt-1"></div>
                    <div><label class="shop-label">{{ __('shop.city') }}</label>
                        <select name="il" required class="shop-input mt-1">
                            <option value="">{{ __('shop.select') }}</option>
                            @foreach($cities as $il)<option value="{{ $il }}" @selected(old('il')==$il)>{{ $il }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="shop-label">{{ __('shop.district') }}</label><input name="ilce" value="{{ old('ilce') }}" required class="shop-input mt-1"></div>
                </div>
                <div><label class="shop-label">{{ __('shop.address') }}</label><textarea name="adres" rows="3" required class="shop-input mt-1">{{ old('adres') }}</textarea></div>
                <div><label class="shop-label">{{ __('shop.postal_code') }}</label><input name="posta_kodu" value="{{ old('posta_kodu') }}" class="shop-input mt-1 max-w-xs"></div>
            </section>

            <section class="shop-panel space-y-4">
                <h2 class="shop-checkout-section__head !mb-0">
                    <span class="shop-checkout-section__num">2</span>
                    {{ __('shop.shipping_method') }}
                </h2>
                @foreach($shippingMethods as $k)
                    <label class="shop-checkout-option">
                        <input type="radio" name="kargo_yontemi" value="{{ $k['id'] }}" @checked(old('kargo_yontemi', 'standart')==$k['id']) required class="mt-1 text-brand-700 focus:ring-brand-500/30">
                        <span>
                            <span class="font-semibold text-slate-900 block">{{ $k['name'] }}</span>
                            <span class="text-sm text-slate-500 mt-0.5 block">{{ $k['desc'] }} · {{ $k['eta'] }}</span>
                        </span>
                    </label>
                @endforeach
            </section>

            <section class="shop-panel space-y-4">
                <h2 class="shop-checkout-section__head !mb-0">
                    <span class="shop-checkout-section__num">3</span>
                    {{ __('shop.payment_method') }}
                </h2>
                @forelse($paymentMethods as $o)
                    <label class="shop-checkout-option">
                        <input type="radio" name="odeme_yontemi" value="{{ $o['id'] }}" @checked(old('odeme_yontemi', $defaultPayment ?? $o['id'])==$o['id']) required class="mt-1 text-brand-700 focus:ring-brand-500/30">
                        <span>
                            <span class="font-semibold text-slate-900 block">{{ $o['name'] }}</span>
                            <span class="text-sm text-slate-500 mt-0.5 block">{{ $o['desc'] }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-slate-500">Aktif ödeme yöntemi bulunmuyor.</p>
                @endforelse
            </section>

            <label class="shop-checkout-contract">
                <input type="checkbox" name="sozlesme" value="1" required class="mt-0.5 shrink-0 rounded text-brand-700 focus:ring-brand-500/30">
                <span class="text-slate-700 leading-relaxed">
                    <a href="{{ route('pages.show', 'on-bilgilendirme') }}" target="_blank" rel="noopener" class="text-brand-700 font-medium underline">{{ __('shop.contract_pre') }}</a>,
                    <a href="{{ route('pages.show', 'mesafeli-satis-sozlesmesi') }}" target="_blank" rel="noopener" class="text-brand-700 font-medium underline">{{ __('shop.contract_distance') }}</a>,
                    <a href="{{ route('pages.show', 'gizlilik-politikasi') }}" target="_blank" rel="noopener" class="text-brand-700 font-medium underline">{{ __('shop.contract_privacy') }}</a>
                    {{ __('shop.contract_accept') }}
                </span>
            </label>
        </div>

        <aside class="space-y-4">
            <div class="shop-panel shop-panel--sticky">
                <h2 class="shop-panel__title">{{ __('shop.order_summary') }}</h2>
                <ul class="mt-4 space-y-3 text-sm max-h-48 overflow-y-auto">
                    @foreach($lines as $line)
                        <li class="flex gap-3">
                            @if($line['product']->imageUrl())
                                <img src="{{ $line['product']->imageUrl() }}" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0 border border-slate-100">
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="line-clamp-2 font-medium text-slate-800">{{ $line['product']->name }}</p>
                                <p class="text-slate-500">× {{ $line['quantity'] }}</p>
                            </div>
                            <span class="font-semibold shrink-0 text-brand-700">{{ number_format($line['line_total'], 2, ',', '.') }} ₺</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-2 text-sm border-t border-slate-100 pt-4">
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('shop.subtotal') }}</dt><dd class="font-medium">{{ number_format($pricing['subtotal'], 2, ',', '.') }} ₺</dd></div>
                    @if($pricing['coupon_discount'] > 0)
                        <div class="flex justify-between text-emerald-700"><dt>{{ __('shop.coupon') }} ({{ $coupon?->code }})</dt><dd>-{{ number_format($pricing['coupon_discount'], 2, ',', '.') }} ₺</dd></div>
                    @endif
                    @if($pricing['promotion_discount'] > 0)
                        <div class="flex justify-between text-emerald-700"><dt>{{ $pricing['promotion_label'] ?? 'Kampanya' }}</dt><dd>-{{ number_format($pricing['promotion_discount'], 2, ',', '.') }} ₺</dd></div>
                    @endif
                    @if($pricing['free_shipping'])
                        <div class="flex justify-between text-emerald-700"><dt>{{ __('shop.shipping') }}</dt><dd>{{ __('shop.free_shipping') }}</dd></div>
                    @endif
                    <div class="flex justify-between text-slate-600"><dt>{{ __('shop.shipping_vat_est') }}</dt><dd>{{ number_format($totals['shipping'] + $totals['vat'] + $totals['cod_fee'], 2, ',', '.') }} ₺</dd></div>
                    <div class="flex justify-between font-bold text-lg pt-3 border-t border-slate-200"><dt>{{ __('shop.total_est') }}</dt><dd class="text-brand-700">{{ number_format($totals['total'], 2, ',', '.') }} ₺</dd></div>
                </dl>
                <p class="mt-2 text-xs text-slate-500 leading-relaxed">{{ __('shop.checkout_estimate_note') }}</p>
                <button type="submit" class="mt-6 btn-primary w-full py-3.5">{{ __('shop.place_order') }}</button>
            </div>

            <div class="shop-panel">
                <p class="font-semibold text-slate-900">{{ __('shop.coupon_code') }}</p>
                @if($coupon)
                    <p class="mt-2 text-sm text-emerald-700 font-medium">{{ $coupon->code }} {{ __('shop.coupon_applied') }}</p>
                    <form method="post" action="{{ route('checkout.coupon.remove') }}" class="mt-2">@csrf @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">{{ __('shop.remove') }}</button>
                    </form>
                @else
                    <form method="post" action="{{ route('checkout.coupon') }}" class="mt-3 flex gap-2">
                        @csrf
                        <input name="coupon_code" placeholder="KOSAR10" class="shop-input flex-1 uppercase text-sm">
                        <button type="submit" class="btn-primary shrink-0 px-4 py-2 text-sm">{{ __('shop.apply') }}</button>
                    </form>
                @endif
            </div>
        </aside>
    </form>
    </div>
@endsection
