<?php

use App\Models\MarketingSetting;
use App\Models\Service;
use Database\Seeders\MedcaPublicPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(MedcaPublicPagesSeeder::class);
    MarketingSetting::query()->updateOrCreate(
        ['id' => 1],
        ['ga4_measurement_id' => 'G-TESTSPRINTA']
    );
});

it('loads ga4 gtag and sprint conversion events on public pages', function () {
    $html = $this->get('/')->assertSuccessful()->getContent();

    expect($html)
        ->toContain('gtag/js?id=G-TESTSPRINTA')
        ->toContain('medcaGa4ConversionEvents')
        ->toContain('phone_click')
        ->toContain('whatsapp_click')
        ->toContain('form_submit')
        ->toContain('__medcaTrackInstalled')
        ->toContain('phone_click')
        ->toContain('form_start');
});

it('includes utm hidden fields in lead capture form', function () {
    $html = view('components.public.lead-capture-form')->render();

    expect($html)->toContain('name="submission_context"');
});

it('keeps breadcrumb schema without visible breadcrumb nav on cms service pages', function () {
    $service = Service::query()->publicListing()->first();
    if ($service === null) {
        $this->markTestSkipped('No public services seeded.');
    }

    $response = $this->get($service->publicUrl());
    $response->assertSuccessful();

    $html = $response->getContent();
    expect($html)->not->toContain('aria-label="Breadcrumb"');

    if (str_contains($html, 'application/ld+json')) {
        expect($html)->toContain('BreadcrumbList');
    }
});

it('does not show mobile floating call fab on public layout', function () {
    $html = $this->get('/')->assertSuccessful()->getContent();

    expect($html)->not->toContain('data-medca-cta="call-fab"');
});

it('registers ga4 conversion events in marketing config', function () {
    $events = config('marketing_automation.ga4_conversion_events');

    expect($events)->toContain('phone_click', 'whatsapp_click', 'form_submit');
});
