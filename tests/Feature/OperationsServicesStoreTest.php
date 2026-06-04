<?php

use App\Models\Block;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\User;
use Illuminate\Support\Str;
use App\ModuleAccess;
use App\Services\Operations\ServiceDetailPageSeoSync;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function operationsManagerUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);
}

it('normalizes service code spacing before validation', function () {
    $user = operationsManagerUser();

    $response = $this->actingAs($user)->post(route('operations.services.store'), [
        'title' => 'Another Service',
        'service_code' => 'My_Service_Code',
        'publish_status' => 'draft',
        'visibility' => 'public',
    ]);

    $response->assertSessionDoesntHaveErrors();

    expect(Service::query()->where('service_code', 'my-service-code')->exists())->toBeTrue();
});

it('persists GEO pincodes when updating a service', function () {
    $user = operationsManagerUser();
    $service = Service::factory()->create(['service_code' => 'caregivers']);
    $pin = PinCode::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)->put(route('operations.services.update', $service), [
        'title' => $service->title,
        'service_code' => 'caregivers',
        'publish_status' => 'published',
        'visibility' => 'public',
        'pincodes' => [$pin->id],
    ]);

    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect();

    expect($service->fresh()->pincodes->pluck('id')->all())->toEqual([$pin->id]);
});

it('migrates legacy service SEO into the linked detail page when empty', function () {
    $service = Service::withoutEvents(fn () => Service::factory()->create([
        'service_code' => 'home-nursing',
        'detail_page_id' => null,
    ]));

    $service->seo()->create([
        'meta_title' => 'Home Nursing Meta',
        'meta_description' => 'Trusted nursing at home.',
        'focus_keywords' => ['nursing', 'home care'],
        'h1' => 'Home Nursing H1',
        'h2' => ['Section A'],
        'ai_context' => 'Context for AI surfaces.',
        'search_intent' => 'commercial',
    ]);

    ServiceFaq::factory()->create([
        'service_id' => $service->id,
        'question' => 'What areas do you cover?',
        'answer' => 'Bangalore south cluster.',
    ]);

    $page = Page::withoutEvents(fn () => Page::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Home Nursing detail',
        'slug' => 'service-home-nursing',
        'meta_title' => null,
        'meta_description' => null,
        'is_active' => true,
    ]));

    expect($service->fresh('seo')->seo?->meta_title)->toBe('Home Nursing Meta');

    app(ServiceDetailPageSeoSync::class)->migrateFromServiceIfEmpty(
        $service->fresh(['seo', 'faqs', 'schema']),
        $page
    );

    $page->refresh()->load('faqs');

    expect($page->meta_title)->toBe('Home Nursing Meta')
        ->and($page->focus_keywords)->toEqual(['nursing', 'home care'])
        ->and($page->heading_h2)->toEqual(['Section A'])
        ->and($page->ai_context)->toBe('Context for AI surfaces.')
        ->and($page->aeo_question)->toBe('What areas do you cover?')
        ->and($page->faqs)->toHaveCount(1);
});

it('persists content fields when creating a service', function () {
    $user = operationsManagerUser();

    $response = $this->actingAs($user)->post(route('operations.services.store'), [
        'title' => 'Home Nursing',
        'service_code' => 'home-nursing',
        'publish_status' => 'draft',
        'visibility' => 'public',
        'short_summary' => 'Doctor-led nursing at home.',
        'description' => '<p>Full nursing care description.</p>',
        'procedures_lines' => "Vital monitoring\nWound care\n",
    ]);

    $response->assertSessionDoesntHaveErrors();
    $service = Service::query()->where('service_code', 'home-nursing')->first();
    expect($service)->not->toBeNull()
        ->and($service->short_summary)->toBe('Doctor-led nursing at home.')
        ->and($service->description)->toContain('Full nursing care')
        ->and($service->procedures)->toEqual(['Vital monitoring', 'Wound care']);
});

it('persists content and media when updating a service', function () {
    Storage::fake('public');
    $user = operationsManagerUser();
    $service = Service::factory()->create([
        'service_code' => 'physio-home',
        'gallery' => ['services/99/existing.jpg'],
    ]);

    $response = $this->actingAs($user)->put(route('operations.services.update', $service), [
        'title' => $service->title,
        'service_code' => 'physio-home',
        'publish_status' => 'published',
        'visibility' => 'public',
        'short_summary' => 'Updated summary',
        'procedures_lines' => "Assessment\nExercise plan",
        'featured_image' => UploadedFile::fake()->image('featured.jpg'),
        'gallery_files' => [UploadedFile::fake()->image('gallery-a.jpg')],
        'remove_gallery' => ['services/99/existing.jpg'],
    ]);

    $response->assertSessionDoesntHaveErrors();

    $service->refresh();
    expect($service->short_summary)->toBe('Updated summary')
        ->and($service->procedures)->toEqual(['Assessment', 'Exercise plan'])
        ->and($service->featured_image)->not->toBeNull()
        ->and($service->gallery)->toHaveCount(1)
        ->and($service->gallery[0])->toStartWith('services/'.$service->id.'/');
});

it('persists SEO, AEO, FAQs, schema, and clinical lists on update', function () {
    $user = operationsManagerUser();
    $service = Service::factory()->create(['service_code' => 'full-recovery']);

    $response = $this->actingAs($user)->put(route('operations.services.update', $service), [
        'title' => $service->title,
        'service_code' => 'full-recovery',
        'publish_status' => 'draft',
        'visibility' => 'public',
        'specialized_care_lines' => "ICU step-down\nPost-op care",
        'shifts_lines' => "12h day\n12h night",
        'seo' => [
            'meta_title' => 'Recovery Meta',
            'meta_description' => 'Meta body',
            'h1' => 'Recovery H1',
            'focus_keywords_lines' => "nursing\nhome care",
            'h2_lines' => "Benefits",
            'ai_context' => 'AEO context block',
            'search_intent' => 'commercial',
        ],
        'ai_keywords_lines' => "who provides nursing\n",
        'target_keywords_lines' => "bangalore nursing\n",
        'faqs' => [
            ['question' => 'Do you cover Arekere?', 'answer' => 'Yes, within 25km.'],
        ],
        'schema_type' => 'MedicalProcedure',
        'schema_json' => '{"@type":"MedicalProcedure","name":"Test"}',
    ]);

    $response->assertSessionDoesntHaveErrors();

    $service->refresh()->load(['seo', 'faqs', 'schema']);

    expect($service->specialized_care)->toEqual(['ICU step-down', 'Post-op care'])
        ->and($service->shifts)->toEqual(['12h day', '12h night'])
        ->and($service->target_keywords)->toEqual(['bangalore nursing'])
        ->and($service->ai_keywords)->toEqual(['who provides nursing'])
        ->and($service->seo?->meta_title)->toBe('Recovery Meta')
        ->and($service->seo?->ai_context)->toBe('AEO context block')
        ->and($service->seo?->focus_keywords)->toEqual(['nursing', 'home care'])
        ->and($service->faqs)->toHaveCount(1)
        ->and($service->faqs->first()?->question)->toBe('Do you cover Arekere?')
        ->and($service->schema?->schema_type)->toBe('MedicalProcedure')
        ->and($service->schema?->schema_json)->toMatchArray(['@type' => 'MedicalProcedure', 'name' => 'Test']);
});

it('moderates reviews and applies related tokens on update', function () {
    $user = operationsManagerUser();
    $service = Service::factory()->create(['service_code' => 'primary-svc']);
    $related = Service::factory()->create(['service_code' => 'related-svc']);

    $page = Page::withoutEvents(fn () => Page::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Primary detail',
        'slug' => 'service-primary-svc',
        'content' => "{{block:service-detail-hero}}\n{{block:service-detail-related}}",
        'is_active' => true,
    ]));
    $service->forceFill(['detail_page_id' => $page->id])->save();

    $review = Review::query()->create([
        'user_id' => $user->id,
        'service_id' => $service->id,
        'rating' => 5,
        'comment' => 'Excellent care',
        'status' => Review::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->put(route('operations.services.update', $service), [
        'title' => $service->title,
        'service_code' => 'primary-svc',
        'publish_status' => 'draft',
        'visibility' => 'public',
        'active_tab' => 'reviews',
        'review_moderation' => [
            ['id' => $review->id, 'status' => Review::STATUS_APPROVED],
        ],
        'related_service_codes' => [$related->service_code],
        'apply_related_to_page' => '1',
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($review->fresh()->status)->toBe(Review::STATUS_APPROVED);
    expect((string) $page->fresh()->content)->toContain('{{service:related-svc}}');
});

it('previews service through production page render path when detail page exists', function () {
    $user = operationsManagerUser();

    Block::query()->updateOrCreate(
        ['block_slug' => 'service-detail-hero'],
        [
            'block_name' => 'Service detail — hero',
            'code' => '<p data-preview-marker>Production path preview</p>',
            'is_active' => true,
        ]
    );

    $service = Service::factory()->create([
        'service_code' => 'preview-path-test',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $page = Page::withoutEvents(fn () => Page::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Preview path',
        'slug' => 'service-preview-path-test',
        'content' => '{{block:service-detail-hero}}',
        'is_active' => true,
    ]));

    $service->forceFill(['detail_page_id' => $page->id])->save();

    $this->actingAs($user)
        ->get(route('operations.services.preview', $service))
        ->assertSuccessful()
        ->assertSee('data-preview-marker', false);
});

it('renders persisted summary on public service fallback page', function () {
    $service = Service::factory()->create([
        'service_code' => 'elder-care',
        'short_summary' => 'Elder care you can trust',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
        'detail_page_id' => null,
    ]);

    $this->get('/services/elder-care')
        ->assertOk()
        ->assertSee('Elder care you can trust', false);
});
