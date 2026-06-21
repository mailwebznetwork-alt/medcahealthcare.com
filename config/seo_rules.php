<?php

return [

    'enabled' => env('SEO_DATA_DRIVEN_ENABLED', true),

    'brand_name' => env('MEDCA_BRAND_NAME', 'Karnataka Diagnostic Centre'),

    'templates' => [
        'service' => [
            'meta_title' => '{service_title} in Bangalore | {brand}',
            'meta_description' => 'Book {service_title} at home in Bangalore. {service_summary}',
            'h1' => '{service_title}',
        ],
        'location' => [
            'meta_title' => '{service_title} in {pincode_area} ({pincode}) | {brand}',
            'meta_description' => '{service_title} near {pincode_area}, pincode {pincode}. {service_summary}',
            'h1' => '{service_title} in {pincode_area}',
        ],
        'category' => [
            'meta_title' => '{category_name} Services | {brand}',
            'meta_description' => 'Explore {category_name} healthcare services from {brand} in Bangalore.',
            'h1' => '{category_name}',
        ],
        'static_page' => [
            'meta_title' => '{page_title} | {brand}',
            'meta_description' => '{page_excerpt}',
            'h1' => '{page_title}',
        ],
    ],

];
