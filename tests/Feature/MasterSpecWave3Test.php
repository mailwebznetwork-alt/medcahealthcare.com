<?php

namespace Tests\Feature;

use App\GraphQL\CatalogGraphqlExecutor;
use App\Models\Service;
use App\Services\MasterSpec\ProgrammaticSeoQualityScorer;
use App\Services\Seo\HreflangGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSpecWave3Test extends TestCase
{
    use RefreshDatabase;

    public function test_hreflang_generator_includes_english_and_x_default(): void
    {
        $map = app(HreflangGenerator::class)->forCanonicalUrl('https://medcahealthcare.com/services/test');

        $this->assertArrayHasKey('en', $map);
        $this->assertArrayHasKey('x-default', $map);
    }

    public function test_programmatic_seo_scorer_returns_score(): void
    {
        $service = Service::factory()->create([
            'short_summary' => 'Test summary for scoring.',
            'quick_answer' => 'Quick answer text.',
        ]);

        $result = app(ProgrammaticSeoQualityScorer::class)->scoreService($service->fresh());

        $this->assertGreaterThan(0, $result['score']);
    }

    public function test_graphql_services_query(): void
    {
        Service::factory()->create(['service_code' => 'GQL-TEST', 'title' => 'GraphQL Test']);

        $result = app(CatalogGraphqlExecutor::class)->execute('
            { services(limit: 5) { code title url } }
        ');

        $this->assertEmpty($result['errors'] ?? []);
        $this->assertNotEmpty($result['data']['services'] ?? []);
    }
}
