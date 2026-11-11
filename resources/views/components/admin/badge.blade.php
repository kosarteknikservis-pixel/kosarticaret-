@props([
    'tone' => 'muted', // success|warning|muted|danger|info
])

@php
    $toneClass = match ($tone) {
        'success' => 'admin-badge-success',
        'warning' => 'admin-badge-warning',
        'danger' => 'admin-badge-danger',
        'info' => 'admin-badge-info',
        default => 'admin-badge-muted',
    };
@endphp

<span {{ $attributes->class(['admin-badge', $toneClass]) }}>{{ $slot }}</span>
