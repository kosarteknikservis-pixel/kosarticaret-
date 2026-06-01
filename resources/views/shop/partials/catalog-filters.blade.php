@php
    $hasActiveFilters = request()->filled('marka')
        || request()->filled('min')
        || request()->filled('max')
        || request()->boolean('stokta')
        || request()->filled('siralama');
@endphp

<form method="get" action="{{ request()->url() }}" class="shop-filter-panel">
    @foreach(request()->except(['marka', 'min', 'max', 'stokta', 'siralama', 'page']) as $key => $value)
        @if(is_array($value))
            @foreach($value as $v)
                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <p class="shop-filter-panel__title">
        <x-shop.icon name="grid" class="w-5 h-5 text-brand-600 shrink-0" />
        {{ __('shop.filters') }}
    </p>

    @if(!empty($brands) && $brands->isNotEmpty())
        <div class="shop-filter-field">
            <label>{{ __('shop.brands') }}</label>
            <select name="marka" class="shop-filter-control">
                <option value="">{{ __('shop.filter_all_brands') }}</option>
                @foreach($brands as $b)
                    <option value="{{ $b->slug }}" @selected(request('marka') === $b->slug)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="shop-filter-field">
        <label>{{ __('shop.filter_price') }}</label>
        <div class="shop-filter-price-grid">
            <input type="number" name="min" value="{{ request('min') }}" placeholder="Min" min="0" class="shop-filter-control">
            <input type="number" name="max" value="{{ request('max') }}" placeholder="Max" min="0" class="shop-filter-control">
        </div>
    </div>

    <label class="shop-filter-check">
        <input type="checkbox" name="stokta" value="1" @checked(request()->boolean('stokta')) class="rounded border-slate-300 text-brand-700 focus:ring-brand-500/30">
        <span class="font-medium text-slate-700">{{ __('shop.filter_in_stock') }}</span>
    </label>

    <div class="shop-filter-field">
        <label>{{ __('shop.sort') }}</label>
        <select name="siralama" class="shop-filter-control">
            <option value="">{{ __('shop.sort_newest') }}</option>
            <option value="fiyat-artan" @selected(request('siralama') === 'fiyat-artan')>{{ __('shop.sort_price_asc') }}</option>
            <option value="fiyat-azalan" @selected(request('siralama') === 'fiyat-azalan')>{{ __('shop.sort_price_desc') }}</option>
            <option value="isim" @selected(request('siralama') === 'isim')>{{ __('shop.sort_name') }}</option>
        </select>
    </div>

    <div class="shop-filter-actions">
        <button type="submit" class="btn-primary shop-filter-submit">{{ __('shop.apply_filters') }}</button>
        @if($hasActiveFilters)
            <a href="{{ request()->url() }}" class="shop-filter-clear">{{ __('shop.clear_filters') }}</a>
        @endif
    </div>
</form>
