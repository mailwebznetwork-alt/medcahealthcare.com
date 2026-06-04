<?php

namespace App\Services\Theme;

use App\Services\Integrations\WhatsAppClickToChatService;
use App\Support\TypographyTypeScale;
use Illuminate\Support\Facades\Session;

class ThemeResolver
{
    public function __construct(
        private readonly ThemeConfigRepository $repository,
        private readonly ThemeCssVariableBuilder $cssBuilder,
    ) {}

    public function previewModeActive(): bool
    {
        return Session::get('theme_preview_public') === true;
    }

    /**
     * @return array<string, string>
     */
    public function publicTokens(): array
    {
        if ($this->previewModeActive()) {
            return $this->repository->draftPublicTokens();
        }

        return $this->repository->publishedPublicTokens();
    }

    public function publicCssBlock(): string
    {
        $colors = $this->publicTokens();
        $shapes = $this->previewModeActive()
            ? $this->repository->draftShapeTokens()
            : $this->repository->publishedShapeTokens();

        return $this->cssBuilder->inlineStyleBlock(
            $this->cssBuilder->allPublicVariables($colors, $shapes)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function branding(): array
    {
        $branding = $this->previewModeActive()
            ? $this->repository->draftBranding()
            : $this->repository->publishedBranding();

        $branding['whatsapp_url'] = app(WhatsAppClickToChatService::class)->primaryUrl();

        return $branding;
    }

    public function brandingValue(string $key, mixed $fallback = null): mixed
    {
        $branding = $this->branding();

        return $branding[$key] ?? $fallback ?? config('medca.'.$key);
    }

    public function headerPreset(): string
    {
        if ($this->previewModeActive()) {
            return $this->repository->draftHeaderPreset();
        }

        return $this->repository->publishedHeaderPreset();
    }

    public function headerPresetClass(): string
    {
        $presets = config('theme_management.header_presets', []);
        $classes = $presets[$this->headerPreset()]['class'] ?? 'medca-header-classic';
        $sticky = (string) ($this->headerConfiguration()['sticky_behavior'] ?? 'sticky');

        return trim($classes.' medca-header-sticky--'.str_replace('_', '-', $sticky));
    }

    /**
     * @return array<string, mixed>
     */
    public function headerConfiguration(): array
    {
        return $this->previewModeActive()
            ? $this->repository->draftHeaderConfiguration()
            : $this->repository->publishedHeaderConfiguration();
    }

    public function headerConfigEnabled(string $key): bool
    {
        return (bool) ($this->headerConfiguration()[$key] ?? false);
    }

    public function layoutPreset(): string
    {
        if ($this->previewModeActive()) {
            return $this->repository->draftLayoutPreset();
        }

        return $this->repository->publishedLayoutPreset();
    }

    public function layoutShellClass(): string
    {
        $presets = config('theme_management.layout_presets', []);

        return $presets[$this->layoutPreset()]['shell_class'] ?? 'max-w-6xl';
    }

    public function layoutMainClasses(): string
    {
        $presets = config('theme_management.layout_presets', []);

        return $presets[$this->layoutPreset()]['main_class']
            ?? 'mx-auto w-full max-w-6xl px-4 md:px-6 lg:px-8';
    }

    public function typography(): array
    {
        $typography = $this->previewModeActive()
            ? $this->repository->draftTypography()
            : $this->repository->publishedTypography();

        return TypographyTypeScale::mergeIntoTypography([
            'heading_font' => (string) ($typography['heading_font'] ?? 'Plus Jakarta Sans'),
            'body_font' => (string) ($typography['body_font'] ?? 'Noto Sans'),
            'scale' => (string) ($typography['scale'] ?? 'default'),
            'line_height' => (string) ($typography['line_height'] ?? '1.5'),
            'letter_spacing' => (string) ($typography['letter_spacing'] ?? 'normal'),
            'type_scale' => is_array($typography['type_scale'] ?? null) ? $typography['type_scale'] : null,
        ]);
    }

    public function googleFontsHref(): string
    {
        $typography = $this->typography();
        $fonts = collect([$typography['heading_font'], $typography['body_font']])
            ->filter(fn (string $font): bool => $font !== '' && $font !== 'inherit')
            ->unique()
            ->map(fn (string $font): string => str_replace(' ', '+', $font).':wght@400;500;600;700')
            ->values();

        if ($fonts->isEmpty()) {
            return 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap';
        }

        return 'https://fonts.googleapis.com/css2?family='.$fonts->implode('&family=').'&display=swap';
    }

    public function typographyCssBlock(): string
    {
        return app(TypographyScaleResolver::class)->cssBlock($this->typography());
    }
}
