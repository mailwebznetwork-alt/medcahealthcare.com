<?php

namespace App\Services\Deployment;

/**
 * Maps blockSection settings to safe HTML attributes for the medca-block wrapper.
 */
class BlockSectionWrapperBuilder
{
    /**
     * @param  array<string, mixed>  $section
     * @return array{class: string, style: string}
     */
    public function attributes(array $section): array
    {
        $classes = [];
        $styles = [];

        if (! empty($section['background_color']) && is_string($section['background_color'])) {
            $color = $this->sanitizeColor((string) $section['background_color']);
            if ($color !== '') {
                $styles[] = 'background-color:'.$color;
            }
        }

        if (! empty($section['background_image']) && is_string($section['background_image'])) {
            $url = $this->sanitizeUrl((string) $section['background_image']);
            if ($url !== '') {
                $styles[] = 'background-image:url('.$url.')';
                $styles[] = 'background-size:cover';
                $styles[] = 'background-position:center';
            }
        }

        if (! empty($section['padding']) && is_string($section['padding'])) {
            $padding = $this->sanitizeCssSize((string) $section['padding']);
            if ($padding !== '') {
                $styles[] = 'padding:'.$padding;
            }
        }

        if (! empty($section['spacing']) && is_string($section['spacing'])) {
            $margin = $this->sanitizeCssSize((string) $section['spacing']);
            if ($margin !== '') {
                $styles[] = 'margin:'.$margin;
            }
        }

        if (! empty($section['border_radius']) && is_string($section['border_radius'])) {
            $radius = $this->sanitizeCssSize((string) $section['border_radius']);
            if ($radius !== '') {
                $styles[] = 'border-radius:'.$radius;
            }
        }

        if (! empty($section['shadow']) && is_string($section['shadow'])) {
            $shadow = $this->sanitizeShadow((string) $section['shadow']);
            if ($shadow !== '') {
                $styles[] = 'box-shadow:'.$shadow;
            }
        }

        if (array_key_exists('visibility_desktop', $section) && ! $this->truthful($section['visibility_desktop'])) {
            $classes[] = 'medca-block--hide-desktop';
        }

        if (array_key_exists('visibility_tablet', $section) && ! $this->truthful($section['visibility_tablet'])) {
            $classes[] = 'medca-block--hide-tablet';
        }

        if (array_key_exists('visibility_mobile', $section) && ! $this->truthful($section['visibility_mobile'])) {
            $classes[] = 'medca-block--hide-mobile';
        }

        return [
            'class' => implode(' ', $classes),
            'style' => implode(';', $styles),
        ];
    }

    private function sanitizeColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^[a-zA-Z]+$/', $value) === 1) {
            return $value;
        }

        return '';
    }

    private function sanitizeUrl(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return preg_replace('/["\')]/', '', $value) ?? '';
        }

        if (preg_match('#^[/a-zA-Z0-9._-]+$#', $value) === 1) {
            return '/'.ltrim($value, '/');
        }

        return '';
    }

    private function sanitizeCssSize(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%|vh|vw)$/', $value) === 1) {
            return $value;
        }

        return '';
    }

    private function sanitizeShadow(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^[\d.\s,a-zA-Z#%-]+$/', $value) === 1 && strlen($value) <= 120) {
            return $value;
        }

        return '';
    }

    private function truthful(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
