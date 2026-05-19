<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public careers / JobPosting structured data
    |--------------------------------------------------------------------------
    |
    | Used for SEO meta defaults and schema.org JobPosting hiringOrganization.
    |
    */

    'organization_name' => env('CAREERS_ORG_NAME', env('APP_NAME', 'MarkOnMinds')),

    'organization_url' => env('CAREERS_ORG_URL', env('APP_URL', 'http://localhost')),

    'organization_logo' => env('CAREERS_ORG_LOGO'),

    /*
    |--------------------------------------------------------------------------
    | Shared job detail layout (Site Architect page slug)
    |--------------------------------------------------------------------------
    |
    | Public /careers/{slug} renders this CMS page when active. Blocks receive
    | $vacancy. Override per vacancy via detail_page_id in Job portal.
    |
    */

    'job_detail_page_slug' => env('CAREERS_JOB_DETAIL_PAGE_SLUG', 'careers-job-detail'),

];
