@if(filled($ga4Id ?? \App\Models\SiteSetting::get('google_analytics_id')))
@php
    $ga4Payload = $ga4Payload ?? null;
@endphp
@if($ga4Payload)
<script>
(() => {
    const fire = () => {
        if (typeof window.gtag !== 'function') {
            return;
        }
        window.gtag('event', @json($ga4Payload['event'] ?? ''), @json($ga4Payload['params'] ?? []));
    };

    if (window.KosarAnalyticsLoaded) {
        fire();
        return;
    }

    const onReady = () => {
        fire();
        window.removeEventListener('kosar:analytics-ready', onReady);
    };
    window.addEventListener('kosar:analytics-ready', onReady);
    ['pointerdown', 'keydown', 'scroll'].forEach((name) => {
        window.addEventListener(name, onReady, { once: true, passive: true });
    });
})();
</script>
@endif
@endif
