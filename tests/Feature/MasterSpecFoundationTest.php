<?php

namespace Tests\Feature;

use App\Enums\MedicalReviewStatus;
use App\Models\BangaloreZone;
use App\Models\Service;
use App\Services\MasterSpec\QuickAnswerGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSpecFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_master_spec_fields_persist_on_service(): void
    {
        $service = Service::factory()->create([
            'quick_answer' => 'Medca provides home nursing in Bangalore.',
            'why_medca' => 'Verified caregivers and 24/7 support.',
            'medical_review_status' => MedicalReviewStatus::Draft,
        ]);

        $this->assertSame('Medca provides home nursing in Bangalore.', $service->fresh()->quick_answer);
        $this->assertSame(MedicalReviewStatus::Draft, $service->fresh()->medical_review_status);
    }

    public function test_quick_answer_generator_fills_from_summary(): void
    {
        $service = Service::factory()->create([
            'title' => 'Home Nursing',
            'short_summary' => 'Professional nursing at home. Available across Bangalore.',
            'quick_answer' => null,
        ]);

        app(QuickAnswerGenerator::class)->fillIfEmpty($service);

        $this->assertNotNull($service->quick_answer);
        $this->assertStringContainsString('Professional nursing', $service->quick_answer);
    }

    public function test_bangalore_zone_links_to_pincode(): void
    {
        $zone = BangaloreZone::query()->create([
            'code' => 'south',
            'name' => 'South Bangalore',
            'slug' => 'south-bangalore',
            'is_active' => true,
        ]);

        $pin = \App\Models\PinCode::factory()->create(['bangalore_zone_id' => $zone->id]);

        $this->assertSame('south', $pin->bangaloreZone?->code);
    }
}
