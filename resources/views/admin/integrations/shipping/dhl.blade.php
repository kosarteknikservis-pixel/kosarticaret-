@extends('layouts.admin')
@section('title', 'DHL Kargo')

@section('content')
    <x-admin.page-header title="DHL eCommerce" subtitle="Kargo gönderi oluşturma, etiket ve durum senkronu">
        <x-slot:actions>
            <form method="post" action="{{ route('admin.integrations.shipping.dhl.test') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary px-4 py-2.5">Bağlantıyı test et</button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="shipping-dhl" />

    @if($configured)
        <div class="admin-card p-4 mb-5 border-emerald-200 bg-emerald-50/60">
            <p class="text-sm font-semibold text-emerald-900">
                @if($sandbox)
                    Test ortamı seçili. API bilgileri girilmezse offline sandbox; girilirse testapi.mngkargo.com.tr kullanılır.
                @else
                    DHL canlı modu yapılandırıldı.
                @endif
            </p>
        </div>
    @endif

    <form method="post" action="{{ route('admin.integrations.shipping.dhl.update') }}" class="grid gap-5 lg:grid-cols-2 max-w-6xl">
        @csrf @method('PUT')

        <section class="admin-card p-5 sm:p-6 space-y-4">
            <div>
                <h2 class="font-bold text-slate-900">DHL eCommerce API</h2>
                <p class="text-sm text-slate-500 mt-1">Apizone portalından Client ID/Secret; DHL’den müşteri no ve şifre (token için).</p>
            </div>
            <label class="admin-checkbox"><input type="checkbox" name="dhl_enabled" value="1" @checked(($values['dhl_enabled'] ?? '0') === '1')> DHL entegrasyonunu aktif et</label>
            <label class="admin-checkbox"><input type="checkbox" name="dhl_sandbox" value="1" @checked(($values['dhl_sandbox'] ?? '1') === '1')> Test ortamı (testapi.mngkargo.com.tr)</label>
            <div><label class="admin-label">API base URL (opsiyonel)</label><input name="dhl_base_url" value="{{ old('dhl_base_url', $values['dhl_base_url'] ?? '') }}" placeholder="{{ config('carriers.dhl.test_base_url') }}" class="admin-input font-mono text-sm"></div>
            <div><label class="admin-label">x-ibm-client-id</label><input name="dhl_client_id" value="{{ old('dhl_client_id', $values['dhl_client_id'] ?? '') }}" class="admin-input font-mono text-sm" autocomplete="off"></div>
            <div><label class="admin-label">x-ibm-client-secret</label><input type="password" name="dhl_client_secret" value="" class="admin-input font-mono text-sm" placeholder="{{ !empty($values['dhl_client_secret']) ? 'Kayıtlı — değiştirmek için yazın' : 'Apizone secret key' }}"></div>
            <div><label class="admin-label">Müşteri numarası (customerNumber)</label><input name="dhl_customer_number" value="{{ old('dhl_customer_number', $values['dhl_customer_number'] ?? $values['dhl_account_number'] ?? '') }}" class="admin-input font-mono text-sm"></div>
            <div><label class="admin-label">Entegrasyon şifresi (token password)</label><input type="password" name="dhl_password" value="" class="admin-input font-mono text-sm" placeholder="{{ !empty($values['dhl_password']) ? 'Kayıtlı — değiştirmek için yazın' : 'Online şube / test şifresi' }}"></div>
            <p class="text-xs text-slate-500 leading-relaxed">Akış: Token → CreateOrder → CreateBarcode (ZPL etiket). Token 8 saat geçerlidir.</p>
        </section>

        <section class="admin-card p-5 sm:p-6 space-y-4">
            <div>
                <h2 class="font-bold text-slate-900">Gönderici bilgileri</h2>
                <p class="text-sm text-slate-500 mt-1">DHL gönderi kaydında kullanılır.</p>
            </div>
            <div><label class="admin-label">Firma adı</label><input name="dhl_sender_name" value="{{ old('dhl_sender_name', $values['dhl_sender_name'] ?? '') }}" class="admin-input"></div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="admin-label">Telefon</label><input name="dhl_sender_phone" value="{{ old('dhl_sender_phone', $values['dhl_sender_phone'] ?? '') }}" class="admin-input"></div>
                <div><label class="admin-label">E-posta</label><input name="dhl_sender_email" value="{{ old('dhl_sender_email', $values['dhl_sender_email'] ?? '') }}" class="admin-input"></div>
            </div>
            <div><label class="admin-label">Adres</label><input name="dhl_sender_address" value="{{ old('dhl_sender_address', $values['dhl_sender_address'] ?? '') }}" class="admin-input"></div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div><label class="admin-label">İl</label><input name="dhl_sender_city" value="{{ old('dhl_sender_city', $values['dhl_sender_city'] ?? '') }}" class="admin-input"></div>
                <div><label class="admin-label">İlçe</label><input name="dhl_sender_district" value="{{ old('dhl_sender_district', $values['dhl_sender_district'] ?? '') }}" class="admin-input"></div>
                <div><label class="admin-label">Posta kodu</label><input name="dhl_sender_postal_code" value="{{ old('dhl_sender_postal_code', $values['dhl_sender_postal_code'] ?? '') }}" class="admin-input"></div>
            </div>
        </section>

        <section class="admin-card p-5 sm:p-6 space-y-4 lg:col-span-2">
            <div>
                <h2 class="font-bold text-slate-900">SMS bildirimi</h2>
                <p class="text-sm text-slate-500 mt-1">İlk koli kargoya verildiğinde müşteriye takip numarası SMS gider.</p>
            </div>
            <label class="admin-checkbox"><input type="checkbox" name="sms_enabled" value="1" @checked(($values['sms_enabled'] ?? '0') === '1')> SMS gönderimini aktif et</label>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="admin-label">Sağlayıcı</label>
                    <select name="sms_provider" class="admin-input">
                        <option value="log" @selected(($values['sms_provider'] ?? 'log') === 'log')>Log (geliştirme)</option>
                        <option value="netgsm" @selected(($values['sms_provider'] ?? '') === 'netgsm')>Netgsm</option>
                    </select>
                </div>
                <div><label class="admin-label">Gönderici başlığı</label><input name="sms_sender" value="{{ old('sms_sender', $values['sms_sender'] ?? config('carriers.sms.sender')) }}" class="admin-input"></div>
            </div>
            <div><label class="admin-label">Takip SMS şablonu</label><textarea name="sms_tracking_template" rows="3" class="admin-input">{{ old('sms_tracking_template', $values['sms_tracking_template'] ?? config('carriers.sms.tracking_template')) }}</textarea></div>
            <div class="grid gap-3 md:grid-cols-3">
                <div><label class="admin-label">Netgsm usercode</label><input name="netgsm_usercode" value="{{ old('netgsm_usercode', $values['netgsm_usercode'] ?? '') }}" class="admin-input font-mono text-sm"></div>
                <div><label class="admin-label">Netgsm şifre</label><input type="password" name="netgsm_password" value="" class="admin-input font-mono text-sm" placeholder="{{ !empty($values['netgsm_password']) ? 'Kayıtlı' : '' }}"></div>
                <div><label class="admin-label">Netgsm header</label><input name="netgsm_header" value="{{ old('netgsm_header', $values['netgsm_header'] ?? '') }}" class="admin-input font-mono text-sm"></div>
            </div>
            <p class="text-xs text-slate-500">Değişkenler: <code>{customer}</code>, <code>{order_number}</code>, <code>{tracking}</code>, <code>{site}</code></p>
        </section>

        <div class="lg:col-span-2">
            <button type="submit" class="admin-btn admin-btn-primary px-6 py-2.5">Kaydet</button>
        </div>
    </form>
@endsection
