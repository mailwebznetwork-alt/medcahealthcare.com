<?php

namespace App\Http\Requests\Operations\Services\Concerns;

use Illuminate\Support\Arr;

trait NormalizesServiceKeywordArrays
{
    use ConvertsMultilineInput;

    /**
     * Flatten odd POST shapes (nested arrays, etc.) so `*.string` validation passes.
     */
    protected function normalizeServiceKeywordArrays(): void
    {
        $seo = $this->input('seo', []);
        if (! is_array($seo)) {
            $seo = [];
        }

        if ($this->has('seo.focus_keywords_lines')) {
            $seo['focus_keywords'] = $this->linesToStringList($seo['focus_keywords_lines'] ?? '');
        } else {
            $seo['focus_keywords'] = $this->flattenStringList($seo['focus_keywords'] ?? null);
        }

        if ($this->has('seo.h2_lines')) {
            $seo['h2'] = $this->linesToStringList($seo['h2_lines'] ?? '');
        } else {
            $seo['h2'] = $this->flattenStringList($seo['h2'] ?? null);
        }

        if ($this->has('seo.h3_lines')) {
            $seo['h3'] = $this->linesToStringList($seo['h3_lines'] ?? '');
        } else {
            $seo['h3'] = $this->flattenStringList($seo['h3'] ?? null);
        }

        unset($seo['focus_keywords_lines'], $seo['h2_lines'], $seo['h3_lines']);

        $targetKeywords = $this->has('target_keywords_lines')
            ? $this->linesToStringList($this->input('target_keywords_lines'))
            : $this->flattenStringList($this->input('target_keywords'));

        $aiKeywords = $this->has('ai_keywords_lines')
            ? $this->linesToStringList($this->input('ai_keywords_lines'))
            : $this->flattenStringList($this->input('ai_keywords'));

        $this->merge([
            'target_keywords' => $targetKeywords,
            'ai_keywords' => $aiKeywords,
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
