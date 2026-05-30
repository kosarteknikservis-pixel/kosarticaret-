@props(['rating' => 0, 'count' => 0, 'size' => 'sm'])

@php
    $starClass = $size === 'lg' ? 'text-base' : 'text-sm';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }} aria-label="{{ number_format($rating, 1) }} / 5">
    <span class="inline-flex {{ $starClass }} leading-none" aria-hidden="true">
        @for($i = 1; $i <= 5; $i++)
            <span class="{{ $rating >= $i - 0.3 ? 'text-amber-400' : 'text-slate-200' }}">★</span>
        @endfor
    </span>
    <span class="font-semibold text-slate-700 {{ $size === 'lg' ? 'text-sm' : 'text-xs' }}">{{ number_format($rating, 1) }}</span>
    @if($count > 0)
        <span class="text-slate-500 {{ $size === 'lg' ? 'text-sm' : 'text-xs' }}">({{ $count }} {{ __('shop.reviews') }})</span>
    @endif
</div>
