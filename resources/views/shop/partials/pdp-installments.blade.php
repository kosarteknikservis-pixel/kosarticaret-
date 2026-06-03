@php
    $installmentUrl = route('products.installments', $product);
    $initialAmount = (float) $product->price;
    $paytrTableToken = \App\Support\PaymentGatewayConfig::paytrInstallmentTableToken();
    $paytrMerchantId = \App\Support\PaymentGatewayConfig::paytrMerchantId();
    $usesPaytrTable = \App\Support\PaymentGatewayConfig::activeProvider() === 'paytr'
        && $paytrTableToken !== ''
        && $paytrMerchantId !== '';
@endphp

<div class="shop-pdp-installments"
     data-installments
     data-installments-mode="{{ $usesPaytrTable ? 'paytr-table' : 'api' }}"
     @unless($usesPaytrTable)
         data-installments-url="{{ $installmentUrl }}"
     @endunless
     data-installments-qty="#pdp-qty"
     data-installments-amount="{{ $initialAmount }}"
     @if($usesPaytrTable)
         data-paytr-table-token="{{ $paytrTableToken }}"
         data-paytr-merchant-id="{{ $paytrMerchantId }}"
     @endif>

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
        @if($usesPaytrTable)
            <div class="shop-paytr-installments-shell">
                <div id="paytr_taksit_tablosu" class="shop-paytr-installments-table"></div>
            </div>
        @else
            @include('shop.partials.pdp-installments-table', ['installmentTable' => $installmentTable])
        @endif
    </div>

    <p class="shop-pdp-installments__disclaimer">{{ __('shop.installments_disclaimer') }}</p>
</div>
