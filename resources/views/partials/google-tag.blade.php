@php($measurementId = config('services.google_analytics.measurement_id'))

@if(filled($measurementId))
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', @json($measurementId));
</script>
@endif
