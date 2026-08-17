@php
    $guide = $hub['guide'] ?? null;
    $siblings = $hub['siblings'] ?? collect();
    $cross = $hub['cross'] ?? collect();
    $hasLinks = $guide || $siblings->isNotEmpty() || $cross->isNotEmpty() || $product->brand;
@endphp

@if($hasLinks)
    <nav class="shop-pdp-hub shop-reveal" aria-labelledby="pdp-hub-heading">
        <h2 id="pdp-hub-heading" class="shop-pdp-hub__title">{{ __('shop.pdp_hub_title') }}</h2>
        <ul class="shop-pdp-hub__list">
            @if($guide)
                <li>
                    <a href="{{ $guide['url'] }}" class="shop-pdp-hub__link">
                        {{ $guide['has_guide'] ? __('shop.pdp_hub_guide', ['category' => $guide['label']]) : __('shop.pdp_hub_category', ['category' => $guide['label']]) }}
                    </a>
                </li>
            @endif
            @if($product->brand)
                <li>
                    <a href="{{ route('brands.show', $product->brand) }}" class="shop-pdp-hub__link">
                        {{ __('shop.pdp_hub_brand', ['brand' => $product->brand->name]) }}
                    </a>
                </li>
            @endif
            @foreach($siblings as $sibling)
                <li>
                    <a href="{{ $sibling->storefrontUrl() }}" class="shop-pdp-hub__link">{{ $sibling->name }}</a>
                </li>
            @endforeach
            @foreach($cross as $relatedCategory)
                <li>
                    <a href="{{ $relatedCategory->storefrontUrl() }}" class="shop-pdp-hub__link">{{ $relatedCategory->name }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
