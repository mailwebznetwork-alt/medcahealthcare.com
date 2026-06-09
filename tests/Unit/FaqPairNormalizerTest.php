<?php

use App\Services\Import\ImportSupport;
use App\Support\FaqPairNormalizer;

it('parses standard faq import strings', function () {
    $pairs = ImportSupport::parseFaqPairs('How do I book?|Book online.;;Are staff verified?|Yes.');

    expect($pairs)->toHaveCount(2)
        ->and($pairs[0]['question'])->toBe('How do I book?')
        ->and($pairs[0]['answer'])->toBe('Book online.');
});

it('parses legacy Q/A faq import strings', function () {
    $value = 'Q: How do I book General Nursing Care? A: Book via our website or call us.|Q: Are the professionals certified? A: Yes, all staff are rigorously verified.';

    $pairs = ImportSupport::parseFaqPairs($value);

    expect($pairs)->toHaveCount(2)
        ->and($pairs[0]['question'])->toBe('How do I book General Nursing Care?')
        ->and($pairs[0]['answer'])->toBe('Book via our website or call us.')
        ->and($pairs[1]['question'])->toBe('Are the professionals certified?')
        ->and($pairs[1]['answer'])->toBe('Yes, all staff are rigorously verified.');
});

it('expands malformed stored faq rows for display', function () {
    $pairs = FaqPairNormalizer::expandStoredPair(
        'Q: How do I book General Nursing Care? A: Book via our website or call us.',
        'Q: Are the professionals certified? A: Yes, all staff are rigorously verified.'
    );

    expect($pairs)->toHaveCount(2)
        ->and($pairs[0]['question'])->not->toStartWith('Q:')
        ->and($pairs[0]['answer'])->not->toStartWith('Q:');
});
