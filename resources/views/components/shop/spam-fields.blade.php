@props([
    'context' => 'contact',
    'token' => null,
])

@php
    $formToken = $token ?? \App\Support\ContactFormSpamGuard::beginForm($context);
@endphp

<input type="hidden" name="_form_token" value="{{ $formToken }}">
<div class="shop-contact-honeypot" aria-hidden="true">
    <label for="website_url_{{ $context }}">Website</label>
    <input type="text" name="website_url" id="website_url_{{ $context }}" tabindex="-1" autocomplete="off">
</div>
