@props([
    'context' => 'contact',
])

@php
    $turnstileEnabled = \App\Support\ContactFormSpamGuard::turnstileEnabled();
    $turnstileSiteKey = \App\Support\ContactFormSpamGuard::siteKey();
    $widgetId = 'turnstile-widget-'.$context;
@endphp

@if($turnstileEnabled)
    <div class="shop-contact-turnstile" data-turnstile-wrap="{{ $context }}">
        <div id="{{ $widgetId }}" class="shop-contact-turnstile__mount" data-turnstile-mount="{{ $context }}"></div>
        <p class="shop-contact-turnstile__hint text-xs text-slate-500 mt-2">Göndermeden önce güvenlik kutusunu tamamlayın.</p>
    </div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" defer></script>
        @endpush
    @endonce
    @push('scripts')
        <script>
            (function () {
                var mountId = @json($widgetId);
                var sitekey = @json($turnstileSiteKey);
                var context = @json($context);
                var attempts = 0;

                function hintEl() {
                    var mount = document.getElementById(mountId);
                    return mount ? mount.closest('[data-turnstile-wrap]')?.querySelector('.shop-contact-turnstile__hint') : null;
                }

                function renderWidget() {
                    if (!window.turnstile || typeof window.turnstile.render !== 'function') {
                        return false;
                    }

                    var mount = document.getElementById(mountId);
                    if (!mount || mount.dataset.rendered === '1') {
                        return true;
                    }

                    try {
                        window.turnstile.render('#' + mountId, {
                            sitekey: sitekey,
                            theme: 'light',
                            size: 'normal',
                            action: 'kosar-' + context,
                            callback: function () {
                                var hint = hintEl();
                                if (hint) {
                                    hint.textContent = 'Güvenlik doğrulaması tamamlandı.';
                                    hint.classList.remove('text-red-600');
                                    hint.classList.add('text-emerald-700');
                                }
                            },
                            'error-callback': function () {
                                var hint = hintEl();
                                if (hint) {
                                    hint.textContent = 'Güvenlik kutusu yüklenemedi. Sayfayı yenileyin.';
                                    hint.classList.add('text-red-600');
                                }
                            },
                        });
                        mount.dataset.rendered = '1';
                        return true;
                    } catch (e) {
                        return false;
                    }
                }

                function boot() {
                    if (renderWidget()) {
                        return;
                    }
                    var timer = window.setInterval(function () {
                        if (renderWidget() || ++attempts > 50) {
                            window.clearInterval(timer);
                            if (attempts > 50) {
                                var hint = hintEl();
                                if (hint) {
                                    hint.textContent = 'Güvenlik kutusu açılamadı. Sayfayı yenileyin veya site yöneticisine bildirin.';
                                    hint.classList.add('text-red-600');
                                }
                            }
                        }
                    }, 200);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', boot);
                } else {
                    boot();
                }
            })();
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('form[data-turnstile-form]').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        var field = form.querySelector('[name="cf-turnstile-response"]');
                        if (!field || !field.value) {
                            event.preventDefault();
                            var hint = form.querySelector('[data-turnstile-wrap] .shop-contact-turnstile__hint');
                            if (hint) {
                                hint.textContent = 'Lütfen güvenlik kutusunu tamamlayın.';
                                hint.classList.add('text-red-600');
                            }
                            var mount = form.querySelector('[data-turnstile-mount]');
                            if (mount) {
                                mount.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
@endif
