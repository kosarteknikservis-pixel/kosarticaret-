@php
    /** @var list<array<string, mixed>> $purchaseOptions */
    $purchaseOptions = $purchaseOptions ?? [];
@endphp
@if(count($purchaseOptions) > 1)
    <div class="shop-pdp-options"
         data-pdp-options
         data-havale-percent="{{ $havalePercent ?? 0 }}"
         data-havale-note="{{ $havaleNote ?? '' }}"
         role="group"
         aria-label="Model seçenekleri">
        <p class="shop-pdp-options__label">Seçenekler</p>
        <div class="shop-pdp-options__list">
            @foreach($purchaseOptions as $option)
                <button type="button"
                        class="shop-pdp-option {{ ! empty($option['selected']) ? 'is-selected' : '' }}"
                        data-pdp-option
                        data-option-key="{{ $option['key'] }}"
                        data-option-slug="{{ $option['slug'] }}"
                        data-option-sku="{{ $option['sku'] }}"
                        data-option-id="{{ $option['product_id'] }}"
                        data-option-price="{{ number_format((float) $option['price'], 2, '.', '') }}"
                        data-option-price-formatted="{{ $option['price_formatted'] }}"
                        data-option-compare="{{ $option['compare_at_price'] !== null ? number_format((float) $option['compare_at_price'], 2, '.', '') : '' }}"
                        data-option-compare-formatted="{{ $option['compare_at_formatted'] ?? '' }}"
                        data-option-havale="{{ $option['havale_price'] !== null ? number_format((float) $option['havale_price'], 2, '.', '') : '' }}"
                        data-option-havale-formatted="{{ $option['havale_formatted'] ?? '' }}"
                        data-option-stock="{{ (int) $option['stock'] }}"
                        data-option-in-stock="{{ ! empty($option['in_stock']) ? '1' : '0' }}"
                        aria-pressed="{{ ! empty($option['selected']) ? 'true' : 'false' }}">
                    {{ $option['label'] }}
                </button>
            @endforeach
        </div>
    </div>
@endif
