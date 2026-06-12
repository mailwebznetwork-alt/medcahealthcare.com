<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Public\ServiceCardImageResolver;

it('falls back to parent category image for service cards', function () {
    $category = ServiceCategory::factory()->create([
        'code' => 'cat-caregiver-services',
        'name' => 'Caregiver Services',
    ]);

    $service = Service::factory()->create([
        'service_code' => 'SRV-alzheimer-s-care',
        'title' => 'Alzheimer\'s Care',
        'featured_image' => null,
    ]);
    $service->categories()->attach($category->id);

    $url = app(ServiceCardImageResolver::class)->urlFor($service->fresh(['categories']));

    expect($url)->toContain('caregiver-services.jpg');
});

it('prefers service featured image when set', function () {
    $service = Service::factory()->create([
        'service_code' => 'SRV-CUSTOM',
        'featured_image' => 'services/custom.jpg',
    ]);

    $url = app(ServiceCardImageResolver::class)->urlFor($service);

    expect($url)->toContain('storage/services/custom.jpg');
});
