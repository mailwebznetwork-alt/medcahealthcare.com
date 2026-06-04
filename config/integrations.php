<?php

use App\Services\Integrations\BingWebmasterService;
use App\Services\Integrations\ClarityService;
use App\Services\Integrations\CrmService;
use App\Services\Integrations\GeminiService;
use App\Services\Integrations\GoogleBusinessProfileService;
use App\Services\Integrations\GoogleService;
use App\Services\Integrations\JustDialService;
use App\Services\Integrations\MetaService;
use App\Services\Integrations\OpenAIService;
use App\Services\Integrations\SocialService;
use App\Services\Integrations\StorageService;
use App\Services\Integrations\TwilioService;
use App\Services\Integrations\WebhookService;
use App\Services\Integrations\WhatsAppService;

return [
    'definitions' => [
        'gemini' => [
            'label' => 'Gemini',
            'type' => 'ai',
            'service' => GeminiService::class,
            'fields' => [
                'api_key' => ['required', 'string', 'max:255'],
                'model' => ['required', 'string', 'max:120'],
                'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            ],
        ],
        'chatgpt' => [
            'label' => 'ChatGPT',
            'type' => 'ai',
            'service' => OpenAIService::class,
            'fields' => [
                'api_key' => ['required', 'string', 'max:255'],
                'model' => ['required', 'string', 'max:120'],
                'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            ],
        ],
        'google_analytics' => [
            'label' => 'Google Analytics',
            'type' => 'google',
            'service' => GoogleService::class,
            'fields' => [
                'measurement_id' => ['required', 'string', 'max:120'],
                'property_id' => ['nullable', 'string', 'max:120'],
                'api_key' => ['required', 'string', 'max:255'],
            ],
        ],
        'google_ads' => [
            'label' => 'Google Ads',
            'type' => 'ads',
            'service' => GoogleService::class,
            'fields' => [
                'google_ads_aw_id' => ['required', 'string', 'max:120'],
                'api_key' => ['required', 'string', 'max:255'],
            ],
        ],
        'google_tag_manager' => [
            'label' => 'Google Tag Manager',
            'type' => 'google',
            'service' => GoogleService::class,
            'fields' => [
                'container_id' => ['required', 'string', 'max:120'],
                'verification_code' => ['required', 'string', 'max:255'],
            ],
        ],
        'google_business_profile' => [
            'label' => 'Google Business Profile',
            'type' => 'google',
            'service' => GoogleBusinessProfileService::class,
            'fields' => [
                'account_id' => ['required', 'string', 'max:120'],
                'location_id' => ['required', 'string', 'max:120'],
                'oauth_refresh_token' => ['required', 'string', 'max:2048'],
            ],
        ],
        'meta_ads' => [
            'label' => 'Meta Ads',
            'type' => 'ads',
            'service' => MetaService::class,
            'fields' => [
                'pixel_id' => ['required', 'string', 'max:120'],
                'access_token' => ['required', 'string', 'max:255'],
            ],
        ],
        'meta_capi' => [
            'label' => 'Meta CAPI',
            'type' => 'ads',
            'service' => MetaService::class,
            'fields' => [
                'capi_pixel_id' => ['required', 'string', 'max:120'],
                'capi_access_token' => ['nullable', 'string', 'max:255'],
                'test_event_code' => ['nullable', 'string', 'max:120'],
            ],
        ],
        'youtube' => [
            'label' => 'YouTube',
            'type' => 'social',
            'service' => SocialService::class,
            'fields' => [
                'api_key' => ['required', 'string', 'max:255'],
                'channel_id' => ['required', 'string', 'max:120'],
            ],
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'type' => 'social',
            'service' => SocialService::class,
            'fields' => [
                'client_id' => ['required', 'string', 'max:255'],
                'client_secret' => ['required', 'string', 'max:255'],
                'access_token' => ['nullable', 'string', 'max:255'],
            ],
        ],
        'facebook' => [
            'label' => 'Facebook',
            'type' => 'social',
            'service' => SocialService::class,
            'fields' => [
                'page_id' => ['required', 'string', 'max:120'],
                'access_token' => ['required', 'string', 'max:255'],
            ],
        ],
        'instagram' => [
            'label' => 'Instagram',
            'type' => 'social',
            'service' => SocialService::class,
            'fields' => [
                'instagram_account_id' => ['required', 'string', 'max:120'],
                'access_token' => ['required', 'string', 'max:255'],
            ],
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'type' => 'communication',
            'service' => WhatsAppService::class,
            'click_to_chat' => true,
            'custom_configure' => true,
            'fields' => [
                'floating_button_enabled' => ['boolean'],
            ],
        ],
        'whatsapp_business' => [
            'label' => 'WhatsApp Business API',
            'type' => 'communication',
            'service' => WhatsAppService::class,
            'fields' => [],
            'hidden_from_add_list' => true,
            'multi_account' => true,
            'account_fields' => [
                'phone_number_id' => ['required', 'string', 'max:120'],
                'access_token' => ['required', 'string', 'max:255'],
                'webhook_verify_token' => ['required', 'string', 'max:255'],
            ],
        ],
        'twilio' => [
            'label' => 'Twilio',
            'type' => 'communication',
            'service' => TwilioService::class,
            'fields' => [
                'sid' => ['required', 'string', 'max:120'],
                'auth_token' => ['required', 'string', 'max:255'],
                'from_number' => ['required', 'string', 'max:40'],
            ],
        ],
        'crm_hubspot' => [
            'label' => 'CRM HubSpot',
            'type' => 'crm',
            'service' => CrmService::class,
            'fields' => [
                'access_token' => ['required', 'string', 'max:255'],
                'portal_id' => ['nullable', 'string', 'max:120'],
            ],
        ],
        'crm_salesforce' => [
            'label' => 'CRM Salesforce',
            'type' => 'crm',
            'service' => CrmService::class,
            'fields' => [
                'instance_url' => ['required', 'url', 'max:2048'],
                'access_token' => ['required', 'string', 'max:255'],
                'client_id' => ['nullable', 'string', 'max:255'],
                'client_secret' => ['nullable', 'string', 'max:255'],
            ],
        ],
        'crm_zoho' => [
            'label' => 'CRM Zoho',
            'type' => 'crm',
            'service' => CrmService::class,
            'fields' => [
                'access_token' => ['required', 'string', 'max:255'],
                'org_id' => ['nullable', 'string', 'max:120'],
            ],
        ],
        'crm_custom_1' => [
            'label' => 'CRM Custom 1',
            'type' => 'crm',
            'service' => CrmService::class,
            'fields' => [
                'crm_name' => ['required', 'string', 'max:120'],
                'base_url' => ['required', 'url', 'max:2048'],
                'access_token' => ['required', 'string', 'max:255'],
            ],
        ],
        'crm_custom_2' => [
            'label' => 'CRM Custom 2',
            'type' => 'crm',
            'service' => CrmService::class,
            'fields' => [
                'crm_name' => ['required', 'string', 'max:120'],
                'base_url' => ['required', 'url', 'max:2048'],
                'access_token' => ['required', 'string', 'max:255'],
            ],
        ],
        'crm_custom_3' => [
            'label' => 'CRM Custom 3',
            'type' => 'crm',
            'service' => CrmService::class,
            'fields' => [
                'crm_name' => ['required', 'string', 'max:120'],
                'base_url' => ['required', 'url', 'max:2048'],
                'access_token' => ['required', 'string', 'max:255'],
            ],
        ],
        'microsoft_clarity' => [
            'label' => 'Microsoft Clarity',
            'type' => 'analytics',
            'service' => ClarityService::class,
            'fields' => [
                'project_id' => ['required', 'string', 'max:120'],
            ],
        ],
        'bing_webmaster' => [
            'label' => 'Bing Webmaster',
            'type' => 'seo',
            'service' => BingWebmasterService::class,
            'fields' => [
                'site_url' => ['required', 'url', 'max:2048'],
                'api_key' => ['required', 'string', 'max:255'],
            ],
        ],
        'just_dial' => [
            'label' => 'Just Dial',
            'type' => 'listing',
            'service' => JustDialService::class,
            'fields' => [
                'api_key' => ['required', 'string', 'max:255'],
                'profile_id' => ['required', 'string', 'max:120'],
                'endpoint_url' => ['nullable', 'url', 'max:2048'],
            ],
        ],
        'webhook' => [
            'label' => 'Webhook',
            'type' => 'automation',
            'service' => WebhookService::class,
            'fields' => [
                'endpoint_url' => ['required', 'url', 'max:2048'],
                'secret' => ['required', 'string', 'max:255'],
            ],
        ],
        'aws_s3' => [
            'label' => 'AWS S3',
            'type' => 'storage',
            'service' => StorageService::class,
            'fields' => [
                'key' => ['required', 'string', 'max:255'],
                'secret' => ['required', 'string', 'max:255'],
                'region' => ['required', 'string', 'max:120'],
                'bucket' => ['required', 'string', 'max:255'],
            ],
        ],
        'cloudflare' => [
            'label' => 'Cloudflare',
            'type' => 'storage',
            'service' => StorageService::class,
            'fields' => [
                'api_token' => ['required', 'string', 'max:255'],
                'zone_id' => ['required', 'string', 'max:255'],
            ],
        ],
        'google_drive' => [
            'label' => 'Google Drive',
            'type' => 'storage',
            'service' => StorageService::class,
            'fields' => [
                'client_id' => ['required', 'string', 'max:255'],
                'client_secret' => ['required', 'string', 'max:255'],
                'refresh_token' => ['required', 'string', 'max:2048'],
            ],
        ],
        'onedrive' => [
            'label' => 'OneDrive',
            'type' => 'storage',
            'service' => StorageService::class,
            'fields' => [
                'client_id' => ['required', 'string', 'max:255'],
                'client_secret' => ['required', 'string', 'max:255'],
                'refresh_token' => ['required', 'string', 'max:2048'],
                'tenant_id' => ['required', 'string', 'max:255'],
            ],
        ],
    ],
];
