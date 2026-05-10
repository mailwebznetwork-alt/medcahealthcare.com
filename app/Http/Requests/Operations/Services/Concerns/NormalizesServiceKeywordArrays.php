<?php

namespace App\Http\Requests\Operations\Services\Concerns;

use Illuminate\Support\Arr;

trait NormalizesServiceKeywordArrays
{
    /**
     * Flatten odd POST shapes (nested arrays, etc.) so `*.string` validation passes.
     */
    protected function normalizeServiceKeywordArrays(): void
    {
        $seo = $this->input('seo', []);
        if (! is_array($seo)) {
            $seo = [];
        }

        $seo['focus_keywords'] = $this->flattenStringList($seo['focus_keywords'] ?? null);
        $seo['h2'] = $this->flattenStringList($seo['h2'] ?? null);
        $seo['h3'] = $this->flattenStringList($seo['h3'] ?? null);

        $this->merge([
            'target_keywords' => $this->flattenStringList($this->input('target_keywords')),
            'ai_keywords' => $this->flattenStringList($this->input('ai_keywords')),
            'seo' => $seo,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function flattenStringList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            $s = trim((string) $value);

            return $s === '' ? [] : [$s];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                foreach (Arr::flatten($item) as $piece) {
                    $s = trim((string) $piece);
                    if ($s !== '') {
                        $out[] = $s;
                    }
                }

                continue;
            }

            $s = trim((string) $item);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values($out);
    }
}
