@props(['settings' => null])
@php
    $m = $settings ?? \App\Models\MarketingSetting::current();
@endphp
@if (filled($m->ga4_measurement_id) || filled($m->meta_pixel_id))
    <script>
        document.addEventListener('submit', function (e) {
            if (e.target && e.target.tagName === 'FORM' && typeof gtag === 'function') {
                gtag('event', 'form_submit');
            }
            if (e.target && e.target.tagName === 'FORM' && typeof fbq === 'function') {
                fbq('track', 'Lead');
            }
        }, true);
    </script>
@endif
