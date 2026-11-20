@props([
    'geo' => [],
    'omitShortAnswer' => false,
])

@php
    $showShort = ! $omitShortAnswer && ! empty($geo['short_answer']);
    $band = $geo['price_band'] ?? null;
    $hasNumericRange = is_array($band) && isset($band['from'], $band['to']);
    $hasBandNote = is_array($band) && filled($band['note'] ?? null);
    $hasGuideCta = ! empty($geo['guide_cta']['url'] ?? null) && ! empty($geo['guide_cta']['label'] ?? null);
    $hasTable = ! empty($geo['selection_table']['rows']);
    $showBlock = $showShort || $hasNumericRange || $hasBandNote || $hasGuideCta || $hasTable;
@endphp

@if($showBlock)
<section class="shop-geo-block shop-reveal" @if($showShort) aria-label="{{ __('shop.geo_short_answer_label') }}" @endif>
    @if($showShort)
    <div class="shop-geo-block__answer">
        <p class="shop-geo-block__label">{{ __('shop.geo_short_answer_label') }}</p>
        <p class="shop-geo-block__text">{{ $geo['short_answer'] }}</p>
    </div>
    @endif

    @if($hasNumericRange)
        <div class="shop-geo-block__price">
            <p class="shop-geo-block__label">{{ __('shop.geo_price_band_label') }}</p>
            <p class="shop-geo-block__price-value">
                {{ number_format($band['from'], 0, ',', '.') }}–{{ number_format($band['to'], 0, ',', '.') }} {{ $band['currency'] }}
            </p>
            @if($hasBandNote)
                <p class="shop-geo-block__price-note">{{ $band['note'] }}</p>
            @endif
        </div>
    @elseif($hasBandNote)
        <div class="shop-geo-block__price shop-geo-block__price--note-only">
            <p class="shop-geo-block__price-note">{{ $band['note'] }}</p>
        </div>
    @endif

    @if($hasGuideCta)
        <p class="shop-geo-block__cta">
            <a href="{{ $geo['guide_cta']['url'] }}" class="shop-geo-block__cta-link">{{ $geo['guide_cta']['label'] }}</a>
        </p>
    @endif

    @if($hasTable)
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
