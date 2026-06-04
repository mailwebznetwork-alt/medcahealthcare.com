@if (config('marketing_automation.enabled', true) && config('marketing_automation.click_tracking.enabled', true))
<script>
(function () {
    if (window.__medcaTrackInstalled) return;
    window.__medcaTrackInstalled = true;

    var endpoint = @json(route('marketing.track'));
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var fingerprint = localStorage.getItem('medca_fp') || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));
    localStorage.setItem('medca_fp', fingerprint);

    function utmFromUrl() {
        var params = new URLSearchParams(location.search);
        return {
            source: params.get('utm_source') || '',
            medium: params.get('utm_medium') || '',
            campaign: params.get('utm_campaign') || ''
        };
    }

    function trackWhatsAppClick(el, href) {
        var utm = utmFromUrl();
        var buttonName = el.getAttribute('data-whatsapp-button')
            || (el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 120)
            || 'whatsapp';
        var phone = el.getAttribute('data-whatsapp-phone') || '';
        if (!phone && href.indexOf('wa.me/') !== -1) {
            var match = href.match(/wa\.me\/(\d+)/);
            if (match) phone = match[1];
        }

        if (typeof gtag === 'function') {
            gtag('event', 'whatsapp_click', {
                button_name: buttonName,
                phone_number: phone,
                page: location.pathname,
                source: utm.source,
                campaign: utm.campaign,
                medium: utm.medium
            });
        }

        if (typeof fbq === 'function') {
            fbq('track', 'Contact');
        }

        window.medcaTrack('whatsapp_click', {
            element_label: buttonName,
            destination_url: href,
            phone_number: phone,
            button_name: buttonName,
            source: utm.source,
            medium: utm.medium,
            campaign: utm.campaign,
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
            var utm = utmFromUrl();
            var payload = Object.assign({
                event_type: eventType,
                page_path: location.pathname,
                page_title: document.title,
                session_fingerprint: fingerprint,
                source: utm.source,
                medium: utm.medium,
                campaign: utm.campaign,
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
            window.medcaTrack('phone_click', { destination_url: href, element_label: (el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 120) });
        } else if (el.classList.contains('btn-premium') || el.classList.contains('medca-cta-solid') || el.dataset.medcaCta !== undefined) {
            window.medcaTrack('cta_click', { destination_url: href || null, element_label: (el.textContent || '').trim().slice(0, 120) });
        } else if (href.indexOf('mailto:') === 0) {
            window.medcaTrack('email_click', { destination_url: href });
        }
    }, true);

    document.addEventListener('focusin', function (e) {
        if (e.target && e.target.tagName === 'FORM') return;
        if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT')) {
            var form = e.target.closest('form');
            if (form && !form.dataset.medcaFormStarted) {
                form.dataset.medcaFormStarted = '1';
                window.medcaTrack('form_start', { element_label: form.getAttribute('name') || form.id || 'form' });
            }
        }
    }, true);

    document.addEventListener('submit', function (e) {
        if (e.target && e.target.tagName === 'FORM') {
            window.medcaTrack('form_submit', { element_label: e.target.getAttribute('name') || e.target.id || 'form' });
        }
    }, true);
})();
</script>
@endif
