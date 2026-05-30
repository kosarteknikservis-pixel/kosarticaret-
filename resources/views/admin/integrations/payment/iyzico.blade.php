@extends('layouts.admin')

@section('title', 'iyzico')

@section('content')
    <x-admin.page-header
        title="iyzico"
        subtitle="Entegrasyonlar → Ödeme → iyzico sanal POS ayarları"
    />

    @if(session('success'))
        <p class="admin-alert-success mb-4">{{ session('success') }}</p>
    @endif

    <x-admin.integrations-nav active="iyzico" />

    <p class="text-sm text-slate-600 mb-4">
        Aktif kredi kartı sağlayıcısı: <strong>{{ \App\Support\PaymentGatewayConfig::label() }}</strong>
    </p>

    <form method="post" action="{{ route('admin.integrations.payment.iyzico.update') }}" class="admin-card p-6 sm:p-8 max-w-3xl space-y-5">
        @csrf @method('PUT')

        @if($isActive && $isConfigured)
            <p class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">iyzico şu an kredi kartı ödemelerinde <strong>aktif</strong>.</p>
        @elseif($isActive)
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">iyzico aktif seçili ancak API bilgileri eksik.</p>
        @endif

        @if($values['has_iyzico_api'] || $values['has_iyzico_secret'])
            <p class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">Kayıtlı API bilgileri mevcut.</p>
        @endif

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">API key</label>
                <input type="password" name="iyzico_api_key" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ $values['has_iyzico_api'] ? '••••••••' : '' }}">
            </div>
            <div>
                <label class="admin-label">Secret key</label>
                <input type="password" name="iyzico_secret_key" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ $values['has_iyzico_secret'] ? '••••••••' : '' }}">
            </div>
        </div>

        <label class="admin-checkbox"><input type="checkbox" name="iyzico_sandbox" value="1" @checked(old('iyzico_sandbox', $values['iyzico_sandbox']) === '1')> Sandbox (test) ortamı</label>

        <div>
            <label class="admin-label">API adresi (isteğe bağlı)</label>
            <input name="iyzico_base_url" value="{{ old('iyzico_base_url', $values['iyzico_base_url']) }}" class="admin-input font-mono text-xs" placeholder="Boş = sandbox veya canlı otomatik">
        </div>

        <div>
            <label class="admin-label">Callback URL (iyzico panel)</label>
            <input type="text" readonly value="{{ $callbackUrl }}" class="admin-input font-mono text-xs bg-slate-50" onclick="this.select()">
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
            <label class="admin-checkbox font-semibold">
                <input type="checkbox" name="set_active" value="1" @checked($isActive)> Kredi kartı için iyzico'yu aktif sağlayıcı yap
            </label>
            <label class="admin-checkbox text-sm text-slate-600">
                <input type="checkbox" name="payment_gateway" value="mock" @checked($values['payment_gateway'] === 'mock')> Bunun yerine demo ödeme modu (geliştirme)
            </label>
        </div>

        <x-admin.form-footer>Kaydet</x-admin.form-footer>
    </form>
@endsection
