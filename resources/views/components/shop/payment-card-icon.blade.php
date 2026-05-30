@props(['brand', 'label', 'image' => null])

@php
    $svgBrands = ['visa', 'mastercard', 'paypal', 'amex', 'visa_electron', 'maestro', 'troy'];
    $useSvg = $image === null && in_array($brand, $svgBrands, true);
@endphp

<span {{ $attributes->merge(['class' => 'shop-pay-icon shop-pay-icon--'.e($brand)]) }} title="{{ $label }}" role="img" aria-label="{{ $label }}">
    @if($useSvg)
        <x-shop.payment-icon-svg :brand="$brand" />
    @elseif($image)
        <img src="{{ $image }}" alt="" class="shop-pay-icon__img" loading="lazy" decoding="async">
    @else
        <span class="shop-pay-icon__fallback" aria-hidden="true">{{ $label }}</span>
    @endif
</span>
