<?php

use App\Services\LeadSourceResolver;
use App\Enums\LeadSource;

it('resolves linkedin and email utm sources', function () {
    $resolver = app(LeadSourceResolver::class);

    expect($resolver->resolve(null, 'linkedin_cpc'))->toBe(LeadSource::LinkedIn)
        ->and($resolver->resolve(null, 'email_newsletter'))->toBe(LeadSource::Email)
        ->and($resolver->resolve(null, 'direct'))->toBe(LeadSource::Direct)
        ->and($resolver->resolve(null, null, 'gclid-abc'))->toBe(LeadSource::GoogleAds)
        ->and($resolver->resolve(null, null, null, 'fbclid-xyz'))->toBe(LeadSource::MetaAds)
        ->and($resolver->resolve(null, 'gmb'))->toBe(LeadSource::Gmb)
        ->and($resolver->resolve(null, 'referral'))->toBe(LeadSource::Referral);
});

it('disables click tracking when feature flag off', function () {
    config(['marketing_automation.click_tracking.enabled' => false]);

    $this->postJson(route('marketing.track'), [
        'event_type' => 'whatsapp_click',
        'session_fingerprint' => 'flag-test',
    ])->assertOk()->assertJson(['recorded' => false]);
});
