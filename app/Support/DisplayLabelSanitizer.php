<?php

namespace App\Support;

class DisplayLabelSanitizer
{
    public static function clean(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        $cleaned = preg_replace('/^Top[- ]Rated\s+/iu', '', $label);

        return trim((string) $cleaned);
    }
}
