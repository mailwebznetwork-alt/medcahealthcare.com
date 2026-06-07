@props(['settings' => null])
@php
    $m = $settings ?? \App\Models\MarketingSetting::current();
    $ga4MeasurementId = $m->ga4_measurement_id;
    $metaPixelId = $m->meta_pixel_id;
    $gtmContainerId = '';

    if (\Illuminate\Support\Facades\Schema::hasTable('integrations')) {
        $google = \App\Models\Integration::query()->where('name', 'google_services')->first();
        $meta = \App\Models\Integration::query()->where('name', 'meta_ads')->first();
        $gtm = \App\Models\Integration::query()->where('name', 'google_tag_manager')->first();
        $googleCredentials = $google?->credentials ?? [];
        $metaCredentials = $meta?->credentials ?? [];
        $gtmCredentials = $gtm?->credentials ?? [];

        $ga4MeasurementId = $googleCredentials['measurement_id'] ?? $ga4MeasurementId;
        $metaPixelId = $metaCredentials['pixel_id'] ?? $metaPixelId;
        $gtmContainerId = (string) ($gtmCredentials['container_id'] ?? $gtmCredentials['gtm_id'] ?? '');
    }
@endphp
@if (filled($gtmContainerId))
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmContainerId }}" height="0" width="0" style="display:none;visibility:hidden" title="GTM"></iframe>
    </noscript>
@endif
@if (filled($ga4MeasurementId) || filled($metaPixelId))
    <script>
        document.addEventListener('submit', function (e) {
            if (e.target && e.target.tagName === 'FORM' && typeof gtag === 'function') {
                gtag('event', 'form_submit', {
                    form_name: e.target.getAttribute('name') || e.target.id || 'form',
                    page_path: location.pathname
                });
                gtag('event', 'generate_lead', { method: 'form' });
            }
            if (e.target && e.target.tagName === 'FORM' && typeof fbq === 'function') {
                fbq('track', 'Lead');
            }
        }, true);
    </script>
@endif
