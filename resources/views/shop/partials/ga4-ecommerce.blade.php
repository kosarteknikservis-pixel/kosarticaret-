@php
    $ga4Id = $ga4Id ?? \App\Models\SiteSetting::get('google_analytics_id');
    $gtmId = $gtmId ?? \App\Models\SiteSetting::get('google_tag_manager_id');
    $trackingEnabled = filled($ga4Id) || filled($gtmId);
    $ga4Payload = $ga4Payload ?? null;
@endphp
@if($trackingEnabled && $ga4Payload)
<script>
(() => {
    const eventName = @json($ga4Payload['event'] ?? '');
    const params = @json($ga4Payload['params'] ?? new \stdClass());
    if (!eventName || typeof window.kosarTrackEcommerce !== 'function') {
        return;
    }
    window.kosarTrackEcommerce(eventName, params);
})();
</script>
@endif
