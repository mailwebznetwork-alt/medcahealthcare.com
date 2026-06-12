<?php

namespace App\Livewire\Settings;

use App\Models\ThemeConfiguration;
use App\Services\Deployment\GlobalContentVariableRepository;
use App\Services\Theme\ThemeColorNormalizer;
use App\Services\Theme\ThemeConfigRepository;
use App\Services\Theme\ThemeContrastValidator;
use App\Services\Theme\ThemeCssVariableBuilder;
use App\Services\Theme\ThemePresetRegistry;
use App\Support\TypographyTypeScale;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class AppearanceSettings extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $activeTab = 'branding';

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    /** @var list<string> */
    public array $contrastWarnings = [];

    /** @var array<string, string> */
    public array $tokens = [];

    /** @var array<string, mixed> */
    public array $branding = [];

    /** @var array<string, string> */
    public array $brandStory = [];

    /** @var array<string, mixed> */
    public array $typography = [];

    public string $heading_font_mode = 'preset';

    public string $body_font_mode = 'preset';

    public string $custom_heading_font = '';

    public string $custom_body_font = '';

    public string $header_preset = 'classic_healthcare';

    public string $layout_preset = 'contained';

    /** @var array<string, mixed> */
    public array $header_config = [];

    public string $preset_slug = '';

    public string $clone_name = '';

    public string $import_json = '';

    public $logo_upload;

    public $favicon_upload;

    public function mount(
        ThemeConfigRepository $repository,
        ThemePresetRegistry $presetRegistry,
        GlobalContentVariableRepository $globalContent,
    ): void {
        $user = auth()->user();
        if ($user === null || ! in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true)) {
            abort(403);
        }

        if (! Schema::hasTable('theme_configurations')) {
            return;
        }

        $this->hydrateFromRepository($repository, $presetRegistry);
        $this->loadBrandStoryFields($globalContent);
    }

    public function setTab(string $tab): void
    {
        $allowed = ['branding', 'brand_story', 'colors', 'typography', 'buttons', 'cards', 'header', 'layout', 'presets', 'preview'];
        if (in_array($tab, $allowed, true)) {
            $this->activeTab = $tab;
        }
    }

    public function saveBranding(ThemeConfigRepository $repository): void
    {
        $this->resetMessages();
        $this->validate([
            'branding.brand_name' => ['required', 'string', 'max:120'],
            'branding.tagline' => ['nullable', 'string', 'max:160'],
            'branding.contact_email' => ['nullable', 'email', 'max:190'],
            'branding.brand_url' => ['nullable', 'url', 'max:500'],
            'branding.whatsapp_url' => ['nullable', 'url', 'max:500'],
            'branding.primary_cta_text' => ['nullable', 'string', 'max:80'],
            'logo_upload' => ['nullable', 'image', 'max:2048'],
            'favicon_upload' => ['nullable', 'image', 'max:512'],
        ]);

        $payload = collect($this->branding)->only(config('theme_management.branding_fields', []))->all();

        if ($this->logo_upload) {
            $payload['logo_path'] = $repository->storeUploadedAsset('logo_path', $this->logo_upload);
        }

        if ($this->favicon_upload) {
            $payload['favicon_path'] = $repository->storeUploadedAsset('favicon_path', $this->favicon_upload);
        }

        $repository->saveDraftBranding($payload, auth()->user());
        $this->branding = $repository->draftBranding();
        $this->logo_upload = null;
        $this->favicon_upload = null;
        $this->notice(__('Branding draft saved. Enable preview or publish to apply on the public site.'));
    }

    public function saveBrandStory(GlobalContentVariableRepository $globalContent): void
    {
        $this->resetMessages();

        if (! Schema::hasTable('global_content_variables')) {
            $this->errorMessage = __('Run database migrations to enable brand story content.');

            return;
        }

        $allowedKeys = $globalContent->keysForGroups(['brand_story', 'home', 'contact']);
        $payload = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $this->brandStory)) {
                $payload[$key] = (string) $this->brandStory[$key];
            }
        }

        $globalContent->sync($payload, auth()->user());
        $this->loadBrandStoryFields($globalContent);
        $this->notice(__('Brand story saved. Home, About, and Contact pages use these values on the next render.'));
    }

    public function saveColors(
        ThemeConfigRepository $repository,
        ThemeColorNormalizer $normalizer,
        ThemeContrastValidator $contrastValidator,
    ): void {
        $this->resetMessages();

        $normalized = $normalizer->normalizeMany($this->tokens);
        if (count($normalized) < count($this->tokens)) {
            $this->errorMessage = __('One or more colors are invalid. Use hex format, e.g. #0055ff.');

            return;
        }

        $this->tokens = $normalized;
        $this->contrastWarnings = $contrastValidator->validatePublicTokens($normalized);

        try {
            $repository->saveDraftPublicTokens($this->tokens, auth()->user(), strictContrast: false);
            $this->tokens = $repository->draftPublicTokens();
            $this->notice(__('Color draft saved. Enable preview or publish to apply on the public site.'));
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: __('Unable to save colors.');
        }
    }

    public function saveTypography(ThemeConfigRepository $repository): void
    {
        $this->resetMessages();
        $payload = $this->resolvedTypographyPayload();

        try {
            $repository->saveDraftTypography($payload, auth()->user());
            $this->typography = $this->normalizeTypographyState($repository->draftTypography());
            $this->syncFontModesFromTypography();
            $this->notice(__('Typography draft saved. Enable preview or publish to apply on the public site.'));
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: __('Unable to save typography.');
        }
    }

    public function resetTypeScaleToDefaults(): void
    {
        $this->typography['type_scale'] = TypographyTypeScale::defaults();
        $this->notice(__('Type scale reset to platform defaults. Save typography draft to keep.'));
    }

    public function saveHeader(ThemeConfigRepository $repository): void
    {
        $this->resetMessages();

        try {
            $repository->saveDraftMeta($this->header_preset, $this->layout_preset, $this->resolvedTypographyPayload(), auth()->user());
            $branding = $repository->draftBranding();
            $branding['header_config'] = $this->normalizedHeaderConfiguration();
            $repository->saveDraftBranding($branding, auth()->user());
            $this->header_config = $repository->draftHeaderConfiguration();
            $this->notice(__('Header draft saved. Enable preview or publish to apply on the public site.'));
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: __('Unable to save header preset.');
        }
    }

    public function saveLayout(ThemeConfigRepository $repository): void
    {
        $this->resetMessages();

        try {
            $repository->saveDraftMeta($this->header_preset, $this->layout_preset, $this->resolvedTypographyPayload(), auth()->user());
            $this->notice(__('Layout preset draft saved.'));
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: __('Unable to save layout preset.');
        }
    }

    public function applyPreset(ThemeConfigRepository $repository, ThemePresetRegistry $presetRegistry): void
    {
        $this->resetMessages();

        if ($this->preset_slug === '') {
            $this->errorMessage = __('Select a preset.');

            return;
        }

        $repository->applyPresetToDraft($this->preset_slug, auth()->user());
        $this->hydrateFromRepository($repository, $presetRegistry);
        $this->notice(__('Preset applied to draft.'));
    }

    public function resetDraft(ThemeConfigRepository $repository, ThemePresetRegistry $presetRegistry): void
    {
        $this->resetMessages();
        $repository->resetDraft();
        Session::forget('theme_preview_public');
        $this->hydrateFromRepository($repository, $presetRegistry);
        $this->notice(__('Draft changes discarded.'));
    }

    public function publish(ThemeConfigRepository $repository, ThemePresetRegistry $presetRegistry): void
    {
        $this->resetMessages();
        $this->authorize('publish', ThemeConfiguration::current());

        try {
            $repository->publishDraft(auth()->user());
            Session::forget('theme_preview_public');
            $this->hydrateFromRepository($repository, $presetRegistry);
            $this->notice(__('Theme published — changes are now live on the public site.'));
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors()['publish'] ?? $e->errors()['tokens'] ?? [])->first()
                ?: __('Publish blocked — fix contrast or invalid colors first.');
        }
    }

    public function enablePreview(): void
    {
        $this->resetMessages();
        Session::put('theme_preview_public', true);
        $this->notice(__('Preview mode enabled. Open the public site in a new tab to see draft changes.'));
    }

    public function disablePreview(): void
    {
        $this->resetMessages();
        Session::forget('theme_preview_public');
        $this->notice(__('Preview mode disabled.'));
    }

    public function clonePreset(ThemeConfigRepository $repository): void
    {
        $this->resetMessages();
        $this->validate(['clone_name' => ['required', 'string', 'max:120']]);
        $repository->clonePreset($this->preset_slug, $this->clone_name, auth()->user());
        $this->notice(__('Preset cloned.'));
    }

    public function exportPreset(ThemeConfigRepository $repository): void
    {
        $this->resetMessages();

        if ($this->preset_slug === '') {
            $this->errorMessage = __('Select a preset.');

            return;
        }

        $this->import_json = json_encode($repository->exportPreset($this->preset_slug), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->notice(__('Preset exported to JSON field below.'));
    }

    public function importPreset(ThemeConfigRepository $repository, ThemePresetRegistry $presetRegistry): void
    {
        $this->resetMessages();
        $payload = json_decode($this->import_json, true);
        if (! is_array($payload)) {
            $this->errorMessage = __('Invalid JSON payload.');

            return;
        }

        try {
            $repository->importPreset($payload, auth()->user());
            $this->notice(__('Preset imported.'));
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: __('Import failed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function previewData(
        ThemeCssVariableBuilder $cssBuilder,
        ThemeContrastValidator $contrastValidator,
    ): array {
        return [
            'css' => $cssBuilder->inlineStyleBlock($cssBuilder->publicVariables($this->tokens)),
            'contrast_errors' => $contrastValidator->validatePublicTokens($this->tokens),
        ];
    }

    public function render(
        ThemeConfigRepository $repository,
        ThemePresetRegistry $presetRegistry,
        ThemeCssVariableBuilder $cssBuilder,
        ThemeContrastValidator $contrastValidator,
        GlobalContentVariableRepository $globalContent,
    ): View {
        $config = Schema::hasTable('theme_configurations') ? ThemeConfiguration::current() : null;
        $brandStoryGroups = Schema::hasTable('global_content_variables')
            ? collect($globalContent->forEditorGrouped())
                ->only(['brand_story', 'home', 'contact'])
                ->all()
            : [];

        return view('livewire.settings.appearance-settings', [
            'brandStoryGroups' => $brandStoryGroups,
            'brandStoryReady' => Schema::hasTable('global_content_variables'),
            'headerPresets' => config('theme_management.header_presets', []),
            'layoutPresets' => config('theme_management.layout_presets', []),
            'fontWhitelist' => config('theme_management.font_whitelist', []),
            'fontScales' => config('theme_management.font_scales', []),
            'presets' => Schema::hasTable('theme_presets') ? $presetRegistry->publicPresets() : collect(),
            'tokenKeys' => array_keys($repository->defaultPublicTokens()),
            'preview' => $this->previewData($cssBuilder, $contrastValidator),
            'configuration' => $config,
            'previewActive' => Session::get('theme_preview_public') === true,
            'canPublish' => auth()->user()?->can('publish', ThemeConfiguration::current()) ?? false,
            'hasDraft' => $config?->draft_updated_at !== null,
            'logoUrl' => $repository->assetUrl($this->branding['logo_path'] ?? null),
            'faviconUrl' => $repository->assetUrl($this->branding['favicon_path'] ?? null),
            'headerConfigKeys' => config('theme_management.header_configuration_keys', []),
            'stickyBehaviors' => config('theme_management.sticky_behaviors', []),
            'typeScaleLabels' => TypographyTypeScale::elementLabels(),
            'resolvedHeadingFont' => $this->resolvedHeadingFont(),
            'resolvedBodyFont' => $this->resolvedBodyFont(),
        ]);
    }

    private function hydrateFromRepository(ThemeConfigRepository $repository, ThemePresetRegistry $presetRegistry): void
    {
        $this->tokens = $repository->draftPublicTokens();
        $this->branding = $repository->draftBranding();
        $this->typography = $this->normalizeTypographyState($repository->draftTypography());
        $this->header_preset = $repository->draftHeaderPreset();
        $this->layout_preset = $repository->draftLayoutPreset();
        $this->header_config = $repository->draftHeaderConfiguration();
        $this->preset_slug = $presetRegistry->builtinSlugs()[0] ?? '';
        $this->syncFontModesFromTypography();
    }

    private function loadBrandStoryFields(GlobalContentVariableRepository $globalContent): void
    {
        if (! Schema::hasTable('global_content_variables')) {
            $this->brandStory = [];

            return;
        }

        $keys = $globalContent->keysForGroups(['brand_story', 'home', 'contact']);
        $editor = $globalContent->forEditor();
        $this->brandStory = [];

        foreach ($keys as $key) {
            $this->brandStory[$key] = (string) ($editor[$key]['value'] ?? '');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedHeaderConfiguration(): array
    {
        $defaults = app(ThemeConfigRepository::class)->defaultHeaderConfiguration();
        $behaviors = array_keys(config('theme_management.sticky_behaviors', []));
        $normalized = [];

        foreach (config('theme_management.header_configuration_keys', []) as $key) {
            if ($key === 'sticky_behavior') {
                $value = (string) ($this->header_config[$key] ?? $defaults[$key]);
                $normalized[$key] = in_array($value, $behaviors, true) ? $value : $defaults[$key];

                continue;
            }

            $normalized[$key] = filter_var($this->header_config[$key] ?? $defaults[$key], FILTER_VALIDATE_BOOLEAN);
        }

        return $normalized;
    }

    private function syncFontModesFromTypography(): void
    {
        $whitelist = config('theme_management.font_whitelist', []);
        $heading = (string) ($this->typography['heading_font'] ?? '');
        $body = (string) ($this->typography['body_font'] ?? '');

        if (in_array($heading, $whitelist, true)) {
            $this->heading_font_mode = 'preset';
            $this->custom_heading_font = '';
        } else {
            $this->heading_font_mode = 'custom';
            $this->custom_heading_font = $heading;
        }

        if (in_array($body, $whitelist, true)) {
            $this->body_font_mode = 'preset';
            $this->custom_body_font = '';
        } else {
            $this->body_font_mode = 'custom';
            $this->custom_body_font = $body;
        }
    }

    private function resolvedHeadingFont(): string
    {
        return $this->heading_font_mode === 'custom'
            ? trim($this->custom_heading_font)
            : (string) ($this->typography['heading_font'] ?? config('typography.defaults.heading_font', 'Plus Jakarta Sans'));
    }

    private function resolvedBodyFont(): string
    {
        return $this->body_font_mode === 'custom'
            ? trim($this->custom_body_font)
            : (string) ($this->typography['body_font'] ?? config('typography.defaults.body_font', 'Noto Sans'));
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvedTypographyPayload(): array
    {
        return TypographyTypeScale::mergeIntoTypography(array_merge($this->typography, [
            'heading_font' => $this->resolvedHeadingFont(),
            'body_font' => $this->resolvedBodyFont(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $typography
     * @return array<string, mixed>
     */
    private function normalizeTypographyState(array $typography): array
    {
        return TypographyTypeScale::mergeIntoTypography($typography);
    }

    private function notice(string $message): void
    {
        $this->statusMessage = $message;
        $this->errorMessage = null;
    }

    private function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
        $this->contrastWarnings = [];
    }
}
