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
                var params = new URLSearchParams(location.search);
                var source = params.get('utm_source') || (function () { try { return localStorage.getItem('medca_utm_source') || ''; } catch (err) { return ''; } })();
                var medium = params.get('utm_medium') || (function () { try { return localStorage.getItem('medca_utm_medium') || ''; } catch (err) { return ''; } })();
                var campaign = params.get('utm_campaign') || (function () { try { return localStorage.getItem('medca_utm_campaign') || ''; } catch (err) { return ''; } })();
                var gclid = params.get('gclid') || (function () { try { return localStorage.getItem('medca_gclid') || ''; } catch (err) { return ''; } })();
                if (gclid && !source) { source = 'google'; }
                if (gclid && !medium) { medium = 'cpc'; }

                gtag('event', 'form_submit', {
                    form_name: e.target.getAttribute('name') || e.target.id || 'form',
                    page_path: location.pathname,
                    source: source || undefined,
                    medium: medium || undefined,
                    campaign: campaign || undefined
                });
                gtag('event', 'generate_lead', {
                    method: 'form',
                    source: source || undefined,
                    medium: medium || undefined,
                    campaign: campaign || undefined
                });
            }
            if (e.target && e.target.tagName === 'FORM' && typeof fbq === 'function') {
                fbq('track', 'Lead');
            }
        }, true);
    </script>
@endif
