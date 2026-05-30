@props([
    'field',
    'label' => 'AI ile yaz',
    'target' => null,
    'variant' => 'secondary',
    'requireOpenAi' => true,
])

@php
    $configured = \App\Services\OpenAiService::isConfigured();
    $disabled = $requireOpenAi && ! $configured;
@endphp

<button
    type="button"
    class="admin-ai-btn admin-ai-btn--{{ $variant }}"
    data-ai-generate
    data-ai-field="{{ $field }}"
    @if($target) data-ai-target="{{ $target }}" @endif
    @if($disabled) disabled title="Site ayarları → Entegrasyonlar: OpenAI API anahtarı ekleyin" @endif
>
    <span class="admin-ai-btn__icon" aria-hidden="true">✦</span>
    {{ $label }}
</button>
