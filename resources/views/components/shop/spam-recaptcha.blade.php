@props([
    'context' => 'contact',
])

@php
    $recaptchaEnabled = \App\Support\ContactFormSpamGuard::recaptchaEnabled();
    $recaptchaSiteKey = \App\Support\ContactFormSpamGuard::recaptchaSiteKey();
@endphp

@if($recaptchaEnabled)
    <div class="shop-contact-recaptcha" data-recaptcha-wrap="{{ $context }}">
        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
        <p class="shop-contact-recaptcha__hint text-xs text-slate-500 mt-2">Göndermeden önce &ldquo;Ben robot değilim&rdquo; kutusunu işaretleyin.</p>
    </div>
    @once
        @push('scripts')
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endpush
    @endonce
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('form[data-recaptcha-form]').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        var field = form.querySelector('[name="g-recaptcha-response"]');
                        if (!field || !field.value) {
                            event.preventDefault();
                            var hint = form.querySelector('[data-recaptcha-wrap] .shop-contact-recaptcha__hint');
                            if (hint) {
                                hint.textContent = 'Lütfen "Ben robot değilim" kutusunu işaretleyin.';
                                hint.classList.add('text-red-600');
                            }
                            var wrap = form.querySelector('[data-recaptcha-wrap]');
                            if (wrap) {
                                wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
@endif
