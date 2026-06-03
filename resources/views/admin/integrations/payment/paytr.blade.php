@extends('layouts.admin')

@section('title', 'PayTR')

@section('content')
    <x-admin.page-header
        title="PayTR"
        subtitle="Entegrasyonlar → Ödeme → PayTR sanal POS ayarları"
    />

    @if(session('success'))
        <p class="admin-alert-success mb-4">{{ session('success') }}</p>
    @endif

    <x-admin.integrations-nav active="paytr" />

    <p class="text-sm text-slate-600 mb-4">
        Aktif kredi kartı sağlayıcısı: <strong>{{ \App\Support\PaymentGatewayConfig::label() }}</strong>
        · Havale / kapıda ödeme: <a href="{{ route('admin.settings.edit', ['tab' => 'shipping']) }}" class="text-teal-700 font-semibold">Site ayarları → Kargo & ödeme</a>
    </p>

    <form method="post" action="{{ route('admin.integrations.payment.paytr.update') }}" class="admin-card p-6 sm:p-8 max-w-3xl space-y-5">
        @csrf @method('PUT')

        @if($isActive && $isConfigured)
            <p class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">PayTR şu an kredi kartı ödemelerinde <strong>aktif</strong>.</p>
        @elseif($isActive)
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">PayTR aktif seçili ancak bilgiler eksik — ödeme demo moduna düşer.</p>
        @endif

        @if($values['has_paytr_key'] || $values['has_paytr_salt'])
            <p class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">Kayıtlı key/salt mevcut — değiştirmek için yeni değer yazın.</p>
        @endif

        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="admin-label">Mağaza no (merchant_id)</label><input name="paytr_merchant_id" value="{{ old('paytr_merchant_id', $values['paytr_merchant_id']) }}" class="admin-input font-mono text-sm"></div>
            <div>
                <label class="admin-label">Merchant key</label>
                <input type="password" name="paytr_merchant_key" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ $values['has_paytr_key'] ? '••••••••' : '' }}">
            </div>
            <div>
                <label class="admin-label">Merchant salt</label>
                <input type="password" name="paytr_merchant_salt" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ $values['has_paytr_salt'] ? '••••••••' : '' }}">
            </div>
            <div class="flex items-end">
                <label class="admin-checkbox"><input type="checkbox" name="paytr_test_mode" value="1" @checked(old('paytr_test_mode', $values['paytr_test_mode']) === '1')> Test modu</label>
            </div>
        </div>

        <div>
            <label class="admin-label">Bildirim URL (PayTR paneline yapıştırın)</label>
            <input type="text" readonly value="{{ $callbackUrl }}" class="admin-input font-mono text-xs bg-slate-50" onclick="this.select()">
        </div>

        <div>
            <label class="admin-label">Taksit tablosu token</label>
            <input
                name="paytr_installment_table_token"
                value="{{ old('paytr_installment_table_token', $values['paytr_installment_table_token']) }}"
                class="admin-input font-mono text-sm"
                autocomplete="off"
                placeholder="PayTR panelindeki Taksit Tablosu için Token"
            >
            <p class="mt-2 text-xs leading-5 text-slate-500">
                PayTR panelindeki "Taksit Tablosu için Token" değerini buraya ekleyin. Bu alan doluysa ürün sayfasında resmi PayTR taksit tablosu kullanılır.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
            <label class="admin-checkbox font-semibold">
                <input type="checkbox" name="set_active" value="1" @checked($isActive)> Kredi kartı için PayTR'yi aktif sağlayıcı yap
            </label>
            <label class="admin-checkbox text-sm text-slate-600">
                <input type="checkbox" name="payment_gateway" value="mock" @checked($values['payment_gateway'] === 'mock')> Bunun yerine demo ödeme modu (geliştirme)
            </label>
        </div>

        <x-admin.form-footer>Kaydet</x-admin.form-footer>
    </form>
@endsection
