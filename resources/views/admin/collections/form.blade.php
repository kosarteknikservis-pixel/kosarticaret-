@extends('layouts.admin')

@section('title', $collection->exists ? $collection->name : 'Yeni etiket')

@section('content')
    <x-admin.page-header :title="$collection->exists ? $collection->name : 'Yeni etiket'" subtitle="Eşleşme ürünün kayıtlı özelliğinden okunur. Teknik tablo değişmez.">
        @if($collection->exists)
            <x-slot:actions>
                <a href="{{ route('admin.collections.preview', $collection) }}" class="admin-btn admin-btn-secondary">Önizle</a>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <form method="post" action="{{ $collection->exists ? route('admin.collections.update', $collection) : route('admin.collections.store') }}" class="admin-card p-5 max-w-3xl space-y-4">
        @csrf
        @if($collection->exists)
            @method('PUT')
        @endif

        <label class="block text-sm">
            <span class="font-medium text-slate-800">Ad</span>
            <input name="name" value="{{ old('name', $collection->name) }}" class="admin-input mt-1" required>
        </label>
        <label class="block text-sm">
            <span class="font-medium text-slate-800">Adres</span>
            <input name="slug" value="{{ old('slug', $collection->slug) }}" class="admin-input mt-1" placeholder="1-hp-hidrofor">
        </label>
        <label class="block text-sm">
            <span class="font-medium text-slate-800">Hedef kelime</span>
            <input name="target_keyword" value="{{ old('target_keyword', $collection->target_keyword) }}" class="admin-input mt-1">
        </label>
        <label class="block text-sm">
            <span class="font-medium text-slate-800">Kategori ağacı</span>
            <select name="category_id" class="admin-input mt-1" required>
                <option value="">Seçin</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $collection->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="grid sm:grid-cols-2 gap-4">
            <label class="block text-sm">
                <span class="font-medium text-slate-800">Motor gücü (HP)</span>
                <input name="motor_hp" value="{{ old('motor_hp', $collection->rules['motor_hp'] ?? '') }}" class="admin-input mt-1" inputmode="decimal" placeholder="1">
            </label>
            <label class="block text-sm">
                <span class="font-medium text-slate-800">Faz</span>
                <select name="phase" class="admin-input mt-1">
                    <option value="">Yok</option>
                    <option value="monofaze" @selected(old('phase', $collection->rules['phase'] ?? '') === 'monofaze')>Monofaze</option>
                    <option value="trifaze" @selected(old('phase', $collection->rules['phase'] ?? '') === 'trifaze')>Trifaze</option>
                </select>
            </label>
        </div>
        <label class="block text-sm">
            <span class="font-medium text-slate-800">Durum</span>
            <select name="status" class="admin-input mt-1">
                <option value="draft" @selected(old('status', $collection->status) === 'draft')>Taslak</option>
                <option value="noindex" @selected(old('status', $collection->status) === 'noindex')>Noindex</option>
                <option value="index" @selected(old('status', $collection->status) === 'index')>Index</option>
            </select>
            <span class="block text-xs text-slate-500 mt-1">Index, en az 6 uygun ürün varken kalır. Yeni etiket taslak açılır.</span>
        </label>
        @error('status')
            <p class="text-sm text-amber-800">{{ $message }}</p>
        @enderror
        @error('motor_hp')
            <p class="text-sm text-amber-800">{{ $message }}</p>
        @enderror
        <button type="submit" class="admin-btn admin-btn-primary">Kaydet</button>
    </form>

    @if($collection->exists)
        <form method="post" action="{{ route('admin.collections.membership', $collection) }}" class="admin-card p-5 max-w-3xl mt-6 space-y-3">
            @csrf
            <h2 class="font-semibold text-slate-900">Elle dahil / hariç</h2>
            <p class="text-sm text-slate-600">Stok kodu veya ürün numarası. Hariç, kuralın önüne geçer.</p>
            <div class="flex flex-col sm:flex-row gap-2">
                <input name="product_ref" value="{{ old('product_ref') }}" class="admin-input" placeholder="SKU veya ürün no">
                <button name="action" value="include" class="admin-btn admin-btn-secondary">Dahil et</button>
                <button name="action" value="exclude" class="admin-btn admin-btn-secondary">Hariç tut</button>
                <button name="action" value="clear" class="admin-btn admin-btn-secondary">Kurala bırak</button>
            </div>
            @error('product_ref')
                <p class="text-sm text-amber-800">{{ $message }}</p>
            @enderror
        </form>

        @php
            $groups = [
                'Eşleşen' => $preview['matched'] ?? [],
                'Güç veya faz alanı boş' => $preview['undecided'] ?? [],
                'Belirsiz güç' => $preview['uncertain'] ?? [],
                'Adında kelime var, kurala uymuyor' => $preview['nameOnly'] ?? [],
            ];
        @endphp
        @foreach($groups as $title => $rows)
            <section class="admin-card p-5 max-w-3xl mt-6">
                <h2 class="font-semibold text-slate-900">{{ $title }} <span class="text-slate-500 font-normal">({{ count($rows) }})</span></h2>
                <div class="mt-3 max-h-80 overflow-y-auto">
                    <table class="admin-table">
                        <tbody>
                            @forelse(array_slice($rows, 0, 80) as $row)
                                <tr>
                                    <td class="text-sm">
                                        <span class="text-slate-400">{{ $row['id'] }}</span>
                                        {{ $row['name'] }}
                                        @if(!empty($row['evidence']))
                                            <span class="block text-xs text-slate-500">{{ $row['evidence'] }}</span>
                                        @endif
                                        @if(!empty($row['reason']))
                                            <span class="block text-xs text-slate-500">{{ $row['reason'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-sm text-slate-500">Kayıt yok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    @endif
@endsection
