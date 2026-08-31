@props(['geo' => []])

@if(!empty($geo['short_answer']))
<section class="shop-geo-block shop-reveal" aria-label="{{ __('shop.geo_short_answer_label') }}">
    <div class="shop-geo-block__answer">
        <p class="shop-geo-block__label">{{ __('shop.geo_short_answer_label') }}</p>
        <p class="shop-geo-block__text">{{ $geo['short_answer'] }}</p>
    </div>

    @if(!empty($geo['price_band']))
        @php($band = $geo['price_band'])
        <div class="shop-geo-block__price">
            <p class="shop-geo-block__label">{{ __('shop.geo_price_band_label') }}</p>
            <p class="shop-geo-block__price-value">
                {{ number_format($band['from'], 0, ',', '.') }}–{{ number_format($band['to'], 0, ',', '.') }} {{ $band['currency'] }}
            </p>
            @if(filled($band['note'] ?? null))
                <p class="shop-geo-block__price-note">{{ $band['note'] }}</p>
            @endif
            @if(!empty($geo['guide_cta']))
                <p class="shop-geo-block__cta">
                    <a href="{{ $geo['guide_cta']['url'] }}" class="shop-geo-block__cta-link">{{ $geo['guide_cta']['label'] }}</a>
                </p>
            @endif
        </div>
    @endif

    @if(!empty($geo['selection_table']['rows']))
        @php($table = $geo['selection_table'])
        <div class="shop-geo-block__table-wrap">
            <h2 class="shop-geo-block__table-title">{{ $table['title'] }}</h2>
            <div class="shop-geo-block__table-scroll">
                <table class="shop-geo-block__table">
                    @if(!empty($table['headers']))
                        <thead>
                            <tr>
                                @foreach($table['headers'] as $header)
                                    <th scope="col">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                    @endif
                    <tbody>
                        @foreach($table['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>
@endif
