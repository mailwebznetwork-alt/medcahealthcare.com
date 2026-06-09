<?php

namespace App\Support;

final class FaqPairNormalizer
{
    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function parseImportString(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $value = trim($value);

        if (self::containsLegacyQaPrefix($value)) {
            return self::parseLegacyBlocks($value);
        }

        $pairs = [];
        foreach (explode(';;', $value) as $chunk) {
            $parts = explode('|', $chunk, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                $pairs[] = [
                    'question' => trim($parts[0]),
                    'answer' => trim($parts[1]),
                ];
            }
        }

        return $pairs;
    }

    /**
     * Expand one stored FAQ row that may contain legacy combined Q/A text.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function expandStoredPair(string $question, string $answer): array
    {
        $question = trim($question);
        $answer = trim($answer);

        if ($question === '' && $answer === '') {
            return [];
        }

        if (self::containsLegacyQaPrefix($question) || self::containsLegacyQaPrefix($answer)) {
            $pairs = [];

            if ($question !== '' && self::containsLegacyQaPrefix($question)) {
                $pairs = array_merge($pairs, self::parseLegacyBlocks($question));
            }

            if ($answer !== '' && self::containsLegacyQaPrefix($answer)) {
                $pairs = array_merge($pairs, self::parseLegacyBlocks($answer));
            }

            return self::dedupePairs($pairs);
        }

        return self::dedupePairs([[
            'question' => $question,
            'answer' => $answer,
        ]]);
    }

    /**
     * @param  iterable<int, object{question: string, answer: string}>  $faqs
     * @return list<array{question: string, answer: string}>
     */
    public static function expandMany(iterable $faqs): array
    {
        $pairs = [];

        foreach ($faqs as $faq) {
            $pairs = array_merge(
                $pairs,
                self::expandStoredPair((string) $faq->question, (string) $faq->answer)
            );
        }

        return self::dedupePairs($pairs);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private static function parseLegacyBlocks(string $value): array
    {
        $normalized = preg_replace('/\s*\|\s*(?=Q:\s)/i', ';;', $value) ?? $value;
        $pairs = [];

        foreach (explode(';;', $normalized) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            if (preg_match('/^Q:\s*(.*?)\s*A:\s*(.*)$/is', $chunk, $matches) !== 1) {
                continue;
            }

            $question = trim($matches[1]);
            $answer = trim($matches[2]);

            if ($question !== '' && $answer !== '') {
                $pairs[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return self::dedupePairs($pairs);
    }

    private static function containsLegacyQaPrefix(string $value): bool
    {
        return (bool) preg_match('/\bQ:\s/i', $value);
    }

    /**
     * @param  list<array{question: string, answer: string}>  $pairs
     * @return list<array{question: string, answer: string}>
     */
    private static function dedupePairs(array $pairs): array
    {
        $seen = [];
        $out = [];

        foreach ($pairs as $pair) {
            $question = trim($pair['question']);
            $answer = trim($pair['answer']);

            if ($question === '' || $answer === '') {
                continue;
            }

            $key = mb_strtolower($question).'|'.mb_strtolower($answer);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = ['question' => $question, 'answer' => $answer];
        }

        return $out;
    }
}
