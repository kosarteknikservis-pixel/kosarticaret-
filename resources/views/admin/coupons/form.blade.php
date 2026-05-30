@extends('layouts.admin')
@section('title', $coupon->exists ? 'Kupon düzenle' : 'Yeni kupon')

@section('content')
    <x-admin.page-header :title="$coupon->exists ? 'Kupon düzenle' : 'Yeni kupon'" subtitle="Örn: KOSAR10" />
    <form method="post" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="admin-card p-6 sm:p-8 max-w-md space-y-4">
        @csrf @if($coupon->exists) @method('PUT') @endif
        <div><label class="admin-label">Kod</label><input name="code" value="{{ old('code', $coupon->code) }}" required class="admin-input uppercase font-mono font-bold"></div>
        <div><label class="admin-label">İndirim (%)</label><input type="number" name="percent" min="1" max="100" value="{{ old('percent', $coupon->percent ?? 10) }}" required class="admin-input"></div>
        <div><label class="admin-label">Min. sepet tutarı (₺)</label><input type="number" step="0.01" name="min_amount" value="{{ old('min_amount', $coupon->min_amount) }}" class="admin-input"></div>
        <div><label class="admin-label">Bitiş tarihi</label><input type="date" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d')) }}" class="admin-input"></div>
        <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $coupon->active ?? true))> Aktif</label>
        <x-admin.form-footer :delete-action="$coupon->exists ? route('admin.coupons.destroy', $coupon) : null" />
    </form>
@endsection
