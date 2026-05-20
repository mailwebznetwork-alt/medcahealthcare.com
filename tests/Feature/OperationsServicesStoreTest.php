<?php

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceSeo;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Operations\ServiceDetailPageSeoSync;

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
