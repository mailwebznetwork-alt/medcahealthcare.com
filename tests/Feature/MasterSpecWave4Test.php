<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Services\Growth\GoogleSearchConsoleService;
use App\Services\Public\CatalogPublicCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MasterSpecWave4Test extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_public_cache_forgets_service_meta_key(): void
    {
        config(['public_cache.enabled' => true, 'public_cache.store' => 'array', 'public_cache.prefix' => 'test_public']);

        $service = Service::factory()->create(['service_code' => 'CACHE-FORGET']);
        $cache = app(CatalogPublicCache::class);

        $cache->documentMeta(service: $service->fresh());
        $key = 'test_public:meta:service:CACHE-FORGET';

        $this->assertNotNull(Cache::store('array')->get($key));

        $cache->forgetForService($service);

        $this->assertNull(Cache::store('array')->get($key));
    }

    public function test_openapi_document_is_publicly_available(): void
    {
        $response = $this->getJson('/api/v1/openapi.json');

        $response->assertOk();
        $response->assertJsonPath('info.title', 'Medca Healthcare Catalog API');
        $response->assertJsonPath('openapi', '3.0.3');
    }

    public function test_gsc_oauth_refresh_token_is_used_when_configured(): void
    {
        config([
            'growth.google_search_console.client_id' => 'client-id',
            'growth.google_search_console.client_secret' => 'client-secret',
            'growth.google_search_console.refresh_token' => 'refresh-token',
            'growth.google_search_console.access_token' => null,
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'oauth-access-token'], 200),
            'www.googleapis.com/webmasters/v3/sites' => Http::response(['siteEntry' => [['siteUrl' => 'https://example.com/']]], 200),
        ]);

        $result = app(GoogleSearchConsoleService::class)->testConnection();

        $this->assertTrue($result['configured']);
        $this->assertSame('oauth_refresh_token', $result['auth_mode']);
        $this->assertContains('https://example.com/', $result['sites']);
    }
}
