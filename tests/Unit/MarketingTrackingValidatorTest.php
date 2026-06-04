<?php

namespace Tests\Unit;

use App\Services\Marketing\Attribution\DeviceContextResolver;
use App\Services\Marketing\Tracking\MarketingTrackingValidator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketingTrackingValidatorTest extends TestCase
{
    public function test_accepts_whitelisted_event_types(): void
    {
        $validator = app(MarketingTrackingValidator::class);
        $request = Request::create('/', 'POST', [
            'event_type' => 'whatsapp_click',
            'page_path' => '/home',
        ]);

        $data = $validator->validate($request);
        $this->assertSame('whatsapp_click', $data['event_type']);
    }

    public function test_rejects_unknown_event_types(): void
    {
        $validator = app(MarketingTrackingValidator::class);
        $request = Request::create('/', 'POST', [
            'event_type' => 'spam_event',
        ]);

        $this->expectException(ValidationException::class);
        $validator->validate($request);
    }

    public function test_accepts_tel_destination_urls(): void
    {
        $validator = app(MarketingTrackingValidator::class);

        foreach (['tel:+918884999002', 'tel:918884999002'] as $tel) {
            $data = $validator->validate(Request::create('/', 'POST', [
                'event_type' => 'phone_click',
                'destination_url' => $tel,
            ]));
            $this->assertSame($tel, $data['destination_url']);
        }
    }

    public function test_rejects_malformed_tel_destination_urls(): void
    {
        $validator = app(MarketingTrackingValidator::class);
        $request = Request::create('/', 'POST', [
            'event_type' => 'phone_click',
            'destination_url' => 'tel:12',
        ]);

        $this->expectException(ValidationException::class);
        $validator->validate($request);
    }

    public function test_device_context_detects_mobile(): void
    {
        $resolver = app(DeviceContextResolver::class);
        $context = $resolver->resolve('Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)');

        $this->assertSame('mobile', $context['device_type']);
    }
}
