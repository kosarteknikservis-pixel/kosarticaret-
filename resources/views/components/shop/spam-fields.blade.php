@props([
    'context' => 'contact',
    'token' => null,
])

@php
    $formToken = $token ?? \App\Support\ContactFormSpamGuard::beginForm($context);
    $turnstileEnabled = \App\Support\ContactFormSpamGuard::turnstileEnabled();
    $turnstileSiteKey = \App\Support\ContactFormSpamGuard::siteKey();
@endphp

<input type="hidden" name="_form_token" value="{{ $formToken }}">
<div class="shop-contact-honeypot" aria-hidden="true">
    <label for="website_url_{{ $context }}">Website</label>
    <input type="text" name="website_url" id="website_url_{{ $context }}" tabindex="-1" autocomplete="off">
</div>

@if($turnstileEnabled)
    <div class="shop-contact-turnstile">
        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light"></div>
    </div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
