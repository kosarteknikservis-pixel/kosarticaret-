@php
    $chips = [];
    if (request()->filled('q')) {
        $chips[] = ['label' => __('shop.filter_search', ['q' => request('q')]), 'remove' => request()->except('q', 'page')];
    }
    if (request()->filled('marka') && !empty($brands)) {
        $brandName = $brands->firstWhere('slug', request('marka'))?->name ?? request('marka');
        $chips[] = ['label' => $brandName, 'remove' => request()->except('marka', 'page')];
    }
    if (request()->filled('min')) {
        $chips[] = ['label' => 'Min '.request('min').' ₺', 'remove' => request()->except('min', 'page')];
    }
    if (request()->filled('max')) {
        $chips[] = ['label' => 'Max '.request('max').' ₺', 'remove' => request()->except('max', 'page')];
    }
    if (request()->boolean('stokta')) {
        $chips[] = ['label' => __('shop.filter_in_stock'), 'remove' => request()->except('stokta', 'page')];
    }
    if (request()->filled('siralama')) {
        $sortLabels = [
            'fiyat-artan' => __('shop.sort_price_asc'),
            'fiyat-azalan' => __('shop.sort_price_desc'),
            'isim' => __('shop.sort_name'),
        ];
        $chips[] = ['label' => $sortLabels[request('siralama')] ?? request('siralama'), 'remove' => request()->except('siralama', 'page')];
    }
@endphp

@if(count($chips) > 0)
    <div class="flex flex-wrap items-center gap-2 mb-4" aria-label="{{ __('shop.active_filters') }}">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('shop.active_filters') }}:</span>
        @foreach($chips as $chip)
            <a href="{{ request()->url() }}?{{ http_build_query($chip['remove']) }}" class="shop-filter-chip">
                {{ $chip['label'] }}
                <x-shop.icon name="x" class="w-3.5 h-3.5" />
            </a>
        @endforeach
        <a href="{{ request()->url() }}" class="text-xs font-semibold text-slate-500 hover:text-brand-700 ml-1">{{ __('shop.clear_filters') }}</a>
    </div>
@endif
