<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Muted activity actions
    |--------------------------------------------------------------------------
    |
    | These activity log actions will not generate admin notifications.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Actions that always notify the actor (sole-admin awareness)
    |--------------------------------------------------------------------------
    */
    'actor_notify_actions' => [
        'bulk_delete',
        'bulk_delete_blocked',
        'bulk_action_failed',
        'login_failure',
        'password_changed',
    ],

    'muted_actions' => [
        'login_success',
        'logout',
        'page_preview',
        'page_sections_reorder',
        'block_remove_from_page',
        'upload_validation_failure',
        'integration_test_success',
        'integration_test_failure',
        'integration_sync_success',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module normalization
    |--------------------------------------------------------------------------
    */
    'module_map' => [
        'integrations' => 'settings',
        'auth' => 'security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module labels
    |--------------------------------------------------------------------------
    */
    'module_labels' => [
        'site_architect' => 'Site Architect',
        'operations' => 'Operations',
        'user_management' => 'User Management',
        'settings' => 'Settings',
        'security' => 'Security',
        'marketing' => 'Marketing',
        'growth_center' => 'Growth Center',
        'system' => 'System',
        'dashboard' => 'Dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module index URLs (fallback when entity URL cannot be resolved)
    |--------------------------------------------------------------------------
    */
    'module_urls' => [
        'site_architect' => '/site-architect/pages',
        'operations' => '/operations',
        'user_management' => '/user-management',
        'settings' => '/admin/settings/integrations',
        'security' => '/security',
        'marketing' => '/marketing/dashboard',
        'growth_center' => '/growth-center/competitors',
        'system' => '/settings/system',
        'dashboard' => '/dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity labels derived from action prefixes
    |--------------------------------------------------------------------------
    */
    'entity_labels' => [
        'page' => 'Page',
        'blog' => 'Blog',
        'block' => 'Block',
        'navigation' => 'Navigation menu',
        'service_category' => 'Service category',
        'service' => 'Service',
        'sub_service' => 'Sub-service',
        'pincode' => 'PIN code',
        'mapping' => 'Service mapping',
        'user' => 'User',
        'integration' => 'Integration',
        'vacancy' => 'Job vacancy',
        'lead' => 'Lead',
        'competitor' => 'Competitor',
        'bulk' => 'Bulk operation',
        'login' => 'Login',
        'password' => 'Password',
    ],

    /*
    |--------------------------------------------------------------------------
    | Operations sub-module URLs (bulk delete deep links)
    |--------------------------------------------------------------------------
    */
    'operations_urls' => [
        'PIN_CODES' => '/operations/pin-codes',
        'SERVICES' => '/operations/services',
        'SERVICE_CATEGORIES' => '/operations/service-categories',
        'SUB_SERVICES' => '/operations/sub-services',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bell dropdown limit
    |--------------------------------------------------------------------------
    */
    'bell_limit' => 12,

    /*
    |--------------------------------------------------------------------------
    | Index page pagination
    |--------------------------------------------------------------------------
    */
    'per_page' => 25,

];
