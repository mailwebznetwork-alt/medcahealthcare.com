<?php

namespace App\Services\Theme;

use App\Support\TypographyTypeScale;

class TypographyScaleResolver
{
    /**
     * @param  array{heading_font?: string, body_font?: string, scale?: string, line_height?: string, letter_spacing?: string}  $typography
     */
    public function cssBlock(array $typography): string
    {
        $headingFont = $this->escapeFont($typography['heading_font'] ?? config('typography.defaults.heading_font', 'Plus Jakarta Sans'));
        $bodyFont = $this->escapeFont($typography['body_font'] ?? config('typography.defaults.body_font', 'Noto Sans'));
        $scaleKey = $this->normalizeScaleKey($typography['scale'] ?? 'default');
        $scaleMultiplier = (float) config('typography.scale_multipliers.'.$scaleKey, 1);
        $basePx = (int) config('typography.body_base_px.'.$scaleKey, 16);
        $rootRem = round(($basePx / 16) * $scaleMultiplier, 4);

        $lineHeight = $this->escapeCss($typography['line_height'] ?? '1.5');
        $letterSpacing = $this->escapeCss($typography['letter_spacing'] ?? 'normal');

        $tabletMax = (string) config('typography.breakpoints.tablet_max', '1023px');
        $mobileMax = (string) config('typography.breakpoints.mobile_max', '767px');

        $elements = $this->resolveElements($typography);
        $desktopVars = $this->variableMap($elements, 'desktop');
        $desktopVars['--medca-font-heading'] = "\"{$headingFont}\"";
        $desktopVars['--medca-font-body'] = "\"{$bodyFont}\"";
        $desktopVars['--medca-text-root'] = "{$rootRem}rem";
        $desktopVars['--medca-public-body-font-size'] = $this->publicMinimumRem('desktop').'rem';
        $tabletVars = $this->variableMap($elements, 'tablet');

        $mobileVars = $this->variableMap($elements, 'mobile');
        $mobileVars['--medca-public-min-font-size'] = $this->publicMinimumRem('mobile').'rem';

        $blocks = [];
        $blocks[] = $this->variablesBlock('body.medca-public-surface', $desktopVars, $lineHeight, $letterSpacing);
        $blocks[] = "@media (max-width: {$tabletMax}) {\n".$this->variablesBlock('body.medca-public-surface', $tabletVars)."\n}";
        $blocks[] = "@media (max-width: {$mobileMax}) {\n".$this->variablesBlock('body.medca-public-surface', $mobileVars)."\n}";

        $blocks[] = $this->elementRules($elements, $headingFont, $bodyFont);

        return implode("\n\n", $blocks);
    }

    /**
     * Full Appearance spec for admin UI (Desktop / Tablet / Mobile).
     *
     * @return array{
     *     heading_font: string,
     *     body_font: string,
     *     desktop: list<array{label: string, family: string, size: string, weight: int, line_height: string}>,
     *     tablet: list<array{label: string, family: string, size: string, weight: int, line_height: string}>,
     *     mobile: list<array{label: string, family: string, size: string, weight: int, line_height: string}>
     * }
     */
    /**
     * @param  array<string, mixed>  $typography
     * @return array{
     *     heading_font: string,
     *     body_font: string,
     *     desktop: list<array{label: string, family: string, size: string, weight: int, line_height: string}>,
     *     tablet: list<array{label: string, family: string, size: string, weight: int, line_height: string}>,
     *     mobile: list<array{label: string, family: string, size: string, weight: int, line_height: string}>
     * }
     */
    public function fullSpec(array $typography = []): array
    {
        $headingFont = $this->escapeFont($typography['heading_font'] ?? (string) config('typography.defaults.heading_font'));
        $bodyFont = $this->escapeFont($typography['body_font'] ?? (string) config('typography.defaults.body_font'));

        return [
            'heading_font' => $headingFont,
            'body_font' => $bodyFont,
            'desktop' => $this->specTableForBreakpoint('desktop', $typography, $headingFont, $bodyFont),
            'tablet' => $this->specTableForBreakpoint('tablet', $typography, $headingFont, $bodyFont),
            'mobile' => $this->specTableForBreakpoint('mobile', $typography, $headingFont, $bodyFont),
        ];
    }

    /**
     * @param  array<string, mixed>  $typography
     * @return list<array{label: string, family: string, size: string, weight: int, line_height: string}>
     */
    public function specTableForBreakpoint(
        string $breakpoint,
        array $typography = [],
        ?string $headingFont = null,
        ?string $bodyFont = null,
    ): array {
        $headingFont = $headingFont ?? $this->escapeFont($typography['heading_font'] ?? (string) config('typography.defaults.heading_font'));
        $bodyFont = $bodyFont ?? $this->escapeFont($typography['body_font'] ?? (string) config('typography.defaults.body_font'));
        $elements = $this->resolveElements($typography);
        $rows = [];

        foreach (TypographyTypeScale::elementLabels() as $key => $label) {
            $def = $elements[$key] ?? [];
            $bp = is_array($def[$breakpoint] ?? null) ? $def[$breakpoint] : [];
            $family = ($def['family'] ?? 'body') === 'heading' ? $headingFont : $bodyFont;
            $sizeRem = round((float) ($bp['size'] ?? 1), 4);
            $px = round($sizeRem * 16);

            $rows[] = [
                'label' => $label,
                'family' => $family,
                'size' => "{$px}px ({$sizeRem}rem)",
                'weight' => (int) ($bp['weight'] ?? 400),
                'line_height' => (string) ($bp['line_height'] ?? 1.5),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $typography
     * @return array<string, array{family: string, desktop: array<string, mixed>, tablet: array<string, mixed>, mobile: array<string, mixed>}>
     */
    private function resolveElements(array $typography): array
    {
        if (isset($typography['type_scale']) && is_array($typography['type_scale'])) {
            return TypographyTypeScale::normalize($typography['type_scale']);
        }

        return TypographyTypeScale::defaults();
    }

    /**
     * @param  array<string, mixed>  $elements
     * @return array<string, string>
     */
    private function variableMap(
        array $elements,
        string $breakpoint,
    ): array {
        $vars = [];

        foreach ($elements as $key => $def) {
            if (! is_array($def)) {
                continue;
            }

            $bp = is_array($def[$breakpoint] ?? null) ? $def[$breakpoint] : [];
            $slug = str_replace('_', '-', $key);
            $sizeRem = round((float) ($bp['size'] ?? 1), 4);
            $sizeRem = max($sizeRem, $this->publicBodyMinimumRem($breakpoint, $key));
            $vars["--medca-text-{$slug}-size"] = "{$sizeRem}rem";
            $vars["--medca-text-{$slug}-weight"] = (string) (int) ($bp['weight'] ?? 400);
            $vars["--medca-text-{$slug}-line-height"] = (string) ($bp['line_height'] ?? 1.5);
        }

        return $vars;
    }

    /**
     * @param  array<string, string>  $vars
     */
    private function variablesBlock(
        string $selector,
        array $vars,
        ?string $lineHeight = null,
        ?string $letterSpacing = null,
    ): string {
        $lines = collect($vars)
            ->map(fn (string $value, string $name): string => "    {$name}: {$value};")
            ->values()
            ->all();

        if ($lineHeight !== null) {
            $lines[] = '    font-family: var(--medca-font-body), ui-sans-serif, system-ui, sans-serif;';
            $lines[] = '    font-size: var(--medca-text-root);';
            $lines[] = "    line-height: {$lineHeight};";
            $lines[] = "    letter-spacing: {$letterSpacing};";
        }

        return $selector." {\n".implode("\n", $lines)."\n}";
    }

    /**
     * @param  array<string, mixed>  $elements
     */
    private function elementRules(array $elements, string $headingFont, string $bodyFont): string
    {
        $rules = [];

        foreach ($elements as $key => $def) {
            if (! is_array($def)) {
                continue;
            }

            $slug = str_replace('_', '-', $key);
            $familyKey = ($def['family'] ?? 'body') === 'heading' ? 'heading' : 'body';
            $fontFamily = $familyKey === 'heading' ? $headingFont : $bodyFont;
            $rule = "    font-family: \"{$fontFamily}\", ui-sans-serif, system-ui, sans-serif;\n"
                ."    font-size: var(--medca-text-{$slug}-size);\n"
                ."    font-weight: var(--medca-text-{$slug}-weight);\n"
                ."    line-height: var(--medca-text-{$slug}-line-height);\n";

            if (str_starts_with($key, 'h') && strlen($key) === 2 && is_numeric($key[1])) {
                $rules[] = "body.medca-public-surface {$key} {\n{$rule}}";

                continue;
            }

            $selector = match ($key) {
                'body_large' => '.medca-text-body-lg, .mom-body-text',
                'body_regular' => '.medca-text-body',
                'small' => '.medca-text-small, .mom-subtext, .mom-micro',
                'button' => '.medca-cta-solid, .medca-cta-on-hero, .btn-premium, button.medca-cta-compact',
                default => null,
            };

            if ($selector !== null) {
                $rules[] = "body.medca-public-surface {$selector} {\n{$rule}}";
            }
        }

        return implode("\n\n", $rules);
    }

    private function normalizeScaleKey(string $scale): string
    {
        return in_array($scale, ['compact', 'default', 'large'], true) ? $scale : 'default';
    }

    private function publicMinimumRem(string $breakpoint): float
    {
        $mins = config('typography.public_minimum', []);

        return match ($breakpoint) {
            'mobile' => (float) ($mins['mobile'] ?? 1),
            'desktop' => (float) ($mins['desktop'] ?? 1.125),
            default => 0,
        };
    }

    private function publicBodyMinimumRem(string $breakpoint, string $elementKey): float
    {
        $bodyKeys = ['body_large', 'body_regular', 'small'];

        if (! in_array($elementKey, $bodyKeys, true)) {
            return 0;
        }

        return match ($breakpoint) {
            'mobile' => $this->publicMinimumRem('mobile'),
            'desktop', 'tablet' => $this->publicMinimumRem('desktop'),
            default => 0,
        };
    }

    private function escapeFont(string $font): string
    {
        return str_replace(['"', '\\'], '', trim($font));
    }

    private function escapeCss(string $value): string
    {
        return preg_replace('/[;{}]/', '', $value) ?? $value;
    }
}
