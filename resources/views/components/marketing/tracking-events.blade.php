@if (config('marketing_automation.enabled', true) && config('marketing_automation.click_tracking.enabled', true))
<script>
(function () {
    if (window.__medcaTrackInstalled) return;
    window.__medcaTrackInstalled = true;

    var endpoint = @json(route('marketing.track'));
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var fingerprint = localStorage.getItem('medca_fp') || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));
    localStorage.setItem('medca_fp', fingerprint);

    var attributionKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid'];

    function readStored(key) {
        try { return localStorage.getItem('medca_' + key) || ''; } catch (e) { return ''; }
    }

    function writeStored(key, val) {
        if (!val) return;
        try { localStorage.setItem('medca_' + key, val); } catch (e) {}
    }

    function persistUtmFromUrl() {
        var params = new URLSearchParams(location.search);
        attributionKeys.forEach(function (key) {
            var val = params.get(key);
            if (val) writeStored(key, val);
        });

        // Auto-tagging often sends gclid without UTMs — map to Paid Search–compatible values.
        if (params.get('gclid') || readStored('gclid')) {
            if (!params.get('utm_source') && !readStored('utm_source')) writeStored('utm_source', 'google');
            if (!params.get('utm_medium') && !readStored('utm_medium')) writeStored('utm_medium', 'cpc');
        } else if (params.get('fbclid') || readStored('fbclid')) {
            if (!params.get('utm_source') && !readStored('utm_source')) writeStored('utm_source', 'facebook');
            if (!params.get('utm_medium') && !readStored('utm_medium')) writeStored('utm_medium', 'paid');
        }
    }
    persistUtmFromUrl();

    function resolvedAttribution() {
        var params = new URLSearchParams(location.search);
        var out = {};
        attributionKeys.forEach(function (key) {
            out[key] = params.get(key) || readStored(key) || '';
        });
        if (out.gclid) {
            out.utm_source = out.utm_source || 'google';
            out.utm_medium = out.utm_medium || 'cpc';
        } else if (out.fbclid) {
            out.utm_source = out.utm_source || 'facebook';
            out.utm_medium = out.utm_medium || 'paid';
        }
        return out;
    }

    function fillFormUtmFields() {
        var attr = resolvedAttribution();
        document.querySelectorAll('[data-medca-utm-field]').forEach(function (input) {
            var key = input.getAttribute('data-medca-utm-field');
            if (!key || !attr[key]) return;
            if (!input.value) input.value = attr[key];
        });
    }
    fillFormUtmFields();
    document.addEventListener('DOMContentLoaded', fillFormUtmFields);

    function campaignEventParams(extra) {
        var attr = resolvedAttribution();
        return Object.assign({
            page_path: location.pathname,
            source: attr.utm_source || undefined,
            medium: attr.utm_medium || undefined,
            campaign: attr.utm_campaign || undefined,
            term: attr.utm_term || undefined,
            content: attr.utm_content || undefined
        }, extra || {});
    }

    function ga4Event(name, params) {
        if (typeof gtag !== 'function') return;
        gtag('event', name, campaignEventParams(params));
    }

    function trackWhatsAppClick(el, href) {
        var attr = resolvedAttribution();
        var buttonName = el.getAttribute('data-whatsapp-button')
            || (el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 120)
            || 'whatsapp';
        var phone = el.getAttribute('data-whatsapp-phone') || '';
        if (!phone && href.indexOf('wa.me/') !== -1) {
            var match = href.match(/wa\.me\/(\d+)/);
            if (match) phone = match[1];
        }

        ga4Event('whatsapp_click', {
            button_name: buttonName,
            phone_number: phone
        });
        ga4Event('generate_lead', { method: 'whatsapp' });

        if (typeof fbq === 'function') {
            fbq('track', 'Contact');
        }

        window.medcaTrack('whatsapp_click', {
            element_label: buttonName,
            destination_url: href,
            phone_number: phone,
            button_name: buttonName,
            source: attr.utm_source,
            medium: attr.utm_medium,
            campaign: attr.utm_campaign,
            page_path: location.pathname,
            meta: {
                phone_number: phone,
                button_name: buttonName,
                page: location.pathname
            }
        });
    }

    window.medcaTrack = function (eventType, meta) {
        try {
            var attr = resolvedAttribution();
            var payload = Object.assign({
                event_type: eventType,
                page_path: location.pathname,
                page_title: document.title,
                session_fingerprint: fingerprint,
                source: attr.utm_source || '',
                medium: attr.utm_medium || '',
                campaign: attr.utm_campaign || '',
                meta: meta && meta.meta ? meta.meta : (meta || {})
            }, meta || {});

            var body = JSON.stringify(payload);

            if (navigator.sendBeacon) {
                var blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(endpoint, blob)) return;
            }

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: body,
                keepalive: true,
                credentials: 'same-origin'
            }).catch(function () {});
        } catch (e) {}
    };

    document.addEventListener('click', function (e) {
        var el = e.target.closest('a,button');
        if (!el) return;
        var href = el.getAttribute('href') || '';
        if (el.getAttribute('data-whatsapp-track') === '1' || href.indexOf('wa.me') !== -1 || href.indexOf('whatsapp') !== -1) {
            trackWhatsAppClick(el, href);
            return;
        }
        if (href.indexOf('tel:') === 0) {
            var label = (el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 120);
            ga4Event('phone_click', { destination_url: href, element_label: label });
            ga4Event('generate_lead', { method: 'phone' });
            window.medcaTrack('phone_click', { destination_url: href, element_label: label });
            return;
        }
        if (el.classList.contains('btn-premium') || el.classList.contains('medca-cta-solid')
            || el.classList.contains('medca-cta-on-hero') || el.dataset.medcaCta !== undefined) {
            var ctaLabel = (el.textContent || '').trim().slice(0, 120);
            ga4Event('cta_click', { destination_url: href || null, element_label: ctaLabel });
            window.medcaTrack('cta_click', { destination_url: href || null, element_label: ctaLabel });
            return;
        }
        if (href.indexOf('mailto:') === 0) {
            window.medcaTrack('email_click', { destination_url: href });
        }
    }, true);

    document.addEventListener('focusin', function (e) {
        if (e.target && e.target.tagName === 'FORM') return;
        if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT')) {
            var form = e.target.closest('form');
            if (form && !form.dataset.medcaFormStarted) {
                form.dataset.medcaFormStarted = '1';
                fillFormUtmFields();
                var formName = form.getAttribute('name') || form.id || 'form';
                ga4Event('form_start', { form_name: formName });
                window.medcaTrack('form_start', { element_label: formName });
            }
        }
    }, true);

    document.addEventListener('submit', function (e) {
        if (e.target && e.target.tagName === 'FORM') {
            fillFormUtmFields();
            var formName = e.target.getAttribute('name') || e.target.id || 'form';
            ga4Event('form_submit', { form_name: formName });
            window.medcaTrack('form_submit', { element_label: formName });
        }
    }, true);
})();
</script>
@endif
