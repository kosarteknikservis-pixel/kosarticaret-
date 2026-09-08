@extends('layouts.admin')
@section('title', 'Rakip fiyat kuralları')

@section('content')
    <x-admin.page-header title="Rakip fiyat kuralları" subtitle="Öneri hesaplama ayarları — otomatik uygulama kapalı tutulur">
        <x-slot:actions>
            <a href="{{ route('admin.competitor-pricing.index') }}" class="admin-btn admin-btn-secondary">Teklif listesi</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="post" action="{{ route('admin.competitor-pricing.settings.update') }}" class="admin-card p-6 sm:p-8 max-w-md space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="admin-label">Kural adı</label>
            <input name="name" value="{{ old('name', $rule->name) }}" class="admin-input" maxlength="120">
        </div>

        <div>
            <label class="admin-label">Rakipten yüzde kaç altında?</label>
            <input type="number" name="undercut_percent" step="0.01" min="0" max="50" value="{{ old('undercut_percent', $rule->undercut_percent) }}" required class="admin-input">
            <p class="text-xs text-slate-500 mt-1">Örn: 2 → onaylı rakip min fiyatın %2 altı önerilir.</p>
            @error('undercut_percent') <p class="admin-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label">Taban fiyat (₺) — opsiyonel</label>
            <input type="number" name="min_price" step="0.01" min="0" value="{{ old('min_price', $rule->min_price) }}" class="admin-input" placeholder="Öneri bunun altına inmesin">
            @error('min_price') <p class="admin-error">{{ $message }}</p> @enderror
        </div>

        <label class="admin-checkbox">
            <input type="checkbox" name="keep_compare_at" value="1" @checked(old('keep_compare_at', $rule->keep_compare_at))>
            İndirimde eski fiyatı “üstü çizili” (compare_at) olarak sakla
        </label>

        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
            Otomatik uygulama bilinçli olarak kapalıdır. Fiyat yalnızca panelden “Uygula” ile değişir.
        </p>

        <p class="text-xs {{ !empty($dataforseoReady) ? 'text-teal-800 bg-teal-50 border-teal-100' : 'text-red-800 bg-red-50 border-red-100' }} border rounded-lg px-3 py-2">
            DataForSEO (Google Shopping): {{ !empty($dataforseoReady) ? 'bağlı' : 'eksik — DATAFORSEO_USERNAME / PASSWORD gerekli' }}
        </p>

        <x-admin.form-footer>Kaydet</x-admin.form-footer>
    </form>
@endsection
