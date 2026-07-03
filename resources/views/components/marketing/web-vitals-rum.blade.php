@php
    $rumEnabled = config('growth.rum.enabled', true);
    $rumEndpoint = url(config('growth.rum.endpoint', '/api/v1/performance/rum'));
    $rumSampleRate = max(0.0, min(1.0, (float) config('growth.rum.sample_rate', 0.25)));
@endphp
@if ($rumEnabled && $rumSampleRate > 0)
    <script>
        (function () {
            if (Math.random() > @json($rumSampleRate)) {
                return;
            }

            var endpoint = @json($rumEndpoint);
            var path = window.location.pathname;

            function sendMetric(name, value, rating, id, navigationType) {
                var body = JSON.stringify({
                    name: name,
                    value: value,
                    rating: rating || null,
                    id: id || null,
                    navigationType: navigationType || null,
                    path: path
                });

                if (navigator.sendBeacon) {
                    navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
                    return;
                }

                fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: body,
                    keepalive: true,
                    credentials: 'same-origin'
                }).catch(function () {});
            }

            try {
                new PerformanceObserver(function (list) {
                    list.getEntries().forEach(function (entry) {
                        if (entry.entryType === 'largest-contentful-paint') {
                            sendMetric('LCP', entry.startTime, entry.startTime <= 2500 ? 'good' : (entry.startTime <= 4000 ? 'needs-improvement' : 'poor'), entry.id || null);
                        }
                        if (entry.entryType === 'layout-shift' && !entry.hadRecentInput) {
                            sendMetric('CLS', entry.value, entry.value <= 0.1 ? 'good' : (entry.value <= 0.25 ? 'needs-improvement' : 'poor'));
                        }
                    });
                }).observe({ type: 'largest-contentful-paint', buffered: true });

                new PerformanceObserver(function (list) {
                    list.getEntries().forEach(function (entry) {
                        if (entry.entryType === 'layout-shift' && !entry.hadRecentInput) {
                            sendMetric('CLS', entry.value, entry.value <= 0.1 ? 'good' : (entry.value <= 0.25 ? 'needs-improvement' : 'poor'));
                        }
                    });
                }).observe({ type: 'layout-shift', buffered: true });

                var paintObserver = new PerformanceObserver(function (list) {
                    list.getEntries().forEach(function (entry) {
                        if (entry.name === 'first-contentful-paint') {
                            sendMetric('FCP', entry.startTime, entry.startTime <= 1800 ? 'good' : (entry.startTime <= 3000 ? 'needs-improvement' : 'poor'));
                        }
                    });
                });
                paintObserver.observe({ type: 'paint', buffered: true });

                var nav = performance.getEntriesByType('navigation')[0];
                if (nav) {
                    sendMetric('TTFB', nav.responseStart, nav.responseStart <= 800 ? 'good' : (nav.responseStart <= 1800 ? 'needs-improvement' : 'poor'), null, nav.type);
                }
            } catch (e) {}
        })();
    </script>
@endif
