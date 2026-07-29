@extends('layouts.admin')
@section('title', 'Yeni sipariş')

@section('content')
    <x-admin.page-header title="Yeni sipariş" subtitle="Telefon, WhatsApp veya yüz yüze satışlar için manuel sipariş oluşturun.">
        <x-slot:actions>
            <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-secondary px-4 py-2.5">Siparişlere dön</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form id="order-create-form" method="post" action="{{ route('admin.orders.store') }}" class="admin-order-detail">
        @csrf
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <section class="admin-card admin-order-sheet overflow-hidden">
                    <div class="admin-order-sheet__header">
                        <div class="min-w-0">
                            <p class="admin-order-sheet__eyebrow">Sipariş içeriği</p>
                            <h2 class="admin-order-sheet__title">Ürünler</h2>
                        </div>
                    </div>

                    <div class="admin-order-lines" id="admin-order-lines">
                        @for($i = 0; $i < 3; $i++)
                            <article class="admin-order-line admin-order-line--add">
                                <div class="admin-order-line__add-head">
                                    <span class="admin-order-line__add-icon" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <div>
                                        <p class="admin-order-line__add-title">Ürün {{ $i + 1 }}</p>
                                        <p class="admin-order-line__add-sub">Ürün seçilmezse kayıt sırasında atlanır.</p>
                                    </div>
                                </div>

                                <div class="admin-product-picker js-order-product-picker admin-order-line__picker">
                                    <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ old('items.'.$i.'.product_id') }}" class="js-order-product-id">
                                    <button type="button" class="admin-product-picker__button" aria-expanded="false">
                                        <span class="admin-product-picker__label" data-picker-label>Ürün ara ve seç</span>
                                        <span class="admin-product-picker__chevron" aria-hidden="true">⌄</span>
                                    </button>
                                    <div class="admin-product-picker__panel" hidden>
                                        <input type="search" class="admin-input admin-product-picker__search" placeholder="Ürün ara">
                                        <div class="admin-product-picker__list">
                                            @foreach($products as $product)
                                                @php
                                                    $productOptionLabel = $product->name.($product->sku ? ' - '.$product->sku : '').' (Stok: '.$product->stock.')';
                                                @endphp
                                                <button type="button" class="admin-product-picker__option" data-product-id="{{ $product->id }}" data-price="{{ $product->price }}" data-label="{{ $productOptionLabel }}" data-search="{{ \Illuminate\Support\Str::lower($productOptionLabel) }}">
                                                    {{ $productOptionLabel }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="admin-order-line__controls admin-order-line__controls--add" role="group" aria-label="Ürün adet ve fiyat">
                                    <label class="admin-order-line__control">
                                        <span class="admin-order-line__control-label">Adet</span>
                                        <input type="number" min="1" name="items[{{ $i }}][quantity]" value="{{ old('items.'.$i.'.quantity', 1) }}" class="admin-input admin-order-line__input js-order-qty" data-qty>
                                    </label>
                                    <label class="admin-order-line__control">
                                        <span class="admin-order-line__control-label">Birim fiyat</span>
                                        <input type="number" min="0" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ old('items.'.$i.'.unit_price', 0) }}" class="admin-input admin-order-line__input js-order-unit-price js-order-price" data-price>
                                    </label>
                                </div>
                            </article>
                        @endfor
                    </div>

                    <footer class="admin-order-receipt" id="admin-order-receipt">
                        <div class="admin-order-receipt__row">
                            <span>Ara toplam</span>
                            <strong data-receipt-subtotal>0,00 ₺</strong>
                        </div>
                        <div class="admin-order-receipt__row">
                            <span>Kargo</span>
                            <strong data-receipt-shipping>0,00 ₺</strong>
                        </div>
                        <div class="admin-order-receipt__row">
                            <span>İndirim</span>
                            <strong data-receipt-discount>0,00 ₺</strong>
                        </div>
                        <div class="admin-order-receipt__row">
                            <span>KDV</span>
                            <strong data-receipt-vat>0,00 ₺</strong>
                        </div>
                        <div class="admin-order-receipt__row">
                            <span>Kapıda ödeme</span>
                            <strong data-receipt-cod>0,00 ₺</strong>
                        </div>
                        <div class="admin-order-receipt__row admin-order-receipt__row--total">
                            <span>Genel toplam</span>
                            <strong data-receipt-total>0,00 ₺</strong>
                        </div>
                    </footer>
                </section>

                <section class="admin-card p-5 sm:p-6">
                    <h2 class="font-bold text-slate-900 mb-4">Teslimat bilgileri</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="admin-label">Ad</label><input name="ad" value="{{ old('ad') }}" class="admin-input" required></div>
                        <div><label class="admin-label">Soyad</label><input name="soyad" value="{{ old('soyad') }}" class="admin-input" required></div>
                        <div><label class="admin-label">E-posta</label><input type="email" name="eposta" value="{{ old('eposta') }}" class="admin-input" required></div>
                        <div><label class="admin-label">Telefon</label><input name="telefon" value="{{ old('telefon') }}" class="admin-input" required></div>
                        <div>
                            <label class="admin-label">İl</label>
                            <select id="admin-order-city" name="il" class="admin-input" required>
                                <option value="">Seçin</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" @selected(old('il') === $city)>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">İlçe</label>
                            <select id="admin-order-district" name="ilce" class="admin-input" required data-selected="{{ old('ilce') }}">
                                <option value="">Seçin</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2"><label class="admin-label">Adres</label><textarea name="adres" rows="3" class="admin-input" required>{{ old('adres') }}</textarea></div>
                        <div><label class="admin-label">Posta kodu</label><input name="posta_kodu" value="{{ old('posta_kodu') }}" class="admin-input"></div>
                    </div>
                </section>

                <section class="admin-card p-5 sm:p-6">
                    <label class="admin-checkbox mb-4">
                        <input id="admin-corporate-toggle" type="checkbox" name="kurumsal_fatura" value="1" @checked(old('kurumsal_fatura'))>
                        Kurumsal fatura bilgisi var
                    </label>
                    <div id="admin-corporate-fields" class="grid gap-4 sm:grid-cols-2">
                        <div><label class="admin-label">Firma adı</label><input name="firma_adi" value="{{ old('firma_adi') }}" class="admin-input" data-corporate-field></div>
                        <div><label class="admin-label">Vergi numarası</label><input name="vergi_numarasi" value="{{ old('vergi_numarasi') }}" class="admin-input" data-corporate-field></div>
                        <div><label class="admin-label">Vergi dairesi</label><input name="vergi_dairesi" value="{{ old('vergi_dairesi') }}" class="admin-input" data-corporate-field></div>
                        <div class="sm:col-span-2"><label class="admin-label">Fatura adresi</label><textarea name="fatura_adresi" rows="3" class="admin-input" data-corporate-field>{{ old('fatura_adresi') }}</textarea></div>
                    </div>
                </section>
            </div>

            <aside>
                <div class="admin-order-sidebar-stack space-y-4 min-w-0">
                    <div class="admin-card p-5 sm:p-6 space-y-4">
                        <h2 class="font-bold text-slate-900">Sipariş ayarları</h2>
                        <div>
                            <label class="admin-label">Ödeme yöntemi</label>
                            <select name="payment_method" id="admin-payment-method" class="admin-input">
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method['id'] }}" @selected(old('payment_method', 'havale') === $method['id'])>{{ $method['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">Kargo yöntemi</label>
                            <select name="kargo_yontemi" id="admin-shipping-method" class="admin-input">
                                @foreach($shippingMethods as $method)
                                    <option value="{{ $method['id'] }}" @selected(old('kargo_yontemi', 'standart') === $method['id'])>{{ $method['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">İndirim (₺)</label>
                            <input type="number" min="0" step="0.01" name="discount" id="admin-discount" value="{{ old('discount', 0) }}" class="admin-input">
                        </div>
                        <div>
                            <label class="admin-label">Durum</label>
                            <select name="status" id="admin-order-status" class="admin-input">
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'hazirlaniyor') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">Ödeme durumu</label>
                            <select name="payment_status" id="admin-payment-status" class="admin-input">
                                @foreach($paymentStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_status', 'basarili') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="admin-label">Kargo takip no</label><input name="shipping_tracking" value="{{ old('shipping_tracking') }}" class="admin-input font-mono"></div>
                        <div><label class="admin-label">Admin notu</label><textarea name="admin_note" rows="4" class="admin-input">{{ old('admin_note') }}</textarea></div>
                        <label class="admin-checkbox">
                            <input type="checkbox" name="send_confirmation_email" value="1" @checked(old('send_confirmation_email', true))>
                            Müşteriye onay e-postası gönder
                        </label>
                        <label class="admin-checkbox">
                            <input type="checkbox" name="send_telegram" value="1" @checked(old('send_telegram', true))>
                            Telegram bildirimi gönder
                        </label>
                        <button type="submit" class="admin-btn admin-btn-primary w-full py-2.5">Siparişi oluştur</button>
                        <p class="text-xs text-slate-500 leading-relaxed">Stok düşülür ve sipariş detay sayfasına yönlendirilirsiniz. Kredi kartı seçilirse ödeme bekleyen sipariş oluşturulur; PayTR otomatik başlatılmaz.</p>
                    </div>

                    <a href="{{ route('admin.orders.index') }}" class="block text-center text-sm font-semibold text-teal-700 hover:underline">← Sipariş listesi</a>
                </div>
            </aside>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                const districtsByCity = @json($districtsByCity, JSON_UNESCAPED_UNICODE);
                const shippingRates = @json($shippingRates, JSON_UNESCAPED_UNICODE);
                const freeShippingMin = {{ json_encode($freeShippingMin) }};
                const codFee = {{ json_encode($codFee) }};
                const vatRate = {{ json_encode($vatRate) }};
                const shouldAddVat = @json($shouldAddVat);

                const city = document.getElementById('admin-order-city');
                const district = document.getElementById('admin-order-district');
                if (city && district) {
                    function fillDistricts() {
                        const selected = district.dataset.selected || district.value;
                        const districts = districtsByCity[city.value] || [];
                        district.innerHTML = '<option value="">Seçin</option>';
                        districts.forEach(function (name) {
                            const option = document.createElement('option');
                            option.value = name;
                            option.textContent = name;
                            option.selected = name === selected;
                            district.appendChild(option);
                        });
                        district.dataset.selected = '';
                    }
                    city.addEventListener('change', fillDistricts);
                    fillDistricts();
                }

                const corporateToggle = document.getElementById('admin-corporate-toggle');
                const corporateFields = document.querySelectorAll('[data-corporate-field]');
                function syncCorporate() {
                    corporateFields.forEach(function (field) {
                        field.disabled = corporateToggle && !corporateToggle.checked;
                    });
                }
                if (corporateToggle) {
                    corporateToggle.addEventListener('change', syncCorporate);
                    syncCorporate();
                }

                function formatMoney(value) {
                    return new Intl.NumberFormat('tr-TR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(value) + ' ₺';
                }

                function calcTotals() {
                    let lineSubtotal = 0;
                    document.querySelectorAll('.admin-order-line--add').forEach(function (line) {
                        const productId = line.querySelector('.js-order-product-id')?.value;
                        if (!productId) {
                            return;
                        }
                        const qty = parseFloat(line.querySelector('[data-qty]')?.value) || 0;
                        const price = parseFloat(line.querySelector('[data-price]')?.value) || 0;
                        lineSubtotal += qty * price;
                    });

                    const discount = Math.max(0, parseFloat(document.getElementById('admin-discount')?.value) || 0);
                    const subtotal = Math.max(0, lineSubtotal - discount);
                    const shippingMethod = document.getElementById('admin-shipping-method')?.value || 'standart';
                    const paymentMethod = document.getElementById('admin-payment-method')?.value || 'havale';
                    let shipping = parseFloat(shippingRates[shippingMethod] || 0);
                    if (freeShippingMin > 0 && subtotal >= freeShippingMin && shippingMethod !== 'hizli') {
                        shipping = 0;
                    }
                    const cod = paymentMethod === 'kapida_odeme' ? codFee : 0;
                    const vatBase = subtotal + shipping;
                    const vat = shouldAddVat ? Math.round(vatBase * vatRate * 100) / 100 : 0;
                    const total = Math.round((vatBase + vat + cod) * 100) / 100;

                    const set = function (selector, value) {
                        const el = document.querySelector(selector);
                        if (el) {
                            el.textContent = formatMoney(value);
                        }
                    };

                    set('[data-receipt-subtotal]', subtotal);
                    set('[data-receipt-shipping]', shipping);
                    set('[data-receipt-discount]', discount);
                    set('[data-receipt-vat]', vat);
                    set('[data-receipt-cod]', cod);
                    set('[data-receipt-total]', total);
                }

                document.querySelectorAll('.js-order-product-picker').forEach(function (picker) {
                    const trigger = picker.querySelector('.admin-product-picker__button');
                    const panel = picker.querySelector('.admin-product-picker__panel');
                    const search = picker.querySelector('.admin-product-picker__search');
                    const hiddenInput = picker.querySelector('.js-order-product-id');
                    const label = picker.querySelector('[data-picker-label]');
                    const options = picker.querySelectorAll('.admin-product-picker__option');

                    function closePicker() {
                        panel.hidden = true;
                        trigger.setAttribute('aria-expanded', 'false');
                    }

                    function openPicker() {
                        panel.hidden = false;
                        trigger.setAttribute('aria-expanded', 'true');
                        search.focus();
                    }

                    trigger.addEventListener('click', function () {
                        panel.hidden ? openPicker() : closePicker();
                    });

                    search.addEventListener('input', function () {
                        const term = search.value.trim().toLocaleLowerCase('tr-TR');
                        options.forEach(function (option) {
                            option.hidden = term !== '' && !option.dataset.search.includes(term);
                        });
                    });

                    options.forEach(function (option) {
                        option.addEventListener('click', function () {
                            hiddenInput.value = option.dataset.productId || '';
                            label.textContent = option.dataset.label || 'Ürün seç';

                            const priceInput = picker.closest('.admin-order-line')?.querySelector('.js-order-unit-price');
                            if (priceInput && option.dataset.price && (priceInput.value === '0' || priceInput.value === '')) {
                                priceInput.value = option.dataset.price;
                            }

                            closePicker();
                            calcTotals();
                        });
                    });

                    document.addEventListener('click', function (event) {
                        if (!picker.contains(event.target)) {
                            closePicker();
                        }
                    });
                });

                document.getElementById('admin-order-lines')?.addEventListener('input', calcTotals);
                document.getElementById('admin-discount')?.addEventListener('input', calcTotals);
                document.getElementById('admin-shipping-method')?.addEventListener('change', calcTotals);
                document.getElementById('admin-payment-method')?.addEventListener('change', function () {
                    const method = this.value;
                    const status = document.getElementById('admin-order-status');
                    const paymentStatus = document.getElementById('admin-payment-status');
                    if (method === 'kredi_karti' && status && paymentStatus) {
                        status.value = 'odeme_bekliyor';
                        paymentStatus.value = 'bekliyor';
                    }
                    calcTotals();
                });

                calcTotals();
            })();
        </script>
    @endpush
@endsection
