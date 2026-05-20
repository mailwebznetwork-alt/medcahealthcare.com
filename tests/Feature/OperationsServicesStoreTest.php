<?php

use App\Models\Service;
use App\Models\User;
use App\ModuleAccess;

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

it('normalizes nested keyword payloads when creating a service', function () {
    $user = operationsManagerUser();

    $response = $this->actingAs($user)->post(route('operations.services.store'), [
        'title' => 'Physio Consult',
        'service_code' => 'ValidCode',
        'publish_status' => 'published',
        'visibility' => 'public',
        'target_keywords' => [['nested', 'pair'], 'single'],
        'ai_keywords' => [null, '', 'ai-term'],
        'seo' => [
            'focus_keywords' => [['a', 'b'], 'c'],
            'h2' => [['h2-one']],
            'h3' => [],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect();

    $service = Service::query()->where('service_code', 'validcode')->first();
    expect($service)->not->toBeNull();
    expect($service->target_keywords)->toEqual(['nested', 'pair', 'single']);
    expect($service->ai_keywords)->toEqual(['ai-term']);

    $service->load('seo');
    expect($service->seo?->focus_keywords)->toEqual(['a', 'b', 'c']);
    expect($service->seo?->h2)->toEqual(['h2-one']);
});

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

it('persists detail carousel lines when updating a service', function () {
    $user = operationsManagerUser();
    $service = Service::factory()->create([
        'service_code' => 'caregivers',
        'procedures' => null,
    ]);

    $response = $this->actingAs($user)->put(route('operations.services.update', $service), [
        'title' => $service->title,
        'service_code' => 'caregivers',
        'publish_status' => 'published',
        'visibility' => 'public',
        'procedures_lines' => "Injection care\nWound dressing\n",
        'specialized_care_lines' => "Post-surgery care\n",
        'shifts_lines' => "12 hour day\n",
    ]);

    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect();

    $service->refresh();
    expect($service->procedures)->toEqual(['Injection care', 'Wound dressing'])
        ->and($service->specialized_care)->toEqual(['Post-surgery care'])
        ->and($service->shifts)->toEqual(['12 hour day']);
});
