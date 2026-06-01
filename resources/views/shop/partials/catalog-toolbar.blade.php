@props(['products' => null, 'total' => 0])

@php
    $paginator = $products;
    $total = $paginator?->total() ?? (int) $total;
@endphp

<div class="shop-catalog-toolbar">
    <p class="shop-catalog-toolbar__count">
        @if($paginator && $paginator->firstItem() && $paginator->lastItem())
            {{ __('shop.results_range', [
                'from' => number_format($paginator->firstItem(), 0, ',', '.'),
                'to' => number_format($paginator->lastItem(), 0, ',', '.'),
                'total' => number_format($total, 0, ',', '.'),
            ]) }}
        @else
            <strong>{{ number_format($total, 0, ',', '.') }}</strong>
            {{ __('shop.results_count') }}
        @endif
    </p>
    <div class="shop-catalog-toolbar__actions">
        <button type="button" id="filter-drawer-open" class="shop-filter-trigger lg:hidden">
            <x-shop.icon name="grid" class="w-4 h-4" />
            {{ __('shop.filters') }}
        </button>
        <form method="get" id="catalog-sort-form" class="shop-catalog-sort">
            @foreach(request()->except(['siralama', 'page']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="catalog-sort" class="sr-only">{{ __('shop.sort') }}</label>
            <select id="catalog-sort" name="siralama" data-auto-submit class="shop-filter-control shop-catalog-sort__select">
                <option value="">{{ __('shop.sort_newest') }}</option>
                <option value="fiyat-artan" @selected(request('siralama') === 'fiyat-artan')>{{ __('shop.sort_price_asc') }}</option>
                <option value="fiyat-azalan" @selected(request('siralama') === 'fiyat-azalan')>{{ __('shop.sort_price_desc') }}</option>
                <option value="isim" @selected(request('siralama') === 'isim')>{{ __('shop.sort_name') }}</option>
            </select>
        </form>
    </div>
</div>
