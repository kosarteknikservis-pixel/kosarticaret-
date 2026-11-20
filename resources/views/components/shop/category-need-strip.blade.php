@props([
    'items' => [],
    'label' => null,
])

@php
    $items = collect($items)
        ->filter(fn ($item) => filled($item['href'] ?? null) && filled($item['label'] ?? null))
        ->values();
@endphp

@if($items->isNotEmpty())
<nav class="shop-need-strip shop-reveal" aria-label="{{ $label ?: __('shop.need_strip_label') }}">
    @if(filled($label))
        <p class="shop-need-strip__label">{{ $label }}</p>
    @endif
    <ul class="shop-need-strip__list">
        @foreach($items as $item)
            <li>
                <a href="{{ $item['href'] }}" class="shop-need-strip__link">
                    <span class="shop-need-strip__text">{{ $item['label'] }}</span>
                    <span class="shop-need-strip__chevron" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
@endif
