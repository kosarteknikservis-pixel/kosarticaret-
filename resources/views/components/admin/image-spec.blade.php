@props(['key' => null, 'bannerType' => null, 'compact' => false])

@php
    $spec = $bannerType
        ? \App\Support\ImageUploadSpec::forBannerType($bannerType)
        : ($key ? \App\Support\ImageUploadSpec::get($key) : null);
@endphp

@if($spec)
    <div {{ $attributes->merge(['class' => 'admin-image-spec'.($compact ? ' admin-image-spec--compact' : '')]) }} role="note">
        <p class="admin-image-spec__size">
            <span class="admin-image-spec__badge">Ölçü</span>
            <strong class="admin-image-spec__px">{{ $spec['ratio_label'] }}</strong>
            <span class="admin-image-spec__ratio">({{ round($spec['width'] / max(1, $spec['height']), 2) }}:1)</span>
        </p>
        @unless($compact)
            <p class="admin-image-spec__hint">{{ $spec['hint'] }}</p>
            <p class="admin-image-spec__meta">{{ $spec['formats'] }} · en fazla {{ $spec['max_mb'] }} MB</p>
            @if(!empty($spec['safe_zone']))
                <p class="admin-image-spec__safe">{{ $spec['safe_zone'] }}</p>
            @endif
        @endunless
    </div>
@endif
