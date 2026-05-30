@extends('layouts.admin')
@section('title', $promotion->exists ? 'Kampanya düzenle' : 'Yeni kampanya')

@section('content')
    <x-admin.page-header :title="$promotion->exists ? 'Kampanya düzenle' : 'Yeni kampanya'" />
    <form method="post" action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}" class="admin-card p-6 sm:p-8 max-w-xl space-y-4">
        @csrf @if($promotion->exists) @method('PUT') @endif
        <div><label class="admin-label">Ad</label><input name="name" value="{{ old('name', $promotion->name) }}" required class="admin-input"></div>
        <div><label class="admin-label">Tip</label>
            <select name="type" class="admin-input">
                @foreach(['percent'=>'Yüzde indirim','fixed'=>'Sabit TL','free_shipping'=>'Ücretsiz kargo','buy_x_get_y'=>'X al Y bedava'] as $k=>$l)
                    <option value="{{ $k }}" @selected(old('type', $promotion->type)===$k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="admin-label">Değer (% veya TL)</label><input type="number" step="0.01" name="value" value="{{ old('value', $promotion->value) }}" class="admin-input"></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="admin-label">Al (X)</label><input type="number" name="buy_qty" value="{{ old('buy_qty', $promotion->buy_qty) }}" class="admin-input"></div>
            <div><label class="admin-label">Bedava (Y)</label><input type="number" name="free_qty" value="{{ old('free_qty', $promotion->free_qty) }}" class="admin-input"></div>
        </div>
        <div><label class="admin-label">Min. sepet (₺)</label><input type="number" step="0.01" name="min_cart" value="{{ old('min_cart', $promotion->min_cart) }}" class="admin-input"></div>
        <div><label class="admin-label">Öncelik</label><input type="number" name="priority" value="{{ old('priority', $promotion->priority ?? 0) }}" class="admin-input max-w-xs"></div>
        <label class="admin-checkbox"><input type="checkbox" name="auto_apply" value="1" @checked(old('auto_apply', $promotion->auto_apply ?? true))> Otomatik uygula</label>
        <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $promotion->active ?? true))> Aktif</label>
        <x-admin.form-footer :delete-action="$promotion->exists ? route('admin.promotions.destroy', $promotion) : null" />
    </form>
@endsection
