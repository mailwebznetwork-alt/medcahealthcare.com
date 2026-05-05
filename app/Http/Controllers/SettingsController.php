<?php

namespace App\Http\Controllers;

use App\Models\GoogleBusinessReview;
use App\Models\Integration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        /** @var Collection<int, Integration> $integrations */
        $integrations = collect();

        if (Schema::hasTable('integrations')) {
            foreach ($this->definitions() as $name => $type) {
                Integration::query()->firstOrCreate(
                    ['name' => $name],
                    ['type' => $type, 'credentials' => [], 'is_enabled' => false]
                );
            }

            $integrations = Integration::query()
                ->orderBy('type')
                ->orderBy('name')
                ->get();
        }

        $googleBusinessReviews = collect();
        if (Schema::hasTable('google_business_reviews')) {
            $googleBusinessReviews = GoogleBusinessReview::query()->latest('review_time')->limit(20)->get();
        }

        return view('settings.integrations', [
            'integrations' => $integrations,
            'fieldMap' => $this->fieldMap(),
            'googleBusinessReviews' => $googleBusinessReviews,
        ]);
    }

    private function definitions(): array
    {
        return [
            'google_services' => 'google',
            'google_business_profile' => 'google',
            'microsoft_clarity' => 'analytics',
            'gemini' => 'ai',
            'meta_ads' => 'meta',
            'meta_capi' => 'meta',
            'whatsapp_business_1' => 'whatsapp',
            'whatsapp_business_2' => 'whatsapp',
            'whatsapp_business_3' => 'whatsapp',
            'twilio' => 'communication',
            'chatgpt' => 'ai',
            'youtube' => 'social',
            'linkedin' => 'social',
            'facebook' => 'social',
            'instagram' => 'social',
            'crm_hubspot' => 'crm',
            'crm_salesforce' => 'crm',
            'crm_zoho' => 'crm',
            'crm_custom_1' => 'crm',
            'crm_custom_2' => 'crm',
            'crm_custom_3' => 'crm',
            'bing_webmaster' => 'seo',
            'just_dial' => 'listing',
            'webhook' => 'automation',
            'aws_s3' => 'storage',
            'cloudflare' => 'storage',
        ];
    }

    private function fieldMap(): array
    {
        return [
            'google_services' => ['measurement_id', 'property_id', 'google_ads_aw_id', 'container_id', 'verification_code', 'location_id', 'api_key'],
            'google_business_profile' => ['account_id', 'location_id', 'oauth_refresh_token'],
            'microsoft_clarity' => ['project_id'],
            'gemini' => ['api_key', 'model', 'temperature'],
            'meta_ads' => ['pixel_id', 'access_token'],
            'meta_capi' => ['capi_pixel_id', 'capi_access_token', 'test_event_code'],
            'whatsapp_business_1' => ['phone_number_id', 'access_token', 'webhook_verify_token'],
            'whatsapp_business_2' => ['phone_number_id', 'access_token', 'webhook_verify_token'],
            'whatsapp_business_3' => ['phone_number_id', 'access_token', 'webhook_verify_token'],
            'twilio' => ['sid', 'auth_token', 'from_number'],
            'chatgpt' => ['api_key', 'model', 'temperature'],
            'youtube' => ['api_key', 'channel_id'],
            'linkedin' => ['client_id', 'client_secret', 'access_token'],
            'facebook' => ['page_id', 'access_token'],
            'instagram' => ['instagram_account_id', 'access_token'],
            'crm_hubspot' => ['access_token', 'portal_id'],
            'crm_salesforce' => ['instance_url', 'access_token', 'client_id', 'client_secret'],
            'crm_zoho' => ['access_token', 'org_id'],
            'crm_custom_1' => ['crm_name', 'base_url', 'access_token'],
            'crm_custom_2' => ['crm_name', 'base_url', 'access_token'],
            'crm_custom_3' => ['crm_name', 'base_url', 'access_token'],
            'bing_webmaster' => ['site_url', 'api_key'],
            'just_dial' => ['api_key', 'profile_id', 'endpoint_url'],
            'webhook' => ['endpoint_url', 'secret'],
            'aws_s3' => ['key', 'secret', 'region', 'bucket'],
            'cloudflare' => ['api_token', 'zone_id'],
        ];
    }
}
