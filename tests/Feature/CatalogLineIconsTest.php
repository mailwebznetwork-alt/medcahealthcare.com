<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Public\CatalogLineIconMapper;
use App\Services\Public\CatalogLineIconResolver;
use App\Services\Public\LucideSvgRenderer;
use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;

test('catalog line icons resolve from database and render lucide svg', function (): void {
    $category = ServiceCategory::factory()->create([
        'code' => 'cat-caregiver-services',
        'name' => 'Caregiver Services',
        'line_icon' => 'heart-handshake',
    ]);

    $service = Service::factory()->create([
        'service_code' => 'SRV-elderly-care',
        'title' => 'Elderly Care',
        'line_icon' => 'users',
        'key_benefits' => [
            ['label' => '24/7 Support', 'icon' => 'headset'],
            'Certified Experts',
        ],
    ]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'SUB-elderly-care-bathing-assistance',
        'title' => 'Bathing Assistance',
        'line_icon' => 'shower-head',
        'is_active' => true,
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
    ]);

    $resolver = app(CatalogLineIconResolver::class);
    $renderer = app(LucideSvgRenderer::class);

    expect($resolver->forCategory($category))->toBe('heart-handshake')
        ->and($resolver->forService($service))->toBe('users')
        ->and($resolver->forSubService($sub))->toBe('shower-head');

    $benefits = $resolver->keyBenefitsFor($service);
    expect($benefits)->toHaveCount(2)
        ->and($benefits[0]['icon'])->toBe('headset')
        ->and($benefits[1]['icon'])->toBe('badge-check');

    $svg = $renderer->svg('users', 'medca-line-icon--md');
    expect($svg)->toContain('<svg')->toContain('stroke="currentColor"')->not->toContain('fill="currentColor"');
});

test('catalog line icon mapper assigns keyword icons for services and benefits', function (): void {
    $mapper = app(CatalogLineIconMapper::class);

    expect($mapper->serviceIcon('SRV-dementia-care', 'Dementia Care'))->toBe('brain')
        ->and($mapper->subServiceIcon('SUB-x-feeding-assistance', 'Feeding Assistance'))->toBe('utensils')
        ->and($mapper->benefitIcon('Personalized Care Plans'))->toBe('clipboard-list');
});

test('service detail page renders line icons for key benefits and sub-services', function (): void {
    $service = Service::factory()->create([
        'service_code' => 'SRV-test-icons',
        'title' => 'Icon Test Service',
        'is_active' => true,
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'key_benefits' => [
            ['label' => 'Quality Care', 'icon' => 'heart-pulse'],
        ],
    ]);

    SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'SUB-test-icons-mobility',
        'title' => 'Mobility Assistance',
        'line_icon' => 'accessibility',
        'is_active' => true,
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
    ]);

    $html = view('components.public.service-detail-body', ['service' => $service->fresh(['subServices'])])->render();

    expect($html)
        ->toContain('medca-svc-detail-benefit-icon')
        ->toContain('medca-svc-detail-sub-icon')
        ->toContain('Mobility Assistance')
        ->toContain('Quality Care');
});
