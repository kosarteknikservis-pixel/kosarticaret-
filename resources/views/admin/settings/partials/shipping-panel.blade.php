@php
    $paymentEnabled = $paymentEnabled ?? \App\Support\PaymentMethodSettings::enabledForAdmin();
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 mb-6">
    <p class="text-sm text-slate-600">
        <strong>Ücretsiz kargo limiti</strong> vitrin ve standart kargo metninde kullanılır — değeri
        <a href="#settings-tab-general" class="text-teal-700 font-semibold admin-settings-tab-jump" data-tab="general">Genel</a>
        sekmesindeki alandan kaydedilir (bu sekmede kaydetmeyin).
    </p>
</div>

<div class="grid sm:grid-cols-2 gap-4">
    <div><label class="admin-label">Kapıda ödeme ücreti (₺)</label><input name="cod_fee" type="number" step="0.01" value="{{ $shippingValues['cod_fee'] }}" class="admin-input"></div>
    <div><label class="admin-label">KDV oranı (0.20 = %20)</label><input name="vat_rate" type="number" step="0.01" min="0" max="1" value="{{ $shippingValues['vat_rate'] }}" class="admin-input"></div>
</div>
<label class="admin-checkbox mt-4 rounded-xl border border-slate-200 bg-white p-4">
    <input type="checkbox" name="checkout_add_vat" value="1" @checked(($shippingValues['checkout_add_vat'] ?? '0') === '1')>
    <span>
        <span class="font-semibold text-slate-900">Sepette ürün fiyatına ayrıca KDV ekle</span>
        <span class="block text-xs text-slate-500 mt-1">Kapalıyken ürün fiyatları KDV dahil kabul edilir; ödeme özetinde sadece ürün + kargo + varsa kapıda ödeme ücreti hesaplanır.</span>
    </span>
</label>

<h3 class="admin-section-title">Kargo firmaları</h3>
<p class="text-sm text-slate-600 -mt-2 mb-4">Müşteri ödeme sayfasında aktif olan firmaları seçer. Boş satırlar kaydedilmez; en az bir aktif firma kalır.</p>
@php
    $shippingRows = collect($shippingMethods)->values();
    $emptyRows = max(0, 5 - $shippingRows->count());
@endphp
<div class="space-y-3">
    @foreach($shippingRows as $i => $method)
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <label class="admin-checkbox mb-3">
                <input type="checkbox" name="shipping_methods[{{ $i }}][active]" value="1" @checked($method['active'] ?? true)>
                Aktif
            </label>
            <div class="grid gap-3 lg:grid-cols-4">
                <div><label class="admin-label">Firma adı</label><input name="shipping_methods[{{ $i }}][name]" value="{{ $method['name'] }}" class="admin-input" placeholder="Aras Kargo"></div>
                <div><label class="admin-label">Ücret (₺)</label><input name="shipping_methods[{{ $i }}][fee]" type="number" step="0.01" value="{{ $method['fee'] }}" class="admin-input"></div>
                <div><label class="admin-label">Açıklama</label><input name="shipping_methods[{{ $i }}][desc]" value="{{ $method['desc'] }}" class="admin-input" placeholder="1000 TL üzeri ücretsiz"></div>
                <div><label class="admin-label">Teslimat süresi</label><input name="shipping_methods[{{ $i }}][eta]" value="{{ $method['eta'] }}" class="admin-input" placeholder="2-4 iş günü"></div>
            </div>
        </div>
    @endforeach
    @for($j = 0; $j < $emptyRows; $j++)
        @php $i = $shippingRows->count() + $j; @endphp
        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/70 p-4">
            <label class="admin-checkbox mb-3">
                <input type="checkbox" name="shipping_methods[{{ $i }}][active]" value="1">
                Aktif
            </label>
            <div class="grid gap-3 lg:grid-cols-4">
                <div><label class="admin-label">Firma adı</label><input name="shipping_methods[{{ $i }}][name]" class="admin-input" placeholder="Yeni kargo firması"></div>
                <div><label class="admin-label">Ücret (₺)</label><input name="shipping_methods[{{ $i }}][fee]" type="number" step="0.01" class="admin-input" placeholder="0.00"></div>
                <div><label class="admin-label">Açıklama</label><input name="shipping_methods[{{ $i }}][desc]" class="admin-input" placeholder="Açıklama"></div>
                <div><label class="admin-label">Teslimat süresi</label><input name="shipping_methods[{{ $i }}][eta]" class="admin-input" placeholder="2-4 iş günü"></div>
            </div>
        </div>
    @endfor
</div>

<h3 class="admin-section-title">Ödeme yöntemleri — görünürlük</h3>
<p class="text-sm text-slate-600 -mt-2 mb-4">
    Ödeme sayfası ve footer güven şeridinde hangi yöntemlerin listeleneceğini seçin.
    Sanal POS:
    <a href="{{ route('admin.integrations.payment.index') }}" class="text-teal-700 font-semibold">Entegrasyonlar → Ödeme</a>
</p>
<div class="overflow-x-auto rounded-xl border border-slate-200 mb-6">
    <table class="admin-table text-sm">
        <thead>
            <tr>
                <th>Yöntem</th>
                <th class="text-center w-28">Ödeme sayfası</th>
                <th class="text-center w-24">Footer</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paymentMethods as $payment)
                @php $id = $payment['id']; @endphp
                <tr>
                    <td class="font-semibold">{{ $shippingValues['pay_'.$id.'_name'] ?: $payment['name'] }}</td>
                    <td class="text-center">
                        <input type="checkbox" name="payment_checkout_enabled[]" value="{{ $id }}" @checked(in_array($id, $paymentEnabled['checkout'], true))>
                    </td>
                    <td class="text-center">
                        <input type="checkbox" name="payment_footer_enabled[]" value="{{ $id }}" @checked(in_array($id, $paymentEnabled['footer'], true))>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@foreach($paymentMethods as $payment)
    @php $id = $payment['id']; @endphp
    <h3 class="admin-section-title text-base">{{ $shippingValues['pay_'.$id.'_name'] ?: $payment['name'] }} <span class="text-slate-400 font-normal text-sm">({{ $id }})</span></h3>
    <div class="space-y-3 mb-6">
        <div><label class="admin-label">Görünen ad</label><input name="pay_{{ $id }}_name" value="{{ $shippingValues['pay_'.$id.'_name'] }}" class="admin-input"></div>
        <div>
            <label class="admin-label">Açıklama
                @if($id === 'kapida_odeme')<span class="text-slate-400 font-normal">— {fee} kapıda ödeme ücreti ile değişir</span>@endif
            </label>
            <input name="pay_{{ $id }}_desc" value="{{ $shippingValues['pay_'.$id.'_desc'] }}" class="admin-input">
        </div>
    </div>
@endforeach
