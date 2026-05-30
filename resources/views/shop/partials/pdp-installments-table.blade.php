@if(! ($installmentTable['available'] ?? false))
    <div class="shop-pdp-installments__empty">
        <span class="shop-pdp-installments__empty-icon">
            <x-shop.icon name="credit-card" class="w-7 h-7" />
        </span>
        <p class="shop-pdp-installments__empty-title">{{ $installmentTable['message'] ?? __('shop.installments_not_configured') }}</p>
        @if(! \App\Support\PaymentGatewayConfig::isLive())
            <p class="shop-pdp-installments__empty-hint">{{ __('shop.installments_setup_hint') }}</p>
        @endif
    </div>
@else
    <div class="shop-installments-table-wrap">
        <table class="shop-installments-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('shop.installments_card') }}</th>
                    @foreach($installmentTable['columns'] as $count)
                        <th scope="col">
                            @if($count <= 1)
                                {{ __('shop.installments_single') }}
                            @else
                                {{ __('shop.installments_count', ['count' => $count]) }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($installmentTable['rows'] as $row)
                    <tr>
                        <th scope="row" class="shop-installments-table__bank">{{ $row['label'] }}</th>
                        @foreach($installmentTable['columns'] as $count)
                            @php $cell = $row['cells'][$count] ?? null; @endphp
                            <td>
                                @if($cell)
                                    <span class="shop-installments-table__monthly">{{ number_format($cell['monthly'], 2, ',', '.') }} ₺</span>
                                    @if($count > 1)
                                        <span class="shop-installments-table__total">{{ number_format($cell['total'], 2, ',', '.') }} ₺</span>
                                    @endif
                                @else
                                    <span class="shop-installments-table__na" aria-hidden="true">—</span>
                                    <span class="sr-only">{{ __('shop.installments_na') }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
