@props([
    'items',
    'showUnitPrice' => false,
])

<ul {{ $attributes->class(['shop-order-items']) }}>
    @foreach($items as $item)
        <li class="shop-order-items__row">
            <div class="shop-order-items__main">
                <p class="shop-order-items__name">{{ $item->product_name }}</p>
                <p class="shop-order-items__meta">
                    {{ $item->quantity }} adet
                    @if($showUnitPrice)
                        · {{ number_format((float) $item->unit_price, 2, ',', '.') }} ₺
                    @endif
                    @if(filled($item->sku))
                        · {{ $item->sku }}
                    @endif
                </p>
            </div>
            <p class="shop-order-items__price">{{ number_format((float) $item->line_total, 2, ',', '.') }} ₺</p>
        </li>
    @endforeach
</ul>
