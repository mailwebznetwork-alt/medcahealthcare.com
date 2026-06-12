<?php

namespace App\Services\Public;

use Illuminate\Support\Facades\Cache;

/**
 * Renders Lucide line icons as inline SVG (public site, no JS required).
 */
final class LucideSvgRenderer
{
    private const STROKE_WIDTH = 1.75;

    private const VIEWBOX = '0 0 24 24';

    private const SIZE_PX = [
        'medca-line-icon--xs' => 16,
        'medca-line-icon--sm' => 18,
        'medca-line-icon--md' => 20,
        'medca-line-icon--lg' => 24,
        'medca-line-icon--xl' => 32,
    ];

    public function svg(string $name, string $class = '', ?string $label = null): string
    {
        $name = $this->normalizeName($name);
        $paths = $this->pathsFor($name);

        if ($paths === []) {
            $paths = $this->pathsFor('circle');
        }

        $classAttr = trim('medca-line-icon '.$class);
        $sizePx = 20;
        foreach (self::SIZE_PX as $sizeClass => $px) {
            if (str_contains($classAttr, $sizeClass)) {
                $sizePx = $px;
                break;
            }
        }

        $aria = $label !== null
            ? ' role="img" aria-label="'.e($label).'"'
            : ' aria-hidden="true"';

        $pathMarkup = implode('', array_map(
            static fn (string $d): string => '<path d="'.e($d).'" />',
            $paths
        ));

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="%d" height="%d" fill="none" stroke="currentColor" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" class="%s"%s>%s</svg>',
            self::VIEWBOX,
            $sizePx,
            $sizePx,
            self::STROKE_WIDTH,
            e($classAttr),
            $aria,
            $pathMarkup
        );
    }

    /**
     * @return list<string>
     */
    public function pathsFor(string $name): array
    {
        $name = $this->normalizeName($name);

        return Cache::remember('lucide_paths_'.$name, now()->addDay(), function () use ($name): array {
            $bundled = config('catalog_line_icons.paths.'.$name);
            if (is_array($bundled) && $bundled !== []) {
                return array_values(array_map('strval', $bundled));
            }

            return $this->pathsFromNodeModule($name);
        });
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = str_replace('_', '-', $name);

        return preg_replace('/[^a-z0-9-]/', '', $name) ?? 'circle';
    }

    /**
     * @return list<string>
     */
    private function pathsFromNodeModule(string $name): array
    {
        $file = base_path('node_modules/lucide/dist/esm/icons/'.$name.'.mjs');
        if (! is_file($file)) {
            return [];
        }

        $content = (string) file_get_contents($file);
        if (! preg_match_all('/\["path",\s*\{\s*d:\s*"([^"]+)"/', $content, $matches)) {
            return [];
        }

        return array_values($matches[1]);
    }
}
