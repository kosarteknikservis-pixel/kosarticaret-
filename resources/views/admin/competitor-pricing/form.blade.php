@extends('layouts.admin')
@section('title', $offer->exists ? 'Rakip teklif düzenle' : 'Yeni rakip teklif')

@section('content')
    <x-admin.page-header
        :title="$offer->exists ? 'Rakip teklif düzenle' : 'Yeni rakip teklif'"
        subtitle="Doğru ürün eşleşmesini kaydedin; fiyat önerisi yalnızca onaylı tekliflerden hesaplanır"
    >
        <x-slot:actions>
            <a href="{{ route('admin.competitor-pricing.index') }}" class="admin-btn admin-btn-secondary">Listeye dön</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if($offer->exists && isset($suggestion) && $suggestion)
        <div class="admin-card p-4 mb-5 max-w-2xl">
            <p class="text-sm text-slate-600">
                Bizim fiyat:
                <span class="font-semibold text-slate-900">{{ number_format((float) $suggestion['our_price'], 2, ',', '.') }} ₺</span>
                @if($suggestion['competitor_min'])
                    · Onaylı rakip min:
                    <span class="font-semibold">{{ number_format((float) $suggestion['competitor_min'], 2, ',', '.') }} ₺</span>
                @endif
                @if($suggestion['suggested'])
                    · Öneri (%{{ rtrim(rtrim(number_format((float) $rule->undercut_percent, 2, ',', '.'), '0'), ',') }}):
                    <span class="font-semibold text-teal-800">{{ number_format((float) $suggestion['suggested'], 2, ',', '.') }} ₺</span>
                @endif
            </p>
            @if($suggestion['reason'])
                <p class="text-xs text-slate-500 mt-1">{{ $suggestion['reason'] }}</p>
            @endif
        </div>
    @endif

    <form
        method="post"
        action="{{ $offer->exists ? route('admin.competitor-pricing.update', $offer) : route('admin.competitor-pricing.store') }}"
        class="admin-card p-6 sm:p-8 max-w-2xl space-y-5"
    >
        @csrf
        @if($offer->exists) @method('PUT') @endif

        <div>
            <label class="admin-label">Ürün</label>
            <input type="search" id="competitor-product-filter" class="admin-input text-sm mb-2" placeholder="İsim veya SKU ara…" autocomplete="off">
            <select name="product_id" id="competitor-product-select" required class="admin-input" size="8">
                <option value="">Ürün seçin…</option>
                @foreach($products as $p)
                    <option
                        value="{{ $p->id }}"
                        data-label="{{ Str::lower($p->name.' '.$p->sku) }}"
                        @selected((string) old('product_id', $offer->product_id ?? $product?->id) === (string) $p->id)
                    >
                        {{ $p->name }}@if($p->sku) · {{ $p->sku }}@endif — {{ number_format((float) $p->price, 2, ',', '.') }} ₺
                    </option>
                @endforeach
            </select>
            @error('product_id') <p class="admin-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Rakip adı</label>
                <input name="competitor_name" value="{{ old('competitor_name', $offer->competitor_name) }}" required class="admin-input" placeholder="Örn: Rakip Mağaza">
                @error('competitor_name') <p class="admin-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label">Eşleşme durumu</label>
                <select name="match_status" class="admin-input">
                    @foreach(['pending' => 'Onay bekliyor', 'approved' => 'Onaylı eşleşme', 'rejected' => 'Reddedildi'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('match_status', $offer->match_status ?? 'pending') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="admin-label">Rakip ürün URL</label>
            <input type="url" name="competitor_url" value="{{ old('competitor_url', $offer->competitor_url) }}" required class="admin-input" placeholder="https://…">
            @error('competitor_url') <p class="admin-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label">Elle fiyat (₺) — çekim başarısızsa</label>
            <input type="number" step="0.01" min="0" name="last_price" value="{{ old('last_price', $offer->last_price) }}" class="admin-input">
            @if($offer->exists && $offer->competitor_title)
                <p class="text-xs text-slate-500 mt-1">Çekilen başlık: {{ $offer->competitor_title }}</p>
            @endif
        </div>

        <div>
            <label class="admin-label">Not</label>
            <textarea name="notes" rows="3" class="admin-input" placeholder="SKU / model eşleşme notu">{{ old('notes', $offer->notes) }}</textarea>
        </div>

        <label class="admin-checkbox">
            <input type="checkbox" name="active" value="1" @checked(old('active', $offer->active ?? true))>
            Aktif
        </label>

        <label class="admin-checkbox">
            <input type="checkbox" name="fetch_now" value="1" @checked(old('fetch_now', ! $offer->exists))>
            Kaydederken fiyatı şimdi çek
        </label>

        <x-admin.form-footer :delete-action="$offer->exists ? route('admin.competitor-pricing.destroy', $offer) : null" />
    </form>

    @if($offer->exists)
        <div class="flex flex-wrap gap-2 mt-4 max-w-2xl">
            <form method="post" action="{{ route('admin.competitor-pricing.fetch', $offer) }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary">Fiyatı şimdi çek</button>
            </form>
            @if($offer->match_status !== 'approved')
                <form method="post" action="{{ route('admin.competitor-pricing.approve', $offer) }}">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-secondary">Eşleşmeyi onayla</button>
                </form>
            @endif
            @if(isset($suggestion) && ($suggestion['can_apply'] ?? false))
                <form method="post" action="{{ route('admin.competitor-pricing.apply', $offer) }}" onsubmit="return confirm('Önerilen fiyat ürüne uygulansın mı?')">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-primary">Önerilen fiyatı uygula</button>
                </form>
            @endif
        </div>
    @endif
@endsection

@push('scripts')
<script>
(() => {
    const filter = document.getElementById('competitor-product-filter');
    const select = document.getElementById('competitor-product-select');
    if (!filter || !select) return;
    filter.addEventListener('input', () => {
        const q = filter.value.trim().toLowerCase();
        Array.from(select.options).forEach((opt, i) => {
            if (i === 0) return;
            const label = opt.getAttribute('data-label') || '';
            opt.hidden = q !== '' && !label.includes(q);
        });
    });
})();
</script>
@endpush
