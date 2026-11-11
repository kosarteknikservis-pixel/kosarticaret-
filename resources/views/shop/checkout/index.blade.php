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

    <div class="grid gap-8 lg:grid-cols-3">
        <form id="checkout-form" method="post" action="{{ route('checkout.store') }}" class="lg:col-span-2 space-y-6">
            @csrf
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
                        <select id="checkout-city" name="il" required class="shop-input mt-1">
                            <option value="">{{ __('shop.select') }}</option>
                            @foreach($cities as $il)<option value="{{ $il }}" @selected(old('il')==$il)>{{ $il }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="shop-label">{{ __('shop.district') }}</label>
                        <select id="checkout-district" name="ilce" required class="shop-input mt-1" data-selected="{{ old('ilce') }}">
                            <option value="">{{ __('shop.select') }}</option>
                            @foreach(($districtsByCity[old('il')] ?? []) as $ilce)
                                <option value="{{ $ilce }}" @selected(old('ilce')==$ilce)>{{ $ilce }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div><label class="shop-label">{{ __('shop.address') }}</label><textarea name="adres" rows="3" required class="shop-input mt-1">{{ old('adres') }}</textarea></div>
                <div><label class="shop-label">{{ __('shop.postal_code') }}</label><input name="posta_kodu" value="{{ old('posta_kodu') }}" class="shop-input mt-1 max-w-xs"></div>

                <div class="shop-corporate-invoice" data-corporate-invoice>
                    <label class="shop-corporate-invoice__toggle">
                        <input
                            type="checkbox"
                            id="corporate-invoice-toggle"
                            name="kurumsal_fatura"
                            value="1"
                            @checked(old('kurumsal_fatura'))
                        >
                        <span class="shop-corporate-invoice__switch" aria-hidden="true"></span>
                        <span>
                            <strong>Kurumsal fatura</strong>
                            <small>İsteğe bağlı olarak firma bilgilerinizi ekleyin.</small>
                        </span>
                    </label>
                    <div class="shop-corporate-invoice__fields" data-corporate-invoice-fields>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="shop-label">Firma adı</label><input name="firma_adi" value="{{ old('firma_adi') }}" class="shop-input mt-1" data-corporate-field></div>
                            <div><label class="shop-label">Vergi numarası</label><input name="vergi_numarasi" value="{{ old('vergi_numarasi') }}" class="shop-input mt-1" inputmode="numeric" data-corporate-field></div>
                            <div><label class="shop-label">Vergi dairesi</label><input name="vergi_dairesi" value="{{ old('vergi_dairesi') }}" class="shop-input mt-1" data-corporate-field></div>
                            <div class="sm:col-span-2"><label class="shop-label">Fatura adresi</label><textarea name="fatura_adresi" rows="3" class="shop-input mt-1" data-corporate-field>{{ old('fatura_adresi') }}</textarea></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="shop-panel space-y-4">
                <h2 class="shop-checkout-section__head !mb-0">
                    <span class="shop-checkout-section__num">2</span>
                    {{ __('shop.shipping_method') }}
                </h2>
                @foreach($shippingMethods as $k)
                    <label class="shop-checkout-option">
                        <input type="radio" name="kargo_yontemi" value="{{ $k['id'] }}" @checked(old('kargo_yontemi', $defaultShipping ?? $shippingMethods[0]['id'])==$k['id']) required class="mt-1 text-brand-700 focus:ring-brand-500/30">
                        <span>
                            <span class="font-semibold text-slate-900 block">{{ $k['name'] }}</span>
                            <span class="text-sm text-slate-500 mt-0.5 block">
                                {{ $k['desc'] }}@if(!empty($k['eta'])) · {{ $k['eta'] }}@endif
                                · {{ (float) $k['fee'] <= 0 ? __('shop.free_shipping') : number_format((float) $k['fee'], 2, ',', '.').' ₺' }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </section>

            <section class="shop-panel space-y-4">
                <h2 class="shop-checkout-section__head !mb-0">
                    <span class="shop-checkout-section__num">3</span>
                    {{ __('shop.payment_method') }}
                </h2>
                @php
                    $selectedPayment = old('odeme_yontemi', $defaultPayment ?? ($paymentMethods[0]['id'] ?? null));
                    $selectedCodTender = old('kapida_odeme_sekli', 'nakit');
                @endphp
                @forelse($paymentMethods as $o)
                    <label class="shop-checkout-option">
                        <input type="radio" name="odeme_yontemi" value="{{ $o['id'] }}" @checked($selectedPayment==$o['id']) required class="mt-1 text-brand-700 focus:ring-brand-500/30" data-checkout-payment>
                        <span>
                            <span class="font-semibold text-slate-900 block">{{ $o['name'] }}</span>
                            <span class="text-sm text-slate-500 mt-0.5 block">{{ $o['desc'] }}</span>
                            @if($o['id'] === 'kapida_odeme' && ($codSmsEnabled ?? false))
                                <span class="text-sm text-slate-500 mt-0.5 block">Sipariş, telefona gelen SMS kodu doğrulandıktan sonra tamamlanır.</span>
                            @endif
                            @if($o['id'] === 'kapida_odeme')
                                <div class="shop-checkout-cod-tender{{ $selectedPayment === 'kapida_odeme' ? '' : ' hidden' }}"
                                     data-cod-tender
                                     @unless($selectedPayment === 'kapida_odeme') hidden @endunless>
                                    <p class="shop-checkout-cod-tender__title">Teslimatta nasıl ödeyeceksiniz?</p>
                                    <div class="shop-checkout-cod-tender__options">
                                        @foreach(\App\Support\PaymentMethod::codTenderLabels() as $tenderId => $tenderLabel)
                                            <label class="shop-checkout-cod-tender__option">
                                                <input type="radio"
                                                       name="kapida_odeme_sekli"
                                                       value="{{ $tenderId }}"
                                                       @checked($selectedCodTender === $tenderId)
                                                       @if($selectedPayment === 'kapida_odeme') required @endif
                                                       class="mt-0.5 text-brand-700 focus:ring-brand-500/30"
                                                       data-cod-tender-input>
                                                <span>{{ $tenderLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('kapida_odeme_sekli')
                                        <p class="shop-checkout-cod-tender__error">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-slate-500">Aktif ödeme yöntemi bulunmuyor.</p>
                @endforelse
            </section>

            <label class="shop-checkout-contract">
                <input type="checkbox" name="sozlesme" value="1" required @checked(old('sozlesme')) class="mt-0.5 shrink-0 rounded text-brand-700 focus:ring-brand-500/30">
                <span class="text-slate-700 leading-relaxed">
                    <a href="{{ route('pages.show', 'on-bilgilendirme') }}" target="_blank" rel="noopener" class="text-brand-700 font-medium underline">{{ __('shop.contract_pre') }}</a>,
                    <a href="{{ route('pages.show', 'mesafeli-satis-sozlesmesi') }}" target="_blank" rel="noopener" class="text-brand-700 font-medium underline">{{ __('shop.contract_distance') }}</a>,
                    <a href="{{ route('pages.show', 'gizlilik-politikasi') }}" target="_blank" rel="noopener" class="text-brand-700 font-medium underline">{{ __('shop.contract_privacy') }}</a>
                    {{ __('shop.contract_accept') }}
                </span>
            </label>
        </form>

        <aside class="shop-checkout-sidebar">
            <div class="shop-checkout-sidebar__sticky space-y-4">
            <div class="shop-panel">
                <h2 class="shop-panel__title">{{ __('shop.order_summary') }}</h2>
                <ul class="mt-4 space-y-3 text-sm max-h-48 overflow-y-auto">
                    @foreach($lines as $line)
                        <li class="flex gap-3">
                            @if($line['product']->imageUrl())
                                <img src="{{ $line['product']->imageUrl('product-thumb') }}" alt="{{ $line['product']->imageAltText() }}" loading="lazy" decoding="async" width="48" height="48" class="shop-checkout-summary-img w-12 h-12 rounded-lg object-cover shrink-0 border border-slate-100">
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="line-clamp-2 font-medium text-slate-800">{{ $line['product']->name }}</p>
                                <p class="text-slate-500">× {{ $line['quantity'] }}</p>
                            </div>
                            <span class="font-semibold shrink-0 text-brand-700">{{ number_format($line['line_total'], 2, ',', '.') }} ₺</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-2 text-sm border-t border-slate-100 pt-4" id="checkout-summary" data-payment-quotes='@json($paymentQuotes)'>
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('shop.subtotal') }}</dt><dd class="font-medium">{{ number_format($pricing['subtotal'], 2, ',', '.') }} ₺</dd></div>
                    @if($pricing['coupon_discount'] > 0)
                        <div class="flex justify-between text-emerald-700"><dt>{{ __('shop.coupon') }} ({{ $coupon?->code }})</dt><dd>-{{ number_format($pricing['coupon_discount'], 2, ',', '.') }} ₺</dd></div>
                    @endif
                    @if($pricing['promotion_discount'] > 0)
                        <div class="flex justify-between text-emerald-700"><dt>{{ $pricing['promotion_label'] ?? 'Kampanya' }}</dt><dd>-{{ number_format($pricing['promotion_discount'], 2, ',', '.') }} ₺</dd></div>
                    @endif
                    @if($totals['shipping'] > 0)
                        <div class="flex justify-between text-slate-600"><dt>{{ __('shop.shipping') }}</dt><dd>{{ number_format($totals['shipping'], 2, ',', '.') }} ₺</dd></div>
                    @else
                        <div class="flex justify-between text-emerald-700"><dt>{{ __('shop.shipping') }}</dt><dd>{{ __('shop.free_shipping') }}</dd></div>
                    @endif
                    @if($totals['vat'] > 0)
                        <div class="flex justify-between text-slate-600"><dt>KDV</dt><dd>{{ number_format($totals['vat'], 2, ',', '.') }} ₺</dd></div>
                    @endif
                    <div class="justify-between text-emerald-700" data-checkout-havale style="display: {{ ($totals['havale_discount'] ?? 0) > 0 ? 'flex' : 'none' }}">
                        <dt>Havale indirimi{{ $havalePercentLabel !== '' ? ' (%'.$havalePercentLabel.')' : '' }}</dt>
                        <dd data-checkout-havale-amount>@if(($totals['havale_discount'] ?? 0) > 0)-{{ number_format($totals['havale_discount'], 2, ',', '.') }} ₺@endif</dd>
                    </div>
                    <div class="justify-between text-slate-600" data-checkout-cod style="display: {{ $totals['cod_fee'] > 0 ? 'flex' : 'none' }}">
                        <dt>Kapıda ödeme ücreti</dt>
                        <dd data-checkout-cod-amount>@if($totals['cod_fee'] > 0){{ number_format($totals['cod_fee'], 2, ',', '.') }} ₺@endif</dd>
                    </div>
                    <div class="flex justify-between font-bold text-lg pt-3 border-t border-slate-200"><dt>{{ __('shop.total_est') }}</dt><dd class="text-brand-700" data-checkout-total>{{ number_format($totals['total'], 2, ',', '.') }} ₺</dd></div>
                </dl>
                <p class="mt-2 text-xs text-slate-500 leading-relaxed">{{ __('shop.checkout_estimate_note') }}</p>
                <button type="submit" form="checkout-form" class="mt-6 btn-primary w-full py-3.5">{{ __('shop.place_order') }}</button>
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
                        <input name="coupon_code" placeholder="KOSAR10" class="shop-input flex-1 uppercase" autocomplete="off" spellcheck="false">
                        <button type="submit" class="btn-primary shrink-0 px-4 py-2">{{ __('shop.apply') }}</button>
                    </form>
                @endif
            </div>
            </div>
        </aside>
    </div>
    <script>
        (function () {
            const districtsByCity = @json($districtsByCity, JSON_UNESCAPED_UNICODE);
            const citySelect = document.getElementById('checkout-city');
            const districtSelect = document.getElementById('checkout-district');
            if (!citySelect || !districtSelect) return;

            function fillDistricts() {
                const selectedDistrict = districtSelect.dataset.selected || districtSelect.value;
                const districts = districtsByCity[citySelect.value] || [];
                districtSelect.innerHTML = '<option value="">{{ __('shop.select') }}</option>';
                districts.forEach(function (district) {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    option.selected = district === selectedDistrict;
                    districtSelect.appendChild(option);
                });
                districtSelect.disabled = districts.length === 0;
                districtSelect.dataset.selected = '';
            }

            citySelect.addEventListener('change', function () {
                districtSelect.dataset.selected = '';
                fillDistricts();
            });
            fillDistricts();
        })();

        (function () {
            const toggle = document.getElementById('corporate-invoice-toggle');
            const wrapper = document.querySelector('[data-corporate-invoice]');
            const fields = document.querySelectorAll('[data-corporate-field]');
            if (!toggle || !fields.length) return;

            function syncCorporateFields() {
                if (wrapper) {
                    wrapper.classList.toggle('is-open', toggle.checked);
                }
                fields.forEach(function (field) {
                    field.required = toggle.checked;
                    field.disabled = !toggle.checked;
                });
            }

            toggle.addEventListener('change', syncCorporateFields);
            syncCorporateFields();
        })();

        (function () {
            const summary = document.getElementById('checkout-summary');
            const form = document.getElementById('checkout-form');
            if (!summary || !form) return;

            let quotes = {};
            try {
                quotes = JSON.parse(summary.dataset.paymentQuotes || '{}');
            } catch (e) {
                return;
            }

            const havaleRow = summary.querySelector('[data-checkout-havale]');
            const havaleAmount = summary.querySelector('[data-checkout-havale-amount]');
            const codRow = summary.querySelector('[data-checkout-cod]');
            const codAmount = summary.querySelector('[data-checkout-cod-amount]');
            const total = summary.querySelector('[data-checkout-total]');
            const money = (value) => new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ₺';

            function syncPaymentQuote() {
                const selected = form.querySelector('input[name="odeme_yontemi"]:checked');
                const quote = selected ? quotes[selected.value] : null;
                if (!quote) return;

                if (havaleRow && havaleAmount) {
                    const discount = Number(quote.havale_discount) || 0;
                    havaleRow.style.display = discount > 0 ? 'flex' : 'none';
                    havaleAmount.textContent = discount > 0 ? '-' + money(discount) : '';
                }
                if (codRow && codAmount) {
                    const fee = Number(quote.cod_fee) || 0;
                    codRow.style.display = fee > 0 ? 'flex' : 'none';
                    codAmount.textContent = fee > 0 ? money(fee) : '';
                }
                if (total) total.textContent = money(Number(quote.total) || 0);
            }

            form.querySelectorAll('input[name="odeme_yontemi"]').forEach(function (input) {
                input.addEventListener('change', syncPaymentQuote);
            });
        })();

        (function () {
            const form = document.getElementById('checkout-form');
            if (!form) return;

            const tenderWrap = form.querySelector('[data-cod-tender]');
            if (!tenderWrap) return;

            const tenderInputs = Array.from(form.querySelectorAll('[data-cod-tender-input]'));

            function syncCodTender() {
                const selected = form.querySelector('input[name="odeme_yontemi"]:checked');
                const isCod = !!(selected && selected.value === 'kapida_odeme');
                tenderWrap.classList.toggle('hidden', !isCod);
                if (isCod) {
                    tenderWrap.removeAttribute('hidden');
                } else {
                    tenderWrap.setAttribute('hidden', 'hidden');
                }
                tenderInputs.forEach(function (input) {
                    input.required = isCod;
                    if (!isCod) input.checked = false;
                });
                if (isCod && !tenderInputs.some(function (input) { return input.checked; })) {
                    const first = tenderInputs[0];
                    if (first) first.checked = true;
                }
            }

            form.querySelectorAll('[data-checkout-payment]').forEach(function (input) {
                input.addEventListener('change', syncCodTender);
            });
            syncCodTender();
        })();

        (function () {
            const form = document.getElementById('checkout-form');
            if (!form) return;

            const fields = ['ad', 'soyad', 'eposta', 'telefon']
                .map(function (name) { return form.elements.namedItem(name); })
                .filter(Boolean);
            if (!fields.length) return;

            let timer = null;
            function payload() {
                return fields.reduce(function (data, field) {
                    data[field.name] = field.value || '';
                    return data;
                }, {});
            }

            function hasContact(data) {
                return Object.values(data).some(function (value) {
                    return String(value).trim().length >= 2;
                });
            }

            function saveContact() {
                const data = payload();
                if (!hasContact(data)) return;

                fetch('{{ route('checkout.contact-save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data),
                    keepalive: true
                }).catch(function () {});
            }

            function scheduleSave() {
                window.clearTimeout(timer);
                timer = window.setTimeout(saveContact, 700);
            }

            fields.forEach(function (field) {
                field.addEventListener('input', scheduleSave);
                field.addEventListener('change', saveContact);
                field.addEventListener('blur', saveContact);
            });
        })();

    </script>
    @php
        $checkoutGaItems = collect($lines)->map(fn ($line) => [
            'item_id' => (string) $line['product']->id,
            'item_name' => $line['product']->name,
            'item_brand' => $line['product']->brand?->name,
            'price' => (float) $line['product']->price,
            'quantity' => (int) $line['quantity'],
        ])->values()->all();
    @endphp
    @include('shop.partials.ga4-ecommerce', [
        'ga4Payload' => [
            'event' => 'begin_checkout',
            'params' => [
                'currency' => 'TRY',
                'value' => (float) ($totals['total'] ?? 0),
                'items' => $checkoutGaItems,
            ],
        ],
    ])
    </div>
@endsection

@push('scripts')
    @php
        $codOtpNotice = session('cod_otp_notice');
        $showCodOtpModal = ($codOtp ?? null) || $errors->has('otp_code') || is_array($codOtpNotice);
        $codMaskedPhone = $codOtp['masked'] ?? null;
    @endphp
    {{-- Üyelik popup’ı ile aynı shop-auth-modal iskeleti --}}
    <div id="cod-sms-modal"
         class="shop-auth-modal{{ $showCodOtpModal ? '' : ' hidden' }}"
         aria-hidden="{{ $showCodOtpModal ? 'false' : 'true' }}">
        <div class="shop-auth-modal__backdrop" data-cod-otp-close></div>
        <section class="shop-auth-modal__dialog"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="cod-sms-modal-title"
                 tabindex="-1">
            <button type="button" class="shop-auth-modal__close" data-cod-otp-close aria-label="Kapat">
                <x-shop.icon name="x" class="w-5 h-5" />
            </button>

            <div class="text-center mb-6">
                <p id="cod-sms-modal-title" class="shop-auth-modal__title">SMS doğrulama</p>
                <p class="shop-auth-modal__subtitle">
                    @if($codMaskedPhone)
                        Kapıda ödeme için <strong class="text-slate-900">{{ $codMaskedPhone }}</strong> numarasına 6 haneli kod gönderildi. Kod doğruysa siparişiniz hemen onaylanır.
                    @else
                        Kapıda ödeme için telefonunuza gelen 6 haneli SMS kodunu girin. Kod doğruysa siparişiniz hemen onaylanır.
                    @endif
                </p>
            </div>

            @if(is_array($codOtpNotice) && ! empty($codOtpNotice['message']) && ! ($codOtpNotice['ok'] ?? false))
                <p class="shop-cod-otp-notice is-error" role="status">{{ $codOtpNotice['message'] }}</p>
            @elseif(is_array($codOtpNotice) && ! empty($codOtpNotice['message']) && ($codOtpNotice['resend'] ?? false))
                <p class="shop-cod-otp-notice is-ok" role="status">{{ $codOtpNotice['message'] }}</p>
            @endif
            @error('otp_code')
                <p class="shop-cod-otp-notice is-error" role="alert">{{ $message }}</p>
            @enderror

            <div class="mb-5">
                <label class="shop-label" for="otp-code">Doğrulama kodu</label>
                <input id="otp-code"
                       form="checkout-form"
                       name="otp_code"
                       value="{{ old('otp_code') }}"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       maxlength="6"
                       pattern="[0-9]*"
                       placeholder="· · · · · ·"
                       class="shop-input shop-cod-otp-input mt-1"
                       @if($showCodOtpModal) autofocus @endif>
            </div>

            <div class="shop-cod-otp-actions">
                <button type="submit"
                        form="checkout-form"
                        name="otp_action"
                        value="resend"
                        formnovalidate
                        class="btn-outline shop-cod-otp-actions__btn">
                    Kodu yeniden gönder
                </button>
                <button type="submit"
                        form="checkout-form"
                        class="btn-primary shop-cod-otp-actions__btn">
                    Doğrula ve siparişi onayla
                </button>
            </div>
        </section>
    </div>
    <script>
        (function () {
            const modal = document.getElementById('cod-sms-modal');
            if (!modal) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            const input = document.getElementById('otp-code');
            const shouldOpen = !modal.classList.contains('hidden');

            function setOpen(isOpen) {
                modal.classList.toggle('hidden', !isOpen);
                modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                document.documentElement.classList.toggle('overflow-hidden', isOpen);
                document.body.classList.toggle('shop-cod-otp-open', isOpen);
                if (isOpen && input) {
                    window.setTimeout(function () { input.focus(); }, 40);
                }
            }

            modal.querySelectorAll('[data-cod-otp-close]').forEach(function (el) {
                el.addEventListener('click', function () { setOpen(false); });
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    setOpen(false);
                }
            });
            if (shouldOpen) {
                setOpen(true);
            }
        })();
    </script>
@endpush
