<?php

namespace App\Livewire\SiteArchitect;

use App\Models\BlockPreset;
use App\Models\DeploymentPackage;
use App\Models\SectionLibraryItem;
use App\Policies\DeploymentEnginePolicy;
use App\Services\Deployment\BlueprintRegistry;
use App\Services\Deployment\DeploymentPackageExporter;
use App\Services\Deployment\DeploymentPackageImporter;
use App\Services\Deployment\DeploymentPackageValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class DeploymentPackages extends Component
{
    public string $package_name = '';

    public string $style_pack_slug = '';

    public string $blueprint_slugs = 'home_healthcare career consultancy';

    /** @var list<string> */
    public array $selected_section_slugs = [];

    /** @var list<string> */
    public array $selected_preset_slugs = [];

    public string $import_json = '';

    /** @var array{valid: bool, errors: list<string>, warnings: list<string>}|null */
    public ?array $validation_report = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(app(DeploymentEnginePolicy::class)->managePackages(auth()->user()), 403);
        $this->style_pack_slug = app(\App\Services\Deployment\StylePackRegistry::class)->slugs()[0] ?? '';
        $this->selected_section_slugs = Schema::hasTable('section_library_items')
            ? SectionLibraryItem::query()->pluck('slug')->all()
            : [];
        $this->selected_preset_slugs = Schema::hasTable('block_presets')
            ? BlockPreset::query()->pluck('slug')->all()
            : [];
    }

    public function validateManifest(DeploymentPackageValidator $validator): void
    {
        $manifest = json_decode($this->import_json, true);
        if (! is_array($manifest)) {
            $this->errorMessage = __('Invalid JSON.');
            $this->validation_report = null;

            return;
        }

        $this->validation_report = $validator->validate($manifest);
        $this->statusMessage = $this->validation_report['valid']
            ? __('Package is compatible.')
            : __('Validation found issues — review report.');
    }

    public function exportPackage(DeploymentPackageExporter $exporter): void
    {
        if ($this->package_name === '') {
            $this->errorMessage = __('Enter a package name.');

            return;
        }

        $blueprints = array_filter(array_map('trim', explode(',', $this->blueprint_slugs)));

        $package = $exporter->export(
            $this->package_name,
            $this->style_pack_slug,
            $blueprints,
            $this->selected_section_slugs,
            $this->selected_preset_slugs,
            auth()->user(),
        );

        $this->import_json = json_encode($package->manifest_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->validation_report = app(DeploymentPackageValidator::class)->validate($package->manifest_json);
        $this->statusMessage = __('Package exported. JSON below — version :v.', ['v' => $package->package_version]);
    }

    public function importPackage(DeploymentPackageImporter $importer, DeploymentPackageValidator $validator): void
    {
        $manifest = json_decode($this->import_json, true);
        if (! is_array($manifest)) {
            $this->errorMessage = __('Invalid package JSON.');

            return;
        }

        $report = $validator->validate($manifest);
        $this->validation_report = $report;

        if (! $report['valid']) {
            $this->errorMessage = implode(' ', $report['errors']);

            return;
        }

        try {
            $importer->import($manifest, $this->package_name ?: 'Imported Package', auth()->user());
            $this->statusMessage = __('Package imported into draft theme, variables, sections, and presets.');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function clonePackage(int $packageId, DeploymentPackageExporter $exporter): void
    {
        $source = DeploymentPackage::query()->find($packageId);
        if ($source === null) {
            return;
        }
        $clone = $exporter->clonePackage($source, $source->name.' Copy', auth()->user());
        $this->statusMessage = __('Cloned as :slug', ['slug' => $clone->slug]);
    }

    public function render(BlueprintRegistry $blueprints): View
    {
        return view('livewire.site-architect.deployment-packages', [
            'blueprintOptions' => $blueprints->slugs(),
            'stylePackOptions' => app(\App\Services\Deployment\StylePackRegistry::class)->all(),
            'sectionOptions' => Schema::hasTable('section_library_items')
                ? SectionLibraryItem::query()->orderBy('name')->get()
                : collect(),
            'presetOptions' => Schema::hasTable('block_presets')
                ? BlockPreset::query()->orderBy('name')->get()
                : collect(),
            'packages' => Schema::hasTable('deployment_packages')
                ? DeploymentPackage::query()->latest()->limit(10)->get()
                : collect(),
            'ready' => Schema::hasTable('deployment_packages'),
        ]);
    }
}
