<?php

return [

    'enabled' => env('EXOTEL_ENABLED', false),

    'account_sid' => env('EXOTEL_ACCOUNT_SID'),

    'api_key' => env('EXOTEL_API_KEY'),

    'api_token' => env('EXOTEL_API_TOKEN'),

    /*
    | HMAC-SHA256(hex) of raw webhook body. Header: X-Exotel-Signature
    | When empty, webhook accepts requests (configure in production).
    */
    'webhook_hmac_secret' => env('EXOTEL_WEBHOOK_HMAC_SECRET'),

    /*
    | Single Exotel number strategy (V1). No DNI pool.
    */
    'primary_exophone_sid' => env('EXOTEL_PRIMARY_EXOPHONE_SID'),

    'primary_phone_number' => env('EXOTEL_PRIMARY_PHONE_NUMBER', env('MEDCA_PHONE_TEL')),

    'stitch_window_minutes' => (int) env('EXOTEL_CALL_STITCH_WINDOW', env('MARKETING_CLICK_STITCH_WINDOW', 120)),

];
