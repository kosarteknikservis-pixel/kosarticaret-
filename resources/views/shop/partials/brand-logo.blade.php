@php $logo = $brand->logoUrl(); @endphp
@if($logo)
    <img src="{{ $brand->logoUrl('brand-logo') }}" @if($srcset = $brand->logoSrcset()) srcset="{{ $srcset }}" sizes="10rem" @endif alt="{{ $brand->name }}" loading="lazy" decoding="async" class="{{ $class ?? 'shop-brand-logo' }}" width="160" height="64">
@else
    <span class="{{ $fallbackClass ?? 'shop-brand-logo-fallback' }}">{{ $brand->name }}</span>
@endif
