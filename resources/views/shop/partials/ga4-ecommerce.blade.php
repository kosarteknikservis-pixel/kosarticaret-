@php
    $ga4Id = $ga4Id ?? \App\Models\SiteSetting::get('google_analytics_id');
    $gtmId = $gtmId ?? \App\Models\SiteSetting::get('google_tag_manager_id');
    $metaPixelId = $metaPixelId
        ?? \App\Models\SiteSetting::get('meta_pixel_id')
        ?? config('services.meta.pixel_id');
    $trackingEnabled = filled($ga4Id) || filled($gtmId) || filled($metaPixelId);
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
