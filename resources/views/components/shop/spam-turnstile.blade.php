@props([
    'context' => 'contact',
])

@php
    $turnstileEnabled = \App\Support\ContactFormSpamGuard::turnstileEnabled();
    $turnstileSiteKey = \App\Support\ContactFormSpamGuard::siteKey();
@endphp

@if($turnstileEnabled)
    <div class="shop-contact-turnstile" data-turnstile-wrap="{{ $context }}">
        <div
            class="cf-turnstile"
            data-sitekey="{{ $turnstileSiteKey }}"
            data-theme="light"
            data-size="normal"
            data-action="kosar-{{ $context }}"
        ></div>
        <p class="shop-contact-turnstile__hint text-xs text-slate-500 mt-2">Göndermeden önce güvenlik doğrulamasının tamamlanmasını bekleyin.</p>
    </div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('form[data-turnstile-form]').forEach(function (form) {
                        form.addEventListener('submit', function (event) {
                            var field = form.querySelector('[name="cf-turnstile-response"]');
                            if (!field || !field.value) {
                                event.preventDefault();
                                var hint = form.querySelector('[data-turnstile-wrap] .shop-contact-turnstile__hint');
                                if (hint) {
                                    hint.textContent = 'Lütfen güvenlik doğrulamasını tamamlayın.';
                                    hint.classList.add('text-red-600');
                                }
                            }
                        });
                    });
                });
            </script>
        @endpush
    @endonce
@endif
