<?php

namespace App\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Block;
use App\Models\Page;
use App\Models\Service;
use App\Services\Content\ContentRenderContext;
use App\Services\Content\ServiceBindingRegistry;
use App\Services\Deployment\BlockSectionWrapperBuilder;
use App\Services\Deployment\BlockSettingsResolver;
use App\Services\Deployment\GlobalContentInterpolator;
use App\Services\Deployment\SectionLibraryRepository;
use App\Services\DynamicModules\DynamicModuleRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
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
    private const string TOKEN_PATTERN = '/\{\{\s*(block|module|service|section)\s*:\s*([^}]+?)\s*\}\}/';

    /**
     * Standalone service-token regex used to scrub leftover service tokens
     * from a block's code before Blade::render so they do not leak as raw
     * text to the rendered page.
     */
    private const string SERVICE_TOKEN_PATTERN = '/\{\{\s*service\s*:\s*([^}]+?)\s*\}\}/';

    /** @var array<string, Block|null> */
    private static array $blockCache = [];

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
        $content = app(GlobalContentInterpolator::class)->interpolate($content);

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

            if ($type === 'section' && $depth < self::MAX_BLOCK_DEPTH) {
                $section = app(SectionLibraryRepository::class)->find($slug);
                if ($section !== null) {
                    self::preregisterInternal(
                        app(SectionLibraryRepository::class)->expandToContent($section),
                        $depth + 1
                    );
                }

                continue;
            }

            if ($type === 'block' && $depth < self::MAX_BLOCK_DEPTH) {
                $block = Block::query()
                    ->where('block_slug', $slug)
                    ->where('is_active', true)
                    ->first(['id', 'code', 'is_active']);

                if ($block !== null && is_string($block->code) && $block->code !== '') {
                    self::preregisterInternal(
                        app(GlobalContentInterpolator::class)->interpolate($block->code),
                        $depth + 1
                    );
                }
            }
        }
    }

    private static function parseInternal(?string $content, int $depth): string
    {
        if ($depth === 0) {
            self::$blockCache = [];
        }

        if ($content === null || trim($content) === '') {
            return '';
        }

        $content = app(GlobalContentInterpolator::class)->interpolate($content);

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

                if ($type === 'section') {
                    return self::renderSection($slug, $depth);
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

    private static function renderSection(string $slug, int $depth): string
    {
        if ($depth >= self::MAX_BLOCK_DEPTH) {
            return '';
        }

        $section = app(SectionLibraryRepository::class)->find($slug);
        if ($section === null) {
            return '';
        }

        $context = app(ContentRenderContext::class);
        $existing = is_array($context->all()['blockOverrides'] ?? null) ? $context->all()['blockOverrides'] : [];
        $context->merge([
            'blockOverrides' => array_replace_recursive(
                $existing,
                app(SectionLibraryRepository::class)->blockOverrides($section)
            ),
        ]);

        return self::parseInternal(app(SectionLibraryRepository::class)->expandToContent($section), $depth + 1);
    }

    private static function renderModule(string $key): string
    {
        if (in_array($key, ['job-portal', 'careers-listing'], true)) {
            return '';
        }

        $class = config('modules.'.$key);

        if (is_string($class) && $class !== '' && class_exists($class)) {
            return Livewire::mount($class);
        }

        try {
            return app(DynamicModuleRenderer::class)->render($key);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Render a block's Blade code (preview, tests, or internal expansion).
     */
    public static function renderBlockCode(string $code, int $depth = 0, ?string $customCss = null, ?string $blockSlug = null): string
    {
        if (trim($code) === '') {
            return self::wrapBlockOutput('', $customCss, $blockSlug);
        }

        $code = app(GlobalContentInterpolator::class)->interpolate($code);

        $serviceVars = self::loadServiceVariablesFromBlockCode($code);

        $bladeReadyCode = self::parseInternal(
            preg_replace(self::SERVICE_TOKEN_PATTERN, '', $code) ?? '',
            $depth + 1
        );

        $html = Blade::render($bladeReadyCode, self::buildBlockRenderVariables($serviceVars));

        return self::wrapBlockOutput($html, $customCss, $blockSlug);
    }

    /**
     * Prefix block HTML with a scoped <style> tag when custom CSS is set.
     */
    public static function wrapBlockOutput(string $html, ?string $customCss, ?string $blockSlug = null): string
    {
        $css = self::normalizeBlockCustomCss($customCss);
        if ($css === '') {
            return $html;
        }

        $attr = $blockSlug !== null && $blockSlug !== ''
            ? ' data-block="'.e($blockSlug).'"'
            : '';

        return '<style'.$attr.' type="text/css">'."\n".$css."\n".'</style>'."\n".$html;
    }

    /**
     * Strip accidental <style> wrappers and break-out sequences from editor input.
     */
    public static function normalizeBlockCustomCss(?string $customCss): string
    {
        $css = trim((string) ($customCss ?? ''));
        if ($css === '') {
            return '';
        }

        $css = preg_replace('#</?style[^>]*>#i', '', $css) ?? $css;
        $css = preg_replace('#</style#i', '', $css) ?? $css;

        return trim($css);
    }

    /**
     * @param  array<string, Service>  $serviceVars
     * @return array<string, mixed>
     */
    public static function buildBlockRenderVariables(array $serviceVars): array
    {
        $services = collect(array_values($serviceVars))->filter(fn ($v): bool => $v instanceof Service);

        $variables = array_merge(
            self::blockRenderDefaults(),
            app(ContentRenderContext::class)->all(),
            ['services' => $services],
            $serviceVars
        );

        if (($variables['service'] ?? null) === null) {
            $primary = $services->first();
            if ($primary instanceof Service) {
                $variables['service'] = $primary;
            }
        }

        return $variables;
    }

    /**
     * Safe fallbacks so block Blade never 500s when a variable is page-specific.
     *
     * @return array<string, mixed>
     */
    private static function blockRenderDefaults(): array
    {
        $errors = session()->get('errors');
        if (! $errors instanceof ViewErrorBag) {
            $errors = new ViewErrorBag;
        }

        return [
            'vacancies' => Collection::make(),
            'publishedServices' => Collection::make(),
            'services' => Collection::make(),
            'pinCodes' => Collection::make(),
            'sectionTitle' => null,
            'vacancy' => null,
            'service' => null,
            'errors' => $errors,
        ];
    }

    private static function renderBlock(string $slug, int $depth): string
    {
        if ($depth >= self::MAX_BLOCK_DEPTH) {
            return '';
        }

        if (! array_key_exists($slug, self::$blockCache)) {
            self::$blockCache[$slug] = Block::query()
                ->where('block_slug', $slug)
                ->where('is_active', true)
                ->first();
        }

        $block = self::$blockCache[$slug];

        if ($block === null) {
            return '';
        }

        $code = is_string($block->code) ? $block->code : '';
        $customCss = is_string($block->custom_css) ? $block->custom_css : null;

        if ($code === '' && self::normalizeBlockCustomCss($customCss) === '') {
            return '';
        }

        $context = app(ContentRenderContext::class)->all();
        $page = ($context['currentPage'] ?? null) instanceof Page ? $context['currentPage'] : null;
        $stylePackSlug = is_string($context['stylePackSlug'] ?? null) ? $context['stylePackSlug'] : null;

        $settingsVars = app(BlockSettingsResolver::class)->renderVariables(
            $slug,
            $block,
            $page,
            $stylePackSlug
        );

        return self::renderBlockCodeWithVariables(
            $code,
            $depth,
            $customCss,
            $block->block_slug,
            $settingsVars
        );
    }

    /**
     * @param  array<string, mixed>  $extraVariables
     */
    public static function renderBlockCodeWithVariables(
        string $code,
        int $depth,
        ?string $customCss,
        ?string $blockSlug,
        array $extraVariables = [],
    ): string {
        if (trim($code) === '') {
            return self::wrapBlockOutput('', $customCss, $blockSlug);
        }

        $code = app(GlobalContentInterpolator::class)->interpolate($code);

        $serviceVars = self::loadServiceVariablesFromBlockCode($code);

        $bladeReadyCode = self::parseInternal(
            preg_replace(self::SERVICE_TOKEN_PATTERN, '', $code) ?? '',
            $depth + 1
        );

        $html = Blade::render($bladeReadyCode, array_merge(
            self::buildBlockRenderVariables($serviceVars),
            $extraVariables
        ));

        $inner = self::wrapBlockOutput($html, $customCss, $blockSlug);

        if ($depth > 0) {
            return $inner;
        }

        $section = is_array($extraVariables['blockSection'] ?? null)
            ? $extraVariables['blockSection']
            : [];
        $wrapperAttrs = app(BlockSectionWrapperBuilder::class)->attributes($section);

        $wrapperClass = trim(implode(' ', array_filter([
            'medca-block',
            (string) ($extraVariables['blockStyleClass'] ?? ''),
            $wrapperAttrs['class'],
        ])));

        $styleAttr = $wrapperAttrs['style'] !== ''
            ? ' style="'.e($wrapperAttrs['style']).'"'
            : '';

        return '<div class="'.e($wrapperClass).'"'.$styleAttr.' data-block-slug="'.e((string) $blockSlug).'">'.$inner.'</div>';
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

            $registry = app(ServiceBindingRegistry::class);
            $service = $registry->get($serviceCode);

            if ($service === null) {
                $service = Service::findForBlockBinding($serviceCode);
                if ($service === null) {
                    continue;
                }

                $service->loadMissing(['seo', 'faqs', 'pincodes']);
                $registry->remember($serviceCode, $service);
            }

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
