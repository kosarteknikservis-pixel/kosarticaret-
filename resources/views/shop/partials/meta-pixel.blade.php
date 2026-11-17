@php
    $metaPixelId = trim((string) (
        $metaPixelId
        ?? \App\Models\SiteSetting::get('meta_pixel_id')
        ?? config('services.meta.pixel_id')
        ?? ''
    ));
@endphp
@if($metaPixelId !== '' && preg_match('/^\d{5,20}$/', $metaPixelId))
<script>
    window.KosarMetaPixelId = @json($metaPixelId);
    window.kosarLoadMetaPixel = function () {
        if (window.KosarMetaPixelLoaded || !window.KosarMetaPixelId) {
            return;
        }
        window.KosarMetaPixelLoaded = true;
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(s)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', window.KosarMetaPixelId);
        fbq('track', 'PageView');
    };
</script>
@endif
