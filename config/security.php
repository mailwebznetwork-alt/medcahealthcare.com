<?php

return [

    'lead_api_key' => env('LEAD_API_KEY'),

    'firewall_rules' => [
        ['name' => 'HTTPS', 'status' => 'active', 'detail' => 'TLS termination enforced at reverse proxy'],
        ['name' => 'Security headers', 'status' => 'active', 'detail' => 'CSP, HSTS, X-Frame-Options via middleware'],
        ['name' => 'Module RBAC', 'status' => 'active', 'detail' => 'Per-module grants via ModuleAccess'],
        ['name' => 'Rate limiting', 'status' => 'active', 'detail' => 'API, leads, and marketing track throttles'],
        ['name' => 'CSRF', 'status' => 'active', 'detail' => 'Web forms protected; marketing track exempt by design'],
    ],

    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),

        'hsts_enabled' => env('SECURITY_HSTS_ENABLED', true),

        'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

        /*
        | Relaxed CSP for Livewire, Alpine, Vite, and Google Fonts.
        | Tighten script-src once inline scripts are eliminated.
        */
        'content_security_policy' => env('SECURITY_CSP', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https: https://www.google-analytics.com https://www.googletagmanager.com https://connect.facebook.net",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ])),
    ],

];
