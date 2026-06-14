@extends('layouts.admin')
@section('title', 'Barkod import')

@section('content')
    <x-admin.page-header title="Barkod & lojistik import" subtitle="CSV ile toplu barkod, ağırlık ve ölçü güncelleme" />

    <x-admin.integrations-nav active="marketplace-logistics" />

    <div class="admin-card p-6 sm:p-8 max-w-3xl space-y-5">
        <p class="text-sm text-slate-600 leading-relaxed">
            CSV ayırıcı: noktalı virgül (<code>;</code>). Zorunlu sütun: <strong>sku</strong>.
            Desteklenen sütunlar: barcode, weight_kg, width_cm, height_cm, depth_cm, vat_rate.
            Türkçe başlıklar da kabul edilir (barkod, agirlik, genislik…).
        </p>

        <form method="post" enctype="multipart/form-data" action="{{ route('admin.integrations.marketplace.logistics-import.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="admin-label">CSV dosyası</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" required class="admin-input">
                @error('csv_file')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button class="admin-btn admin-btn-primary px-5 py-2.5">İçe aktar</button>
        </form>

        <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-xs font-mono text-slate-600 overflow-x-auto">
            sku;barcode;weight_kg;width_cm;height_cm;depth_cm;vat_rate<br>
            wnp70;8690000000001;12.5;30;40;35;20<br>
            abc123;8690000000002;2.1;10;10;10;20
        </div>
    </div>
@endsection
