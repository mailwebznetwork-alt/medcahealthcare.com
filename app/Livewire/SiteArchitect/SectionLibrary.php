<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Page;
use App\Models\SectionLibraryItem;
use App\Policies\DeploymentEnginePolicy;
use App\Services\Deployment\SectionLibraryRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class SectionLibrary extends Component
{
    public string $import_json = '';

    public string $capture_page_slug = '';

    public string $capture_name = '';

    public string $create_name = '';

    public string $create_blocks = 'hero-home';

    public string $insert_section_slug = '';

    public string $insert_page_slug = '';

    public ?string $preview_html = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(app(DeploymentEnginePolicy::class)->manageBlockPresets(auth()->user()), 403);
    }

    public function createSection(SectionLibraryRepository $repository): void
    {
        if (config('platform_composition.section_library_deprecated', false)) {
            $this->errorMessage = __('Section Library is deprecated. Compose pages with {{block:slug}} tokens instead.');

            return;
        }

        if ($this->create_name === '') {
            $this->errorMessage = __('Enter a section name.');

            return;
        }

        $slugs = array_filter(array_map('trim', explode(',', $this->create_blocks)));
        $blocks = array_map(fn (string $slug): array => ['slug' => $slug, 'style_variant' => 'style_1'], $slugs);

        $repository->save($this->create_name, $blocks, null, null, auth()->user());
        $this->statusMessage = __('Section created.');
    }

    public function exportSection(string $slug, SectionLibraryRepository $repository): void
    {
        $section = $repository->find($slug);
        if ($section === null) {
            $this->errorMessage = __('Section not found.');

            return;
        }
        $this->import_json = json_encode($repository->export($section), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->statusMessage = __('Section exported to JSON field.');
    }

    public function importSection(SectionLibraryRepository $repository): void
    {
        $payload = json_decode($this->import_json, true);
        if (! is_array($payload)) {
            $this->errorMessage = __('Invalid JSON.');

            return;
        }
        try {
            $repository->import($payload, auth()->user());
            $this->statusMessage = __('Section imported.');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function captureFromPage(SectionLibraryRepository $repository): void
    {
        $page = Page::query()->where('slug', $this->capture_page_slug)->first();
        if ($page === null) {
            $this->errorMessage = __('Page not found.');

            return;
        }
        if ($this->capture_name === '') {
            $this->errorMessage = __('Enter a section name.');

            return;
        }
        $repository->captureFromPage($page, $this->capture_name, auth()->user());
        $this->statusMessage = __('Section saved from page.');
    }

    public function insertIntoPage(SectionLibraryRepository $repository): void
    {
        $page = Page::query()->where('slug', $this->insert_page_slug)->first();
        if ($page === null || $this->insert_section_slug === '') {
            $this->errorMessage = __('Select a page and section.');

            return;
        }
        $repository->insertIntoPage($page, $this->insert_section_slug);
        $this->statusMessage = __('Section inserted into page. Edit blocks in Pages as usual.');
    }

    public function cloneSection(string $slug, SectionLibraryRepository $repository): void
    {
        $section = $repository->find($slug);
        if ($section === null) {
            return;
        }
        $repository->clone($section, $section->name.' Copy', auth()->user());
        $this->statusMessage = __('Section cloned.');
    }

    public function deleteSection(string $slug, SectionLibraryRepository $repository): void
    {
        $section = $repository->find($slug);
        if ($section === null) {
            return;
        }

        try {
            $repository->delete($section);
            $this->statusMessage = __('Section deleted.');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function previewSection(string $slug, SectionLibraryRepository $repository): void
    {
        $section = $repository->find($slug);
        if ($section === null) {
            $this->errorMessage = __('Section not found.');

            return;
        }

        $this->preview_html = $repository->previewContent($section);
        $this->statusMessage = __('Preview generated from block tokens.');
    }

    public function render(): View
    {
        return view('livewire.site-architect.section-library', [
            'sections' => Schema::hasTable('section_library_items')
                ? SectionLibraryItem::query()->orderBy('name')->get()
                : collect(),
            'pages' => Page::query()->orderBy('title')->get(['id', 'title', 'slug']),
            'ready' => Schema::hasTable('section_library_items'),
        ]);
    }
}
