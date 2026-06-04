<?php

use App\Models\LeadIntentEvent;
use App\Models\MarketingClickEvent;
use App\Services\Marketing\Tracking\MarketingTrackingValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('accepts tel urls with country code in validator', function () {
    $validator = app(MarketingTrackingValidator::class);
    $data = $validator->validate(Request::create('/', 'POST', [
        'event_type' => 'phone_click',
        'destination_url' => 'tel:+918884999002',
    ]));

    expect($data['destination_url'])->toBe('tel:+918884999002');
});

it('accepts tel urls without plus prefix in validator', function () {
    $validator = app(MarketingTrackingValidator::class);
    $data = $validator->validate(Request::create('/', 'POST', [
        'event_type' => 'phone_click',
        'destination_url' => 'tel:918884999002',
    ]));

    expect($data['destination_url'])->toBe('tel:918884999002');
});

it('records phone_click with tel url to marketing and lead intent', function () {
    if (! Schema::hasTable('lead_intent_events')) {
        $this->markTestSkipped();
    }

    $this->postJson(route('marketing.track'), [
        'event_type' => 'phone_click',
        'destination_url' => 'tel:+918884999002',
        'page_path' => '/contact',
        'source' => 'google',
        'medium' => 'cpc',
        'campaign' => 'prod_patch',
        'session_fingerprint' => 'phone-tel-'.uniqid(),
    ])
        ->assertOk()
        ->assertJson(['recorded' => true]);

    $click = MarketingClickEvent::query()->where('event_type', 'phone_click')->latest('id')->first();
    expect($click)->not->toBeNull()
        ->and($click->destination_url)->toBe('tel:+918884999002');

    $intent = LeadIntentEvent::query()->where('marketing_click_event_id', $click->id)->first();
    expect($intent)->not->toBeNull()
        ->and($intent->intent_type)->toBe('phone_click')
        ->and($intent->channel->value)->toBe('calls');
});

it('rejects invalid tel urls with too few digits', function () {
    $this->postJson(route('marketing.track'), [
        'event_type' => 'phone_click',
        'destination_url' => 'tel:123',
        'session_fingerprint' => 'phone-bad-'.uniqid(),
    ])->assertUnprocessable();
});
