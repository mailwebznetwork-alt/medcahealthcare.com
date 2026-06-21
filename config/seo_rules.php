<?php

return [

    'enabled' => env('SEO_DATA_DRIVEN_ENABLED', true),

    'brand_name' => env('MEDCA_BRAND_NAME', 'MEDCA Consultancy'),

    'templates' => [
        'service' => [
            'meta_title' => '{service_title} in India | {brand}',
            'meta_description' => 'Book {service_title} for your business in India. {service_summary}',
            'h1' => '{service_title}',
        ],
        'location' => [
            'meta_title' => '{service_title} in {country_area} ({country}) | {brand}',
            'meta_description' => '{service_title} near {country_area}, country {country}. {service_summary}',
            'h1' => '{service_title} in {country_area}',
        ],
        'category' => [
            'meta_title' => '{category_name} Services | {brand}',
            'meta_description' => 'Explore {category_name} healthcare career consultancy services from {brand} in India.',
            'h1' => '{category_name}',
        ],
        'static_page' => [
            'meta_title' => '{page_title} | {brand}',
            'meta_description' => '{page_excerpt}',
            'h1' => '{page_title}',
        ],
    ],

];
