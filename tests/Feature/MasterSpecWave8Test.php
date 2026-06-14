<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Services\MasterSpec\ContentHealthService;
use App\Services\MasterSpec\ThinContentRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MasterSpecWave8Test extends TestCase
{
    use RefreshDatabase;

    public function test_thin_content_rules_detect_short_service_copy(): void
    {
        $service = Service::factory()->create([
            'description' => 'Short text.',
            'short_summary' => null,
        ]);

        $rules = app(ThinContentRules::class);

        $this->assertTrue($rules->isThinService($service));
        $this->assertLessThan(ThinContentRules::SERVICE_MIN_WORDS, $rules->serviceWordCount($service));
    }

    public function test_content_health_report_includes_schema_and_location_metrics(): void
    {
        $report = app(ContentHealthService::class)->report();

        $this->assertArrayHasKey('pages_missing_schema_json', $report);
        $this->assertArrayHasKey('thin_indexable_locations', $report);
        $this->assertArrayHasKey('recommendations', $report);
    }

    public function test_content_health_report_command_writes_markdown(): void
    {
        $output = storage_path('framework/testing/content-health-report.md');
        File::delete($output);

        $exitCode = Artisan::call('medca:content-health-report', ['--output' => $output]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($output);
        $this->assertStringContainsString('Content Health Report', File::get($output));

        File::delete($output);
    }
}
