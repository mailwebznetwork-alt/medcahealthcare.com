<?php

namespace App\Support;

/**
 * Detects placeholder / Faker / test artifact content that must not exist in production.
 */
final class FakerContentGuard
{
    /** @var list<string> */
    private const BLOCKED_FRAGMENTS = [
        'example.com',
        'example.net',
        'example.org',
        'test.com',
        'lorem ipsum',
        '@example.',
        '@test.com',
    ];

    /**
     * Codes created by automated tests or debugging that must never ship to production.
     *
     * @var list<string>
     */
    private const KNOWN_TEST_CODES = [
        'dolor-aut',
        'dolor-ut',
        'nihil-facilis',
        'med-lab-nav',
        'med-lab-nav-unique',
        'srv-live-test',
    ];

    /**
     * Common Latin tokens from Faker's en_US word generator.
     *
     * @var list<string>
     */
    private const LATIN_FAKER_TOKENS = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
        'nihil', 'facilis', 'voluptatum', 'architecto', 'blanditiis', 'molestias',
        'quibusdam', 'aspernatur', 'tempora', 'accusamus', 'perferendis', 'veniam',
        'quas', 'corrupti', 'aperiam', 'expedita', 'debitis', 'sapiente', 'commodi',
        'praesentium', 'incidunt', 'ducimus', 'iusto', 'quos', 'aliquid', 'culpa',
        'placeat', 'vitae', 'rerum', 'enim', 'laboriosam', 'fugiat', 'fugit',
        'provident', 'officia', 'consequatur', 'repellat', 'eligendi', 'autem',
        'obcaecati', 'distinctio', 'reiciendis', 'voluptate', 'similique', 'harum',
    ];

    public function applies(): bool
    {
        return app()->environment('production');
    }

    public function isFakerLike(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        $lower = strtolower($trimmed);

        foreach (self::BLOCKED_FRAGMENTS as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }

        $tokenHits = $this->latinFakerTokenHits($lower);

        if ($tokenHits >= 2) {
            return true;
        }

        if ($tokenHits >= 1 && $this->containsHighConfidenceFakerToken($lower)) {
            return true;
        }

        $normalizedCode = strtolower(str_replace('_', '-', $trimmed));
        if (in_array($normalizedCode, self::KNOWN_TEST_CODES, true)) {
            return true;
        }

        return false;
    }

    public function isCatalogFaker(?string $name, ?string $code, ?string $description = null): bool
    {
        foreach ([$name, $code, $description] as $field) {
            if ($this->isFakerLike($field)) {
                return true;
            }
        }

        $normalizedCode = strtolower(str_replace('_', '-', trim((string) $code)));

        return in_array($normalizedCode, self::KNOWN_TEST_CODES, true);
    }

    public function validationMessage(): string
    {
        return __('Placeholder or test content is not allowed. Use real Medca catalog names and codes.');
    }

    private function latinFakerTokenHits(string $lower): int
    {
        $tokens = preg_split('/[\s\-_\.]+/', $lower) ?: [];
        $hits = 0;

        foreach ($tokens as $token) {
            if ($token === '' || is_numeric($token)) {
                continue;
            }

            if (in_array($token, self::LATIN_FAKER_TOKENS, true)) {
                $hits++;
            }
        }

        return $hits;
    }

    private function containsHighConfidenceFakerToken(string $lower): bool
    {
        $tokens = preg_split('/[\s\-_\.]+/', $lower) ?: [];

        foreach ($tokens as $token) {
            if (in_array($token, ['dolor', 'nihil', 'lorem', 'ipsum', 'facilis', 'blanditiis', 'molestias'], true)) {
                return true;
            }
        }

        return false;
    }
}
