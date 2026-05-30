@php
    $installmentUrl = route('products.installments', $product);
    $initialAmount = (float) $product->price;
@endphp

<div class="shop-pdp-installments"
     data-installments
     data-installments-url="{{ $installmentUrl }}"
     data-installments-qty="#pdp-qty"
     data-installments-amount="{{ $initialAmount }}">

    <div class="shop-pdp-installments__toolbar">
        <p class="shop-pdp-installments__intro">{{ __('shop.installments_intro') }}</p>
        @if($installmentTable['provider_label'] ?? null)
            <span class="shop-pdp-installments__provider">{{ $installmentTable['provider_label'] }}</span>
        @endif
    </div>

    <p class="shop-pdp-installments__amount">
        {{ __('shop.installments_for_amount') }}
        <strong data-installments-amount-label>{{ number_format($installmentTable['amount'], 2, ',', '.') }} ₺</strong>
    </p>

    <div data-installments-body>
        @include('shop.partials.pdp-installments-table', ['installmentTable' => $installmentTable])
    </div>

    <p class="shop-pdp-installments__disclaimer">{{ __('shop.installments_disclaimer') }}</p>
</div>
