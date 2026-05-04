<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CDN (optional)
    |--------------------------------------------------------------------------
    |
    | When enabled, public URLs use CDN_URL + /storage/{path} instead of APP_URL.
    |
    */

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image processing (upload-time only)
    |--------------------------------------------------------------------------
    */

    'max_width' => 1200,

    'responsive_widths' => [
        'small' => 480,
        'medium' => 768,
        'large' => 1200,
    ],

    'blur_width' => 20,

    'jpeg_quality' => 85,

    'webp_quality' => 82,

];
