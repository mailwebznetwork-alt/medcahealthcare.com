@props(['settings' => null])
@php
    $m = $settings ?? \App\Models\MarketingSetting::current();
@endphp
@if (filled($m->ga4_measurement_id))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $m->ga4_measurement_id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $m->ga4_measurement_id }}');
        @if (filled($m->google_ads_aw_id))
        gtag('config', '{{ $m->google_ads_aw_id }}');
        @endif
    </script>
@endif
@if (filled($m->meta_pixel_id))
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $m->meta_pixel_id }}');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img
            height="1"
            width="1"
            class="hidden"
            src="https://www.facebook.com/tr?id={{ $m->meta_pixel_id }}&ev=PageView&noscript=1"
            alt=""
        />
    </noscript>
@endif
