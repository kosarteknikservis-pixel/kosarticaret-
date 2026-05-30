@php $logo = $brand->logoUrl(); @endphp
@if($logo)
    <img src="{{ $logo }}" alt="{{ $brand->name }}" loading="lazy" class="{{ $class ?? 'shop-brand-logo' }}" width="160" height="64">
@else
    <span class="{{ $fallbackClass ?? 'shop-brand-logo-fallback' }}">{{ $brand->name }}</span>
@endif
