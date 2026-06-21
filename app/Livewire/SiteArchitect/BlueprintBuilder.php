<?php

namespace App\Livewire\SiteArchitect;

use App\Models\DeploymentGeneration;
use App\Policies\DeploymentEnginePolicy;
use App\Services\Deployment\BlueprintPageGenerator;
use App\Services\Deployment\BlueprintRegistry;
use App\Services\Deployment\StylePackRegistry;
use App\Services\Theme\ThemePresetRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class BlueprintBuilder extends Component
{
    public string $industry = 'healthcare career consultancy';

    public string $blueprint_slug = 'home_healthcare career consultancy';

    public string $style_pack_slug = 'healthcare career consultancy_professional';

    public string $theme_preset_slug = 'clinical_blue';

    public string $layout_preset = 'contained';

    public bool $activate_generated_pages = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    /** @var list<string> */
    public array $generatedSlugs = [];

    public function mount(BlueprintRegistry $blueprints, StylePackRegistry $stylePacks): void
    {
        abort_unless(app(DeploymentEnginePolicy::class)->useBlueprintBuilder(auth()->user()), 403);

        if ($blueprints->slugs() !== []) {
            $this->blueprint_slug = $blueprints->slugs()[0];
        }
        if ($stylePacks->slugs() !== []) {
            $this->style_pack_slug = $stylePacks->slugs()[0];
        }
    }

    public function updatedStylePackSlug(StylePackRegistry $stylePacks): void
    {
        $pack = $stylePacks->find($this->style_pack_slug);
        if (! is_array($pack)) {
            return;
        }
        $this->theme_preset_slug = (string) ($pack['theme_preset_slug'] ?? $this->theme_preset_slug);
        $this->layout_preset = (string) ($pack['layout_preset'] ?? $this->layout_preset);
    }

    public function generate(BlueprintPageGenerator $generator): void
    {
        abort_unless(app(DeploymentEnginePolicy::class)->generatePages(auth()->user()), 403);
        $this->resetMessages();

        if (! Schema::hasTable('deployment_generations')) {
            $this->errorMessage = __('Run database migrations to enable the Deployment Engine.');

            return;
        }

        try {
            $result = $generator->generate(
                $this->blueprint_slug,
                $this->style_pack_slug,
                $this->theme_preset_slug,
                $this->layout_preset,
                auth()->user(),
                applyThemeToDraft: true,
                activatePages: $this->activate_generated_pages,
            );

            $this->generatedSlugs = is_array($result['generation']->generated_page_slugs)
                ? $result['generation']->generated_page_slugs
                : [];

            $this->statusMessage = __('Generated :count page(s) as drafts. Open Site Architect → Pages to edit standard blocks.', [
                'count' => count($this->generatedSlugs),
            ]);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function previewStylePack(): void
    {
        Session::put(
            (string) config('deployment_engine.preview_session_keys.style_pack'),
            $this->style_pack_slug,
        );
        $this->statusMessage = __('Style pack preview enabled on the public site. Publish theme separately to go live.');
        $this->errorMessage = null;
    }

    public function clearStylePackPreview(): void
    {
        Session::forget((string) config('deployment_engine.preview_session_keys.style_pack'));
        $this->statusMessage = __('Style pack preview cleared.');
        $this->errorMessage = null;
    }

    public function render(
        BlueprintRegistry $blueprints,
        StylePackRegistry $stylePacks,
        ThemePresetRegistry $themePresets,
    ): View {
        return view('livewire.site-architect.blueprint-builder', [
            'industries' => config('deployment_engine.industries', []),
            'blueprintOptions' => $blueprints->forIndustry($this->industry !== '' ? $this->industry : null),
            'stylePackOptions' => collect($stylePacks->all())->map(fn (array $pack, string $slug): array => [
                'slug' => $slug,
                'label' => (string) ($pack['label'] ?? $slug),
            ])->values()->all(),
            'themePresetOptions' => $themePresets->builtinSlugs(),
            'layoutPresets' => config('theme_management.layout_presets', []),
            'recentGenerations' => Schema::hasTable('deployment_generations')
                ? DeploymentGeneration::query()->latest()->limit(5)->get()
                : collect(),
        ]);
    }

    private function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
    }
}
