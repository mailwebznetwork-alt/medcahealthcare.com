<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Declarative firewall / edge rules (Security workspace)
    |--------------------------------------------------------------------------
    | Operational enforcement lives in middleware and infrastructure; this list
    | is the operator-facing summary shown under Security → Firewall rules.
    */

    'firewall_rules' => [
        [
            'name' => 'Public lead ingest',
            'scope' => 'POST /api/leads',
            'rule' => 'Rate limited per IP (see throttle:api_leads)',
            'status' => 'active',
        ],
        [
            'name' => 'Payment webhook',
            'scope' => 'POST /api/payments/notify',
            'rule' => 'HMAC-SHA256 body signature when SETTINGS_PAYMENT_INGEST_HMAC_SECRET is set; optional bearer fallback',
            'status' => 'active',
        ],
        [
            'name' => 'Authenticated API',
            'scope' => '/api/admin/*',
            'rule' => 'Laravel Sanctum (session SPA + personal access tokens)',
            'status' => 'active',
        ],
        [
            'name' => 'Staff RBAC',
            'scope' => 'Web routes',
            'rule' => 'Module grants + role middleware (HTML 403 vs JSON by Accept)',
            'status' => 'active',
        ],
    ],

];
