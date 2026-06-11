<?php

return [

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
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ])),
    ],

];
