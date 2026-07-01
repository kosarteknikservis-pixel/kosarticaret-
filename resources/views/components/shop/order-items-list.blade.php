@props([
    'items',
    'showUnitPrice' => false,
])

<ul {{ $attributes->class(['shop-order-items', 'shop-order-items--with-unit' => $showUnitPrice]) }}>
    @foreach($items as $item)
        <li class="shop-order-items__row">
            <div class="shop-order-items__body">
                <p class="shop-order-items__name">{{ $item->product_name }}</p>
                @if(filled($item->sku))
                    <p class="shop-order-items__sku">{{ $item->sku }}</p>
                @endif
            </div>
            <div class="shop-order-items__details">
                <div class="shop-order-items__detail">
                    <span class="shop-order-items__detail-label">Adet</span>
                    <span class="shop-order-items__detail-value">{{ $item->quantity }}</span>
                </div>
                @if($showUnitPrice)
                    <div class="shop-order-items__detail">
                        <span class="shop-order-items__detail-label">Birim</span>
                        <span class="shop-order-items__detail-value">{{ number_format((float) $item->unit_price, 2, ',', '.') }} ₺</span>
                    </div>
                @endif
                <div class="shop-order-items__detail shop-order-items__detail--total">
                    <span class="shop-order-items__detail-label">Tutar</span>
                    <span class="shop-order-items__detail-value">{{ number_format((float) $item->line_total, 2, ',', '.') }} ₺</span>
                </div>
            </div>
        </li>
    @endforeach
</ul>
