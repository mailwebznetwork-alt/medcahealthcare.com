<?php

namespace App\Services\Theme;

use App\Models\ThemeConfiguration;
use App\Models\ThemePreset;
use App\Models\User;
use App\Services\Deployment\GlobalContentVariableRepository;
use App\Support\TypographyTypeScale;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ThemeConfigRepository
{
    public function __construct(
        private readonly ThemeContrastValidator $contrastValidator,
        private readonly ThemePresetRegistry $presetRegistry,
        private readonly ThemeColorNormalizer $colorNormalizer,
    ) {}

    /**
     * @return array<string, string>
     */
    public function defaultPublicTokens(): array
    {
        return [
            'primary' => '#0055ff',
            'primary_hover' => '#001e5c',
            'navy' => '#001f5c',
            'navy_mid' => '#012a7d',
            'navy_border' => '#001433',
            'navy_accent' => '#164081',
            'text_primary' => '#0f172a',
            'text_secondary' => '#475569',
            'text_muted' => '#64748b',
            'surface' => '#ffffff',
            'surface_muted' => '#f8fafc',
            'surface_elevated' => '#f1f5f9',
            'border' => '#e2e8f0',
            'success' => '#16a34a',
            'warning' => '#d97706',
            'danger' => '#dc2626',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBranding(): array
    {
        return [
            'brand_name' => config('medca.brand_name'),
            'tagline' => config('medca.tagline'),
            'company_legal_name' => config('medca.company_legal_name'),
            'phone_display' => config('medca.phone_display'),
            'phone_tel' => config('medca.phone_tel'),
            'whatsapp_url' => config('medca.whatsapp_url'),
            'contact_email' => config('medca.contact_email', ''),
            'brand_url' => config('app.url'),
            'primary_cta_text' => config('medca.primary_cta_text', 'Book a consultation'),
            'logo_path' => null,
            'favicon_path' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultTypography(): array
    {
        return TypographyTypeScale::mergeIntoTypography([
            'heading_font' => config('typography.defaults.heading_font', 'Plus Jakarta Sans'),
            'body_font' => config('typography.defaults.body_font', 'Noto Sans'),
            'scale' => 'default',
            'line_height' => '1.5',
            'letter_spacing' => 'normal',
        ]);
    }

    /**
     * Header preset configuration toggles (stored in branding.header_config).
     *
     * @return array<string, mixed>
     */
    public function defaultHeaderConfiguration(): array
    {
        return [
            'show_top_bar' => true,
            'show_search' => false,
            'show_location_selector' => false,
            'show_branch_selector' => false,
            'show_social_icons' => false,
            'show_secondary_menu' => false,
            'mobile_cta_enabled' => true,
            'mobile_whatsapp_enabled' => true,
            'sticky_behavior' => 'sticky',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publishedHeaderConfiguration(): array
    {
        $branding = $this->publishedBranding();
        $stored = is_array($branding['header_config'] ?? null) ? $branding['header_config'] : [];

        return array_merge($this->defaultHeaderConfiguration(), $stored);
    }

    /**
     * @return array<string, mixed>
     */
    public function draftHeaderConfiguration(): array
    {
        $branding = $this->draftBranding();
        $stored = is_array($branding['header_config'] ?? null) ? $branding['header_config'] : [];

        return array_merge($this->defaultHeaderConfiguration(), $stored);
    }

    public function configuration(): ThemeConfiguration
    {
        return ThemeConfiguration::current();
    }

    /**
     * @return array<string, string>
     */
    public function publishedPublicTokens(): array
    {
        return Cache::remember(config('theme_management.cache_key').'.tokens', config('theme_management.cache_ttl_seconds'), function (): array {
            $config = $this->configuration();
            $published = is_array($config->published_public) ? $config->published_public : [];

            return array_merge($this->defaultPublicTokens(), $published);
        });
    }

    /**
     * @return array<string, string>
     */
    public function draftPublicTokens(): array
    {
        $config = $this->configuration();
        $draft = is_array($config->draft_public) ? $config->draft_public : [];

        return array_merge($this->publishedPublicTokens(), $draft);
    }

    /**
     * @return array<string, string>
     */
    public function publishedShapeTokens(): array
    {
        $registry = app(ThemeTokenRegistry::class);
        $config = $this->configuration();
        $published = is_array($config->published_shape) ? $config->published_shape : [];

        return array_merge($registry->defaultShapeTokens(), $published);
    }

    /**
     * @return array<string, string>
     */
    public function draftShapeTokens(): array
    {
        $config = $this->configuration();
        $draft = is_array($config->draft_shape) ? $config->draft_shape : [];

        return array_merge($this->publishedShapeTokens(), $draft);
    }

    /**
     * @return array<string, mixed>
     */
    public function publishedBranding(): array
    {
        $config = $this->configuration();
        $branding = is_array($config->branding) ? $config->branding : [];

        return array_merge($this->defaultBranding(), array_filter($branding, fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function draftBranding(): array
    {
        $config = $this->configuration();
        $draft = is_array($config->draft_branding) ? $config->draft_branding : [];

        return array_merge($this->publishedBranding(), $draft);
    }

    /**
     * @return array<string, mixed>
     */
    public function publishedTypography(): array
    {
        $config = $this->configuration();
        $typography = is_array($config->typography) ? $config->typography : [];

        return array_merge($this->defaultTypography(), $typography);
    }

    /**
     * @return array<string, mixed>
     */
    public function draftTypography(): array
    {
        $config = $this->configuration();
        $draft = is_array($config->draft_typography) ? $config->draft_typography : [];

        return array_merge($this->publishedTypography(), $draft);
    }

    public function publishedHeaderPreset(): string
    {
        return $this->configuration()->header_preset ?: 'classic_digital growth platform';
    }

    public function draftHeaderPreset(): string
    {
        return $this->configuration()->draft_header_preset ?: $this->publishedHeaderPreset();
    }

    public function publishedLayoutPreset(): string
    {
        return $this->configuration()->layout_preset ?: 'contained';
    }

    public function draftLayoutPreset(): string
    {
        return $this->configuration()->draft_layout_preset ?: $this->publishedLayoutPreset();
    }

    /**
     * @param  array<string, string>  $tokens
     */
    public function saveDraftPublicTokens(array $tokens, User $user, bool $strictContrast = false): void
    {
        $defaults = $this->defaultPublicTokens();
        $published = $this->publishedPublicTokens();
        $normalized = $this->colorNormalizer->normalizeMany(array_merge($defaults, $tokens));

        $invalid = array_diff_key(array_merge($defaults, $tokens), $normalized);
        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'tokens' => [__('One or more colors are invalid. Use hex format, e.g. #0055ff.')],
            ]);
        }

        $contrastErrors = $this->contrastValidator->validatePublicTokens($normalized);
        if ($contrastErrors !== [] && $strictContrast) {
            throw ValidationException::withMessages(['tokens' => $contrastErrors]);
        }

        $diff = [];
        foreach ($normalized as $key => $value) {
            $baseline = $published[$key] ?? $defaults[$key] ?? null;
            if ($baseline !== $value) {
                $diff[$key] = $value;
            }
        }

        $config = $this->configuration();
        $config->fill([
            'draft_public' => $diff !== [] ? $diff : null,
            'updated_by_id' => $user->id,
            'draft_updated_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $branding
     */
    public function saveDraftBranding(array $branding, User $user): void
    {
        $config = $this->configuration();
        $config->fill([
            'draft_branding' => array_merge(
                is_array($config->draft_branding) ? $config->draft_branding : [],
                $branding
            ),
            'updated_by_id' => $user->id,
            'draft_updated_at' => now(),
        ])->save();
    }

    public function saveDraftTypography(array $typography, User $user): void
    {
        $this->assertTypography($typography);

        $config = $this->configuration();
        $merged = array_merge(
            is_array($config->draft_typography) ? $config->draft_typography : [],
            array_merge($this->defaultTypography(), $typography)
        );
        $config->fill([
            'draft_typography' => $merged,
            'updated_by_id' => $user->id,
            'draft_updated_at' => now(),
        ])->save();
    }

    public function saveDraftMeta(string $headerPreset, string $layoutPreset, array $typography, User $user): void
    {
        $this->assertHeaderPreset($headerPreset);
        $this->assertLayoutPreset($layoutPreset);
        $this->assertTypography($typography);

        $config = $this->configuration();
        $config->fill([
            'draft_header_preset' => $headerPreset,
            'draft_layout_preset' => $layoutPreset,
            'draft_typography' => array_merge(
                is_array($config->draft_typography) ? $config->draft_typography : [],
                array_merge($this->defaultTypography(), $typography)
            ),
            'updated_by_id' => $user->id,
            'draft_updated_at' => now(),
        ])->save();
    }

    public function applyPresetToDraft(string $slug, User $user): void
    {
        $preset = $this->presetRegistry->findBySlug($slug);
        if ($preset === null) {
            throw ValidationException::withMessages(['preset' => __('Unknown theme preset.')]);
        }

        $config = $this->configuration();
        $config->fill([
            'draft_public' => is_array($preset->tokens) ? $preset->tokens : [],
            'draft_header_preset' => $preset->header_preset ?? 'classic_digital growth platform',
            'draft_layout_preset' => $preset->layout_preset ?? 'contained',
            'draft_typography' => is_array($preset->typography) ? $preset->typography : $this->defaultTypography(),
            'active_preset_slug' => $preset->slug,
            'updated_by_id' => $user->id,
            'draft_updated_at' => now(),
        ])->save();
    }

    public function publishDraft(User $user): void
    {
        $config = $this->configuration();
        $draftTokens = is_array($config->draft_public) ? $config->draft_public : [];
        $mergedTokens = $draftTokens !== []
            ? array_merge($this->defaultPublicTokens(), $draftTokens)
            : array_merge($this->defaultPublicTokens(), is_array($config->published_public) ? $config->published_public : []);

        if ($draftTokens !== []) {
            $errors = $this->contrastValidator->validatePublicTokens($mergedTokens);
            if ($errors !== []) {
                throw ValidationException::withMessages(['publish' => $errors]);
            }
        }

        $publishedBranding = $this->publishedBranding();
        if (is_array($config->draft_branding) && $config->draft_branding !== []) {
            $publishedBranding = array_merge($publishedBranding, $config->draft_branding);
        }

        $publishedTypography = $this->publishedTypography();
        if (is_array($config->draft_typography) && $config->draft_typography !== []) {
            $publishedTypography = array_merge($publishedTypography, $config->draft_typography);
        }

        $existingPublished = is_array($config->published_public) ? $config->published_public : [];

        $config->fill([
            'published_public' => $draftTokens !== []
                ? array_merge($existingPublished, $draftTokens)
                : $config->published_public,
            'branding' => $publishedBranding,
            'typography' => $publishedTypography,
            'header_preset' => $config->draft_header_preset ?: $config->header_preset,
            'layout_preset' => $config->draft_layout_preset ?: $config->layout_preset,
            'active_style_pack' => $config->draft_style_pack ?: $config->active_style_pack,
            'published_at' => now(),
            'published_by_id' => $user->id,
            'draft_public' => null,
            'draft_branding' => null,
            'draft_typography' => null,
            'draft_header_preset' => null,
            'draft_layout_preset' => null,
            'draft_style_pack' => null,
        ])->save();

        ThemeConfiguration::forgetCache();
        GlobalContentVariableRepository::forgetCache();
    }

    public function resetDraft(): void
    {
        $config = $this->configuration();
        $config->fill([
            'draft_public' => null,
            'draft_branding' => null,
            'draft_typography' => null,
            'draft_header_preset' => null,
            'draft_layout_preset' => null,
            'draft_style_pack' => null,
        ])->save();
    }

    public function clonePreset(string $slug, string $newName, User $user): ThemePreset
    {
        $source = $this->presetRegistry->findBySlug($slug);
        if ($source === null) {
            throw ValidationException::withMessages(['preset' => __('Unknown theme preset.')]);
        }

        $newSlug = str($newName)->slug()->toString().'-'.now()->format('His');

        return ThemePreset::query()->create([
            'slug' => $newSlug,
            'name' => $newName,
            'shell' => 'public',
            'is_builtin' => false,
            'tokens' => $source->tokens,
            'branding' => $source->branding,
            'header_preset' => $source->header_preset,
            'layout_preset' => $source->layout_preset,
            'typography' => $source->typography,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportPreset(string $slug): array
    {
        $preset = $this->presetRegistry->findBySlug($slug);
        if ($preset === null) {
            throw ValidationException::withMessages(['preset' => __('Unknown theme preset.')]);
        }

        return [
            'slug' => $preset->slug,
            'name' => $preset->name,
            'shell' => $preset->shell,
            'tokens' => $preset->tokens,
            'branding' => $preset->branding,
            'header_preset' => $preset->header_preset,
            'layout_preset' => $preset->layout_preset,
            'typography' => $preset->typography,
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function importPreset(array $payload, User $user): ThemePreset
    {
        if (! is_array($payload['tokens'] ?? null)) {
            throw ValidationException::withMessages(['import' => __('Invalid preset payload.')]);
        }

        $errors = $this->contrastValidator->validatePublicTokens($payload['tokens']);
        if ($errors !== []) {
            throw ValidationException::withMessages(['import' => $errors]);
        }

        $slug = str((string) ($payload['slug'] ?? 'imported'))->slug()->toString().'-'.now()->format('YmdHis');

        return ThemePreset::query()->create([
            'slug' => $slug,
            'name' => (string) ($payload['name'] ?? 'Imported Preset'),
            'shell' => 'public',
            'is_builtin' => false,
            'tokens' => $payload['tokens'],
            'branding' => is_array($payload['branding'] ?? null) ? $payload['branding'] : null,
            'header_preset' => $payload['header_preset'] ?? 'classic_digital growth platform',
            'layout_preset' => $payload['layout_preset'] ?? 'contained',
            'typography' => is_array($payload['typography'] ?? null) ? $payload['typography'] : $this->defaultTypography(),
        ]);
    }

    public function storeUploadedAsset(string $field, UploadedFile $file): string
    {
        if (! in_array($field, ['logo_path', 'favicon_path'], true)) {
            throw ValidationException::withMessages([$field => __('Invalid upload field.')]);
        }

        $allowed = $field === 'favicon_path'
            ? ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon']
            : ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];

        if (! in_array($file->getMimeType(), $allowed, true)) {
            throw ValidationException::withMessages([$field => __('Invalid file type for upload.')]);
        }

        return $file->store('theme-assets', 'public');
    }

    public function assetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function assertHeaderPreset(string $preset): void
    {
        if (! array_key_exists($preset, config('theme_management.header_presets', []))) {
            throw ValidationException::withMessages(['header_preset' => __('Invalid header preset.')]);
        }
    }

    private function assertLayoutPreset(string $preset): void
    {
        if (! array_key_exists($preset, config('theme_management.layout_presets', []))) {
            throw ValidationException::withMessages(['layout_preset' => __('Invalid layout preset.')]);
        }
    }

    /**
     * @param  array<string, mixed>  $typography
     */
    private function assertTypography(array $typography): void
    {
        foreach (['heading_font', 'body_font'] as $key) {
            if (! isset($typography[$key]) || ! is_string($typography[$key])) {
                continue;
            }

            $font = trim($typography[$key]);
            if ($font === '') {
                throw ValidationException::withMessages([$key => __('Font name is required.')]);
            }

            if (! preg_match("/^[\\p{L}0-9 '\\-]{2,80}$/u", $font)) {
                throw ValidationException::withMessages([$key => __('Invalid font name.')]);
            }
        }

        if (isset($typography['scale']) && ! in_array($typography['scale'], ['compact', 'default', 'large'], true)) {
            throw ValidationException::withMessages(['typography.scale' => __('Invalid font scale.')]);
        }

        if (isset($typography['type_scale']) && is_array($typography['type_scale'])) {
            TypographyTypeScale::assertValid($typography['type_scale']);
        }
    }
}
