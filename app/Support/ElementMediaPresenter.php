<?php

namespace App\Support;

use App\Models\Media;
use App\Services\Media\MediaPublicUrl;

class ElementMediaPresenter
{
    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    public function settings(array $section, ?string $presetKey = null): array
    {
        $defaults = config('element_media.defaults', []);
        $preset = $presetKey ? (config('element_media.layout_presets.'.$presetKey) ?? []) : [];

        return array_replace($defaults, $preset, $section);
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<string>
     */
    public function wrapperClasses(array $section, ?string $presetKey = null): array
    {
        $s = $this->settings($section, $presetKey);
        $classes = ['medca-element-media'];

        $position = (string) ($s['position'] ?? $s['image_position'] ?? 'center');
        $classes[] = 'medca-element-media--pos-'.$position;

        $style = (string) ($s['style'] ?? $s['image_style'] ?? 'default');
        $classes[] = 'medca-element-media--style-'.$style;

        $sizeMode = (string) ($s['size_mode'] ?? $s['image_size_mode'] ?? 'auto');
        $classes[] = 'medca-element-media--fit-'.$sizeMode;

        foreach (['desktop', 'tablet', 'mobile'] as $bp) {
            $align = (string) ($s['alignment_'.$bp] ?? $s['image_alignment_'.$bp] ?? 'center');
            $classes[] = 'medca-element-media--align-'.$bp.'-'.$align;
        }

        if (! empty($s['background_parallax']) || ! empty($s['image_parallax'])) {
            $classes[] = 'medca-element-media--parallax';
        }
        if (! empty($s['background_fixed']) || ! empty($s['image_fixed_background'])) {
            $classes[] = 'medca-element-media--fixed-bg';
        }

        return $classes;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, string|null>
     */
    public function inlineStyles(array $section, ?string $presetKey = null): array
    {
        $s = $this->settings($section, $presetKey);
        $styles = [];

        $overlay = $s['overlay_opacity'] ?? $s['background_overlay_opacity'] ?? $s['image_overlay'] ?? null;
        if ($overlay !== null && $overlay !== '') {
            $styles['--medca-overlay-opacity'] = ((int) $overlay / 100);
        }

        if (filled($s['image_opacity'] ?? null)) {
            $styles['--medca-image-opacity'] = ((int) $s['image_opacity'] / 100);
        }

        if (filled($s['image_border_radius'] ?? $s['border_radius'] ?? null)) {
            $styles['--medca-radius'] = (string) $s['image_border_radius'];
        }

        $overlayColor = $s['background_overlay_color'] ?? $s['overlay'] ?? null;
        if (filled($overlayColor)) {
            $styles['--medca-overlay-color'] = (string) $overlayColor;
        }

        return $styles;
    }

    public function layoutClass(?string $presetKey): string
    {
        if ($presetKey === null || $presetKey === '') {
            return '';
        }

        return 'medca-layout-'.str_replace('_', '-', $presetKey);
    }

    /**
     * @return array{src: string, srcset: string, sizes: string, avif: string|null, alt: string}
     */
    public function responsiveSources(Media $media, string $sizes = '(max-width: 768px) 100vw, 1200px'): array
    {
        $fallback = MediaPublicUrl::forPath($media->webp_path ?? $media->optimized_path ?? $media->file_path);
        $small = MediaPublicUrl::forPath($media->small_path);
        $medium = MediaPublicUrl::forPath($media->medium_path);
        $large = MediaPublicUrl::forPath($media->large_path ?? $media->webp_path);
        $avif = MediaPublicUrl::forPath($media->avif_path);

        $pieces = array_values(array_filter([
            $small !== '' ? $small.' 480w' : null,
            $medium !== '' ? $medium.' 768w' : null,
            $large !== '' ? $large.' 1200w' : null,
        ]));

        return [
            'src' => $small !== '' ? $small : $fallback,
            'srcset' => implode(', ', $pieces),
            'sizes' => $sizes,
            'avif' => $avif !== '' ? $avif : null,
            'alt' => (string) ($media->alt_text ?? ''),
        ];
    }
}
