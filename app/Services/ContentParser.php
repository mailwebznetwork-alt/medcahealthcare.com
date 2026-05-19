<?php

namespace App\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Block;
use App\Models\Service;
use App\Services\Content\ContentRenderContext;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class ContentParser
{
    /**
     * Maximum nested {{block:slug}} expansion depth — guards against accidental
     * cycles (block A → block B → block A).
     */
    private const int MAX_BLOCK_DEPTH = 4;

    /**
     * Token regex covering the three supported types: block, module, service.
     *
     * Whitespace around the colon and inside braces is tolerant.
     */
    private const string TOKEN_PATTERN = '/\{\{\s*(block|module|service)\s*:\s*([^}]+?)\s*\}\}/';

    /**
     * Standalone service-token regex used to scrub leftover service tokens
     * from a block's code before Blade::render so they do not leak as raw
     * text to the rendered page.
     */
    private const string SERVICE_TOKEN_PATTERN = '/\{\{\s*service\s*:\s*([^}]+?)\s*\}\}/';

    public static function parse(?string $content): string
    {
        return self::parseInternal($content, 0);
    }

    /**
     * Walk page-level content, expand {{block:slug}} references one level
     * (recursively), and register every {{service:CODE}} found inside with
     * the request-scoped collector. This is meant to run BEFORE <head> is
     * rendered, so service Schema.org JSON-LD can be emitted in the page
     * head regardless of how/where the admin chose to display the service.
     *
     * Idempotent — calling parse() afterwards will simply re-register the
     * same services with the collector (deduplication is by service_code).
     */
    public static function preregister(?string $content): void
    {
        if ($content === null || trim($content) === '') {
            return;
        }

        self::preregisterInternal($content, 0);
    }

    private static function preregisterInternal(string $content, int $depth): void
    {
        if (preg_match_all(self::TOKEN_PATTERN, $content, $matches, PREG_SET_ORDER) === false) {
            return;
        }

        foreach ($matches as $row) {
            $type = strtolower(trim($row[1] ?? ''));
            $slug = trim($row[2] ?? '');

            if ($slug === '') {
                continue;
            }

            if ($type === 'service') {
                self::registerServiceTokenWithCollector($slug);

                continue;
            }

            if ($type === 'block' && $depth < self::MAX_BLOCK_DEPTH) {
                $block = Block::query()
                    ->where('block_slug', $slug)
                    ->where('is_active', true)
                    ->first(['id', 'code', 'is_active']);

                if ($block !== null && is_string($block->code) && $block->code !== '') {
                    self::preregisterInternal($block->code, $depth + 1);
                }
            }
        }
    }

    private static function parseInternal(?string $content, int $depth): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        $result = preg_replace_callback(
            self::TOKEN_PATTERN,
            function (array $matches) use ($depth): string {
                $type = strtolower(trim($matches[1]));
                $slug = trim($matches[2]);

                if ($type === 'module') {
                    return self::renderModule($slug);
                }

                if ($type === 'service') {
                    self::registerServiceTokenWithCollector($slug);

                    return '';
                }

                if ($type === 'block') {
                    return self::renderBlock($slug, $depth);
                }

                return '';
            },
            $content
        );

        return $result ?? '';
    }

    private static function renderModule(string $key): string
    {
        if (in_array($key, ['job-portal', 'careers-listing'], true)) {
            return '';
        }

        $class = config('modules.'.$key);

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return '';
        }

        return Livewire::mount($class);
    }

    private static function renderBlock(string $slug, int $depth): string
    {
        if ($depth >= self::MAX_BLOCK_DEPTH) {
            return '';
        }

        $block = Block::query()
            ->where('block_slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($block === null || ! is_string($block->code) || $block->code === '') {
            return '';
        }

        $serviceVars = self::loadServiceVariablesFromBlockCode($block->code);

        $expandedNonServiceTokens = self::parseInternal(
            preg_replace(self::SERVICE_TOKEN_PATTERN, '', $block->code) ?? '',
            $depth + 1
        );

        $bladeReadyCode = $expandedNonServiceTokens;

        $services = collect(array_values($serviceVars))->filter(fn ($v): bool => $v instanceof Service);

        $sharedVars = array_merge(
            app(ContentRenderContext::class)->all(),
            ['services' => $services],
            $serviceVars
        );

        return Blade::render($bladeReadyCode, $sharedVars);
    }

    /**
     * Scan a block's code for {{service:CODE}} tokens, load each published
     * service, expose them as Blade variables (snake-cased code), and
     * register them with the request-scoped collector so the layout's
     * <head> can emit Schema.org JSON-LD.
     *
     * @return array<string, Service>
     */
    private static function loadServiceVariablesFromBlockCode(string $code): array
    {
        $vars = [];

        if (preg_match_all(self::SERVICE_TOKEN_PATTERN, $code, $matches) === false) {
            return $vars;
        }

        foreach ($matches[1] ?? [] as $rawCode) {
            $serviceCode = trim((string) $rawCode);
            if ($serviceCode === '') {
                continue;
            }

            $service = Service::findForBlockBinding($serviceCode);
            if ($service === null) {
                continue;
            }

            $service->loadMissing(['seo', 'faqs', 'pincodes']);

            if (
                $service->publish_status === PublishStatus::Published
                && $service->visibility === ServiceVisibility::Public
            ) {
                self::registerWithCollector($service);
            }

            $vars[$service->bladeVariableName()] = $service;
        }

        return $vars;
    }

    private static function registerServiceTokenWithCollector(string $serviceCode): void
    {
        $service = Service::findPublishedByCode($serviceCode);
        if ($service === null) {
            return;
        }

        $service->loadMissing(['seo', 'faqs', 'pincodes']);
        self::registerWithCollector($service);
    }

    private static function registerWithCollector(Service $service): void
    {
        try {
            app(ServiceContextCollector::class)->register($service);
        } catch (\Throwable) {
            // Collector is best-effort — if the container is misconfigured
            // (e.g., during early bootstrap), token rendering must not fail.
        }
    }
}
