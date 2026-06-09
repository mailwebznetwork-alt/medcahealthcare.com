<?php

use App\Support\BlockContent;

it('normalizes branding phone tel into a tel href', function () {
    config(['medca.phone_tel' => '+919999999999']);

    expect(BlockContent::telHref())->toBe('tel:+919999999999');
});

it('renders Call Us before WhatsApp in the lead action bar', function () {
    config([
        'medca.phone_tel' => '+919999999999',
        'medca.whatsapp_url' => 'https://wa.me/919888888888',
    ]);

    $html = view('components.public.lead-action-bar')->render();

    expect($html)
        ->toContain('Call Us')
        ->toContain('WhatsApp Us')
        ->toContain('tel:+919999999999')
        ->toContain('https://wa.me/919888888888')
        ->and(strpos($html, 'Call Us'))->toBeLessThan(strpos($html, 'WhatsApp Us'));
});

it('renders Call Us before WhatsApp in the home hero block', function () {
    config([
        'medca.phone_tel' => '+919999999999',
        'medca.whatsapp_url' => 'https://wa.me/919888888888',
    ]);

    $html = view('blocks.home.hero-home', [
        'blockSettings' => [],
        'blockMedia' => [],
    ])->render();

    expect($html)
        ->toContain('Call Us')
        ->toContain('>Call Us</a>')
        ->and(strpos($html, 'Call Us'))->toBeLessThan(strpos($html, 'WhatsApp Us'));
});
