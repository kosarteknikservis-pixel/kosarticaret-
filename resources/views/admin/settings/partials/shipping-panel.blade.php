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

<h3 class="admin-section-title">Kargo ücretleri</h3>
<div class="grid sm:grid-cols-2 gap-4">
    <div><label class="admin-label">Standart kargo (₺)</label><input name="shipping_rate_standart" type="number" step="0.01" value="{{ $shippingValues['shipping_rate_standart'] }}" class="admin-input"></div>
    <div><label class="admin-label">Hızlı kargo (₺)</label><input name="shipping_rate_hizli" type="number" step="0.01" value="{{ $shippingValues['shipping_rate_hizli'] }}" class="admin-input"></div>
</div>

@foreach($shippingMethods as $method)
    @php $id = $method['id']; @endphp
    <h3 class="admin-section-title">{{ $method['name'] }} ({{ $id }})</h3>
    <div class="space-y-3">
        <div><label class="admin-label">Görünen ad</label><input name="ship_{{ $id }}_name" value="{{ $shippingValues['ship_'.$id.'_name'] }}" class="admin-input"></div>
        <div>
            <label class="admin-label">Açıklama
                @if($id === 'standart')<span class="text-slate-400 font-normal">— boş bırakırsanız ücretsiz kargo limiti metni kullanılır</span>@endif
            </label>
            <input name="ship_{{ $id }}_desc" value="{{ $shippingValues['ship_'.$id.'_desc'] }}" class="admin-input">
        </div>
        <div><label class="admin-label">Teslimat süresi</label><input name="ship_{{ $id }}_eta" value="{{ $shippingValues['ship_'.$id.'_eta'] }}" class="admin-input"></div>
    </div>
@endforeach

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
