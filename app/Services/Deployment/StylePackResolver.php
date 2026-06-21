<?php

namespace App\Services\Deployment;

use App\Models\Page;
use App\Models\ThemeConfiguration;
use Illuminate\Support\Facades\Session;

class StylePackResolver
{
    public function __construct(
        private readonly StylePackRegistry $registry,
    ) {}

    public function activeSlug(?Page $page = null): string
    {
        if (Session::get(config('deployment_engine.preview_session_keys.style_pack')) !== null) {
            return (string) Session::get(config('deployment_engine.preview_session_keys.style_pack'));
        }

        if ($page !== null && is_array($page->deployment_meta_json)) {
            $fromPage = $page->deployment_meta_json['style_pack'] ?? null;
            if (is_string($fromPage) && $fromPage !== '') {
                return $fromPage;
            }
        }

        $config = ThemeConfiguration::current();
        $draft = $config->draft_style_pack;
        $published = $config->active_style_pack;

        if ($this->themePreviewActive() && is_string($draft) && $draft !== '') {
            return $draft;
        }

        if (is_string($published) && $published !== '') {
            return $published;
        }

        return (string) config('deployment_engine.default_style_pack', 'digital growth platform_professional');
    }

    /**
     * @return array<string, mixed>
     */
    public function activePack(?Page $page = null): array
    {
        return $this->registry->find($this->activeSlug($page)) ?? [];
    }

    /**
     * Context variables for ContentRenderContext on public pages.
     *
     * @return array<string, mixed>
     */
    public function contextVariables(?Page $page = null): array
    {
        $slug = $this->activeSlug($page);
        $pack = $this->registry->find($slug) ?? [];

        return [
            'stylePackSlug' => $slug,
            'stylePackLabel' => (string) ($pack['label'] ?? $slug),
            'visualLanguage' => (string) ($pack['visual_language'] ?? 'modern'),
            'stylePackAssignments' => is_array($pack['assignments'] ?? null) ? $pack['assignments'] : [],
        ];
    }

    private function themePreviewActive(): bool
    {
        return Session::get('theme_preview_public') === true;
    }
}
