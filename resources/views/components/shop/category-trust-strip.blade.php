@props(['items' => []])

@if(!empty($items))
<section class="shop-category-trust shop-reveal" aria-label="Satın alma güvencesi">
    <ul class="shop-category-trust__list">
        @foreach($items as $item)
            <li class="shop-category-trust__item">
                <span class="shop-category-trust__icon" aria-hidden="true">
                    <x-shop.icon :name="$item['icon'] ?? 'check'" class="w-5 h-5" />
                </span>
                <span class="shop-category-trust__label">{{ $item['label'] ?? '' }}</span>
            </li>
        @endforeach
    </ul>
</section>
@endif
