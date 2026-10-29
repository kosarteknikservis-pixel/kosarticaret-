@extends('layouts.admin')
@section('title', $channel->name.' ayarları')

@section('content')
    <x-admin.page-header :title="$channel->name" subtitle="API kimlik bilgileri ve senkron kuralları">
        <x-slot:actions>
            <a href="{{ route('admin.integrations.marketplace.channels.index') }}" class="admin-btn admin-btn-secondary">Kanallar</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="marketplace-channels" />

    <form method="post" action="{{ route('admin.integrations.marketplace.channels.update', $channel) }}" class="grid gap-5 lg:grid-cols-[1fr_min(22rem,32%)] max-w-5xl">
        @csrf @method('PUT')

        <div class="admin-card p-6 sm:p-8 space-y-5">
            <h3 class="admin-section-title" style="margin-top:0">Bağlantı</h3>

            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $channel->is_active)) class="rounded border-slate-300">
                Kanalı aktif et
            </label>

            <div>
                <label class="admin-label">Ortam</label>
                <select name="environment" class="admin-input">
                    <option value="production" @selected(old('environment', $channel->environment) === 'production')>Canlı</option>
                    <option value="sandbox" @selected(old('environment', $channel->environment) === 'sandbox')>Test (Sandbox)</option>
                </select>
            </div>

            <h3 class="admin-section-title">API bilgileri</h3>
            <p class="text-sm text-slate-500 -mt-2">Boş bırakılan alanlar mevcut değeri korur. Değerler şifreli saklanır.</p>

            @foreach($credentialFields as $key => $label)
                <div>
                    <label class="admin-label">{{ $label }}</label>
                    <input type="password" name="credentials[{{ $key }}]" class="admin-input font-mono text-sm" autocomplete="off" placeholder="{{ data_get($channel->credentials, $key) ? '•••••••• (değiştirmek için yazın)' : 'Girin' }}">
                </div>
            @endforeach

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="admin-btn admin-btn-primary px-5 py-2.5">Kaydet</button>
            </div>
        </div>

        <div class="space-y-5">
            <div class="admin-card p-6 space-y-4">
                <h3 class="admin-section-title" style="margin-top:0">Senkron kuralları</h3>

                <div>
                    <label class="admin-label">Fiyat modu</label>
                    <select name="settings[price_mode]" class="admin-input">
                        @foreach([
                            'site' => 'Site fiyatı',
                            'markup' => 'Site + markup',
                            'margin' => 'Maliyet + kâr (komisyon düşülür)',
                            'fixed' => 'Manuel (listing)',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('settings.price_mode', $settings['price_mode'] ?? 'site') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="admin-label">Markup (%)</label>
                    <input type="number" step="0.01" min="0" name="settings[price_markup_percent]" value="{{ old('settings.price_markup_percent', $settings['price_markup_percent'] ?? 0) }}" class="admin-input">
                    <p class="mt-1 text-xs text-slate-500">Yalnızca “Site + markup” modunda.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Maliyet + kâr (KDV hariç)</p>
                    <p class="text-xs text-slate-500 -mt-1">Formül: (maliyet + sabit) ÷ (1 − komisyon − diğer − hedef kâr). Maliyet üründe yoksa site fiyatına düşer.</p>

                    <div>
                        <label class="admin-label">Komisyon (%)</label>
                        <input type="number" step="0.01" min="0" max="99" name="settings[commission_percent]" value="{{ old('settings.commission_percent', $settings['commission_percent'] ?? 15) }}" class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Diğer oran (%)</label>
                        <input type="number" step="0.01" min="0" max="99" name="settings[other_fee_percent]" value="{{ old('settings.other_fee_percent', $settings['other_fee_percent'] ?? 0) }}" class="admin-input">
                        <p class="mt-1 text-xs text-slate-500">Hizmet bedeli, stopaj vb. oransal pay.</p>
                    </div>
                    <div>
                        <label class="admin-label">Hedef kâr (%)</label>
                        <input type="number" step="0.01" min="0" max="99" name="settings[target_margin_percent]" value="{{ old('settings.target_margin_percent', $settings['target_margin_percent'] ?? 10) }}" class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Sabit gider (₺)</label>
                        <input type="number" step="0.01" min="0" name="settings[fixed_fee]" value="{{ old('settings.fixed_fee', $settings['fixed_fee'] ?? 0) }}" class="admin-input">
                        <p class="mt-1 text-xs text-slate-500">Sabit kargo / paketleme; yoksa 0.</p>
                    </div>
                </div>

                <div>
                    <label class="admin-label">Stok güvenlik payı (%)</label>
                    <input type="number" step="1" min="0" max="100" name="settings[stock_buffer_percent]" value="{{ old('settings.stock_buffer_percent', $settings['stock_buffer_percent'] ?? 5) }}" class="admin-input">
                </div>

                @if($channel->key === 'n11')
                    <div>
                        <label class="admin-label">N11 kargo şablonu</label>
                        <input type="text" name="settings[shipment_template]" value="{{ old('settings.shipment_template', $settings['shipment_template'] ?? '') }}" class="admin-input" placeholder="Hesabım > Teslimat Bilgilerim şablon adı">
                        <p class="mt-1 text-xs text-slate-500">Paneldeki teslimat şablon adıyla birebir aynı olmalı.</p>
                    </div>
                    <div>
                        <label class="admin-label">N11 entegratör adı</label>
                        <input type="text" name="settings[integrator]" value="{{ old('settings.integrator', $settings['integrator'] ?? 'KosarTicaret') }}" class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Hazırlık günü</label>
                        <input type="number" step="1" min="1" max="30" name="settings[preparing_day]" value="{{ old('settings.preparing_day', $settings['preparing_day'] ?? 2) }}" class="admin-input">
                    </div>
                @endif

                <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="settings[auto_sync_stock]" value="1" @checked(old('settings.auto_sync_stock', $settings['auto_sync_stock'] ?? false)) class="rounded border-slate-300">
                    Otomatik stok sync (Faz 4)
                </label>
                <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="settings[auto_sync_price]" value="1" @checked(old('settings.auto_sync_price', $settings['auto_sync_price'] ?? false)) class="rounded border-slate-300">
                    Otomatik fiyat sync (Faz 4)
                </label>
            </div>

            <div class="admin-card p-6">
                <h3 class="admin-section-title" style="margin-top:0">Bağlantı testi</h3>
                <p class="text-sm text-slate-600 mb-4">Credential kaydı ve temel erişim kontrolü.</p>
                <button type="submit" formaction="{{ route('admin.integrations.marketplace.channels.test', $channel) }}" formmethod="post" class="admin-btn admin-btn-secondary w-full">Bağlantıyı test et</button>
                @if($channel->last_error)
                    <p class="text-sm text-red-600 mt-3">{{ $channel->last_error }}</p>
                @endif
            </div>
        </div>
    </form>
@endsection
