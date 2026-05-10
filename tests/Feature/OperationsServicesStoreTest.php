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
