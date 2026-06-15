@extends('layouts.admin')
@section('title', 'Sipariş '.$order->order_number)

@section('content')
    <x-admin.page-header :title="'Sipariş '.$order->order_number" :subtitle="$order->email.' · '.$order->created_at->format('d.m.Y H:i')" />

    @php
        $teslimat = $order->shipping_address['teslimat'] ?? [];
        $kurumsalFatura = $teslimat['kurumsalFatura'] ?? null;
        $kargoFirma = $order->shipping_address['kargo_firma']['name'] ?? $order->shipping_address['kargo_yontemi'] ?? '—';
        $nextItemIndex = $order->items->count();
    @endphp

    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="admin-card p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Durum</p>
            <p class="mt-1 font-extrabold text-slate-900">{{ \App\Support\OrderStatus::label($order->status) }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Ödeme</p>
            <p class="mt-1 font-extrabold text-slate-900">{{ \App\Support\PaymentStatus::label($order->payment_status) }}</p>
            @if($order->payment_failed_at)
                <p class="mt-1 text-xs text-red-700">Son başarısız deneme: {{ $order->payment_failed_at->format('d.m.Y H:i') }}</p>
            @endif
        </div>
        <div class="admin-card p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kargo</p>
            <p class="mt-1 font-extrabold text-slate-900">{{ $kargoFirma }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Toplam</p>
            <p class="mt-1 font-extrabold text-teal-700">{{ number_format($order->total, 2, ',', '.') }} ₺</p>
        </div>
    </div>

    <form id="order-update-form" method="post" action="{{ route('admin.orders.update', $order) }}" class="admin-order-detail">
        @csrf @method('PATCH')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <section class="admin-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-slate-900">Ürünler</h2>
                            <p class="text-xs text-slate-500 mt-1">Adet ve fiyat değişirse toplamlar yeniden hesaplanır.</p>
                        </div>
                        @if($order->status === 'teslim_edildi')
                            <span class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">Teslim edilmiş sipariş</span>
                        @endif
                    </div>
                    <div class="admin-table-wrap admin-order-items-wrap">
                        <table class="admin-table admin-order-items-table">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th>Adet</th>
                                    <th>Birim fiyat</th>
                                    <th>Sil</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $i => $item)
                                    <tr>
                                        <td data-label="Ürün">
                                            <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                                            <p class="font-semibold text-slate-900">{{ $item->product_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $item->sku ?: 'SKU yok' }}</p>
                                        </td>
                                        <td data-label="Adet"><input type="number" min="1" name="items[{{ $i }}][quantity]" value="{{ $item->quantity }}" class="admin-input w-24"></td>
                                        <td data-label="Birim fiyat"><input type="number" min="0" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ $item->unit_price }}" class="admin-input w-32"></td>
                                        <td data-label="Sil"><label class="admin-checkbox"><input type="checkbox" name="items[{{ $i }}][remove]" value="1"> Sil</label></td>
                                    </tr>
                                @endforeach
                                @for($j = 0; $j < 1; $j++)
                                    @php $i = $nextItemIndex + $j; @endphp
                                    <tr>
                                        <td data-label="Ürün">
                                            <div class="admin-product-picker js-order-product-picker">
                                                <input type="hidden" name="items[{{ $i }}][product_id]" class="js-order-product-id">
                                                <button type="button" class="admin-product-picker__button" aria-expanded="false">
                                                    <span class="admin-product-picker__label" data-picker-label>Yeni ürün ekle</span>
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
                                        </td>
                                        <td data-label="Adet"><input type="number" min="1" name="items[{{ $i }}][quantity]" value="1" class="admin-input w-24"></td>
                                        <td data-label="Birim fiyat"><input type="number" min="0" step="0.01" name="items[{{ $i }}][unit_price]" value="0" class="admin-input w-32 js-order-unit-price"></td>
                                        <td data-label="Not"><span class="text-xs text-slate-400">Boşsa eklenmez</span></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-order-totals px-5 py-4 border-t border-slate-100 grid gap-2 sm:grid-cols-2 text-sm">
                        <div class="text-slate-500">Ara toplam: <strong class="text-slate-900">{{ number_format($order->subtotal, 2, ',', '.') }} ₺</strong></div>
                        <div class="text-slate-500">Kargo: <strong class="text-slate-900">{{ number_format($order->shipping_cost, 2, ',', '.') }} ₺</strong></div>
                        <div class="text-slate-500">İndirim: <strong class="text-slate-900">{{ number_format($order->discount, 2, ',', '.') }} ₺</strong></div>
                        <div class="text-slate-500">Toplam: <strong class="text-teal-700">{{ number_format($order->total, 2, ',', '.') }} ₺</strong></div>
                    </div>
                </section>

                <section class="admin-card p-5 sm:p-6">
                    <h2 class="font-bold text-slate-900 mb-4">Teslimat bilgileri</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="admin-label">Ad</label><input name="ad" value="{{ old('ad', $teslimat['ad'] ?? '') }}" class="admin-input" required></div>
                        <div><label class="admin-label">Soyad</label><input name="soyad" value="{{ old('soyad', $teslimat['soyad'] ?? '') }}" class="admin-input" required></div>
                        <div><label class="admin-label">E-posta</label><input type="email" name="eposta" value="{{ old('eposta', $order->email) }}" class="admin-input" required></div>
                        <div><label class="admin-label">Telefon</label><input name="telefon" value="{{ old('telefon', $order->phone ?? ($teslimat['telefon'] ?? '')) }}" class="admin-input" required></div>
                        <div>
                            <label class="admin-label">İl</label>
                            <select id="admin-order-city" name="il" class="admin-input" required>
                                <option value="">Seçin</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" @selected(old('il', $teslimat['il'] ?? '') === $city)>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">İlçe</label>
                            <select id="admin-order-district" name="ilce" class="admin-input" required data-selected="{{ old('ilce', $teslimat['ilce'] ?? '') }}">
                                <option value="">Seçin</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2"><label class="admin-label">Adres</label><textarea name="adres" rows="3" class="admin-input" required>{{ old('adres', $teslimat['adres'] ?? '') }}</textarea></div>
                        <div><label class="admin-label">Posta kodu</label><input name="posta_kodu" value="{{ old('posta_kodu', $teslimat['postaKodu'] ?? $teslimat['posta_kodu'] ?? '') }}" class="admin-input"></div>
                    </div>
                </section>

                <section class="admin-card p-5 sm:p-6">
                    <label class="admin-checkbox mb-4">
                        <input id="admin-corporate-toggle" type="checkbox" name="kurumsal_fatura" value="1" @checked(old('kurumsal_fatura', (bool) $kurumsalFatura))>
                        Kurumsal fatura bilgisi var
                    </label>
                    <div id="admin-corporate-fields" class="grid gap-4 sm:grid-cols-2">
                        <div><label class="admin-label">Firma adı</label><input name="firma_adi" value="{{ old('firma_adi', $kurumsalFatura['firmaAdi'] ?? '') }}" class="admin-input" data-corporate-field></div>
                        <div><label class="admin-label">Vergi numarası</label><input name="vergi_numarasi" value="{{ old('vergi_numarasi', $kurumsalFatura['vergiNumarasi'] ?? '') }}" class="admin-input" data-corporate-field></div>
                        <div><label class="admin-label">Vergi dairesi</label><input name="vergi_dairesi" value="{{ old('vergi_dairesi', $kurumsalFatura['vergiDairesi'] ?? '') }}" class="admin-input" data-corporate-field></div>
                        <div class="sm:col-span-2"><label class="admin-label">Fatura adresi</label><textarea name="fatura_adresi" rows="3" class="admin-input" data-corporate-field>{{ old('fatura_adresi', $kurumsalFatura['faturaAdresi'] ?? '') }}</textarea></div>
                    </div>
                </section>

                <section class="admin-card p-5 sm:p-6">
                    <h2 class="font-bold text-slate-900 mb-4">İşlem geçmişi</h2>
                    @forelse($order->logs as $log)
                        <div class="border-l-2 border-slate-200 pl-4 pb-4 last:pb-0">
                            <p class="font-semibold text-slate-900">{{ $log->message }}</p>
                            <p class="text-xs text-slate-500">{{ $log->created_at->format('d.m.Y H:i') }} · {{ $log->user?->email ?? 'Sistem' }}</p>
                            @if($log->new_values)
                                <dl class="mt-2 grid gap-1 text-xs text-slate-600">
                                    @foreach($log->new_values as $field => $value)
                                        <div><dt class="inline font-semibold">{{ $field }}:</dt> <dd class="inline">{{ is_array($value) ? implode(', ', $value) : $value }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Henüz işlem geçmişi yok.</p>
                    @endforelse
                </section>
            </div>

            <aside>
                <div class="admin-order-sidebar-stack space-y-4">
                    <div class="admin-card p-5 sm:p-6 space-y-4">
                        <h2 class="font-bold text-slate-900">Sipariş yönetimi</h2>
                        <div>
                            <label class="admin-label">Durum</label>
                            <select name="status" class="admin-input">
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $order->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">Ödeme durumu</label>
                            <select name="payment_status" class="admin-input">
                                @foreach($paymentStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_status', $order->payment_status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="admin-label">Kargo takip no</label><input name="shipping_tracking" value="{{ old('shipping_tracking', $order->shipping_tracking) }}" class="admin-input font-mono"></div>
                        <div><label class="admin-label">Admin notu</label><textarea name="admin_note" rows="4" class="admin-input">{{ old('admin_note', $order->admin_note) }}</textarea></div>
                        <button type="submit" class="admin-btn admin-btn-primary w-full py-2.5">Siparişi güncelle</button>
                        <p class="text-xs text-slate-500 leading-relaxed">Durum veya takip no değişirse müşteriye otomatik e-posta gönderilir.</p>
                    </div>

                    @if($order->isPendingPayment())
                        <div class="admin-card p-5 sm:p-6 space-y-4 border-amber-200 bg-amber-50/50">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Ödeme bekleniyor</p>
                                <h2 class="font-bold text-slate-900 mt-1">PayTR ödemesi tamamlanmadı</h2>
                            </div>
                            <dl class="text-sm text-slate-700 space-y-2">
                                <div>
                                    <dt class="font-semibold text-slate-900">Müşteri ödeme sayfası</dt>
                                    <dd class="mt-1 break-all font-mono text-xs">{{ $order->paymentPageUrl() }}</dd>
                                </div>
                                @if($order->payment_reminder_sent_at)
                                    <div>
                                        <dt class="font-semibold text-slate-900">Hatırlatma e-postası</dt>
                                        <dd>{{ $order->payment_reminder_sent_at->format('d.m.Y H:i') }} gönderildi</dd>
                                    </div>
                                @endif
                            </dl>
                            <form method="post" action="{{ route('admin.orders.payment-reminder', $order) }}" onsubmit="return confirm('Ödeme hatırlatma e-postası müşteriye gönderilsin mi?');">
                                @csrf
                                <button type="submit" class="admin-btn admin-btn-secondary w-full py-2.5">Ödeme hatırlatması gönder</button>
                            </form>
                            <p class="text-xs text-slate-500 leading-relaxed">Otomatik hatırlatma siparişten {{ config('kosar.payment_reminder.delay_hours', 2) }} saat sonra bir kez gönderilir.</p>
                        </div>
                    @endif

                    <div class="admin-card p-5 sm:p-6 space-y-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Muhasebe</p>
                            <h2 class="font-bold text-slate-900 mt-1">Paraşüt</h2>
                        </div>
                        @if($order->parasut_sales_invoice_id)
                            <p class="admin-alert-success text-sm">Taslak satış faturası oluşturuldu.</p>
                            <dl class="text-sm text-slate-600 space-y-1">
                                <div><dt class="inline font-semibold">Fatura ID:</dt> <dd class="inline font-mono">{{ $order->parasut_sales_invoice_id }}</dd></div>
                                <div><dt class="inline font-semibold">Aktarım:</dt> <dd class="inline">{{ $order->parasut_synced_at?->format('d.m.Y H:i') }}</dd></div>
                            </dl>
                        @else
                            <p class="text-sm text-slate-600">Bu sipariş Paraşüt’e henüz aktarılmadı.</p>
                            <button type="submit" form="parasut-sync-form" class="admin-btn admin-btn-secondary w-full py-2.5" onclick="return confirm('Bu sipariş Paraşüt’e taslak satış faturası olarak aktarılsın mı?');">Paraşüt’e aktar</button>
                        @endif
                        @if($order->parasut_error)
                            <p class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $order->parasut_error }}</p>
                        @endif
                        <p class="text-xs text-slate-500 leading-relaxed">Aktarım manuel çalışır ve Paraşüt tarafında taslak satış faturası oluşturur.</p>
                    </div>

                    <div class="admin-card p-5 sm:p-6 space-y-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Müşteri kaynağı</p>
                            <h2 class="font-bold text-slate-900 mt-1">Sipariş izi</h2>
                        </div>
                        <dl class="text-sm text-slate-600 space-y-2">
                            <div><dt class="font-semibold text-slate-900">Kaynak</dt><dd>{{ $order->order_source ?: 'Bilinmiyor / eski sipariş' }}</dd></div>
                            @if($order->order_medium)
                                <div><dt class="font-semibold text-slate-900">Medium</dt><dd>{{ $order->order_medium }}</dd></div>
                            @endif
                            @if($order->order_campaign)
                                <div><dt class="font-semibold text-slate-900">Kampanya</dt><dd>{{ $order->order_campaign }}</dd></div>
                            @endif
                            @if($order->landing_url)
                                <div><dt class="font-semibold text-slate-900">İlk giriş</dt><dd class="break-words">{{ $order->landing_url }}</dd></div>
                            @endif
                            @if($order->referrer_url)
                                <div><dt class="font-semibold text-slate-900">Referans</dt><dd class="break-words">{{ $order->referrer_url }}</dd></div>
                            @endif
                        </dl>
                        <p class="text-xs text-slate-500 leading-relaxed">Bu alan yeni siparişlerde ziyaretçi kaynağından otomatik dolar.</p>
                    </div>

                    <a href="{{ route('admin.orders.index') }}" class="block text-center text-sm font-semibold text-teal-700 hover:underline">← Sipariş listesi</a>
                </div>
            </aside>
        </div>
    </form>

    <form id="parasut-sync-form" method="post" action="{{ route('admin.orders.parasut.sync', $order) }}" class="hidden">
        @csrf
    </form>

    @push('scripts')
        <script>
            (function () {
                const districtsByCity = @json($districtsByCity, JSON_UNESCAPED_UNICODE);
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
                            label.textContent = option.dataset.label || 'Yeni ürün ekle';

                            const priceInput = picker.closest('tr')?.querySelector('.js-order-unit-price');
                            if (priceInput && option.dataset.price && (priceInput.value === '0' || priceInput.value === '')) {
                                priceInput.value = option.dataset.price;
                            }

                            closePicker();
                        });
                    });

                    document.addEventListener('click', function (event) {
                        if (!picker.contains(event.target)) {
                            closePicker();
                        }
                    });

                    picker.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closePicker();
                            trigger.focus();
                        }
                    });
                });
            })();
        </script>
    @endpush
@endsection
