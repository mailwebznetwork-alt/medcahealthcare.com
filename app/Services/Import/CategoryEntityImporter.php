<?php

namespace App\Services\Import;

use App\Enums\ServiceVisibility;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryFaq;
use App\Services\Governance\CategoryCreationGuard;
use App\Services\Governance\MasterDataAudit;
use App\Services\Governance\MasterDataProtection;
use Illuminate\Support\Str;

final class CategoryEntityImporter extends AbstractSpreadsheetImporter
{
    public function __construct(
        SpreadsheetReader $reader,
        ImportBatchRecorder $recorder,
        private readonly ImportCategoryFieldMapper $fieldMapper,
    ) {
        parent::__construct($reader, $recorder);
    }

    public function entityKey(): string
    {
        return 'categories';
    }

    protected function requiredColumns(): array
    {
        return ['code', 'name'];
    }

    protected function optionalColumns(): array
    {
        return [
            'slug', 'description', 'parent_code', 'sort_order', 'is_featured', 'is_active',
            'show_on_homepage', 'show_on_about', 'show_on_contact', 'visibility',
            'meta_title', 'meta_description', 'focus_keywords', 'secondary_keywords',
            'canonical_url', 'robots_index', 'og_title', 'og_description', 'og_image',
            'aeo_question', 'aeo_answer', 'faq_pairs', 'h1', 'breadcrumb_title',
        ];
    }

    protected function previewRow(array $row, int $line): array
    {
        $code = ServiceCategory::normalizeCode((string) ($row['code'] ?? ''));
        if ($code === '') {
            return ['status' => 'invalid', 'detail' => __('Missing category code.'), 'key' => null];
        }

        $exists = ServiceCategory::query()->where('code', $code)->exists();

        return [
            'status' => $exists ? 'update' : 'ready',
            'detail' => $exists ? __('Will update existing category.') : null,
            'key' => $code,
        ];
    }

    protected function importRow(array $row, int $line): array
    {
        $code = ServiceCategory::normalizeCode((string) ($row['code'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        if ($code === '' || $name === '') {
            return ['action' => 'failed', 'error' => __('Missing code or name.')];
        }

        if (! app(MasterDataProtection::class)->allowsWrite('import')) {
            app(MasterDataAudit::class)->categoryRecreationBlocked($code, 'import', 'Master data protection is enabled.');

            return ['action' => 'skipped', 'error' => __('Import blocked by master data protection.')];
        }

        $slug = trim((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($name);
        }

        $guard = app(CategoryCreationGuard::class);
        $restored = $guard->resolveForExplicitRecreate($code, 'import', $slug);
        $existing = ServiceCategory::query()->where('code', $code)->first() ?? $restored;
        $previous = $existing?->toArray();

        if ($existing === null && ! $guard->canCreateCategory($code, 'import')) {
            return ['action' => 'skipped', 'error' => __('Category permanently deleted; import skipped.')];
        }

        $parentId = null;
        if (filled($row['parent_code'] ?? null)) {
            $parent = ServiceCategory::query()->where('code', ServiceCategory::normalizeCode($row['parent_code']))->first();
            if ($parent === null) {
                return ['action' => 'failed', 'error' => __('Parent category not found: :code', ['code' => $row['parent_code']])];
            }
            $parentId = $parent->id;
        }

        $attrs = $this->fieldMapper->categoryAttributes($row, $parentId);
        $attrs['name'] = $name;
        $attrs['code'] = $code;
        if (! isset($attrs['slug'])) {
            $attrs['slug'] = $row['slug'] ?? $code;
        }

        if (filled($row['visibility'] ?? null)) {
            $attrs['visibility'] = ServiceVisibility::tryFrom(strtolower($row['visibility'])) ?? ServiceVisibility::Public;
        }

        if ($existing === null) {
            $category = ServiceCategory::query()->create($attrs);
            $this->recorder->record('created', 'service_category', $category->id, null, $line);
            app(MasterDataAudit::class)->categoryCreated($category, 'import');
            $action = 'created';
        } else {
            $existing->update($attrs);
            $category = $existing->fresh();
            $this->recorder->record('updated', 'service_category', $category->id, $previous, $line);
            app(MasterDataAudit::class)->categoryUpdated($category, 'import');
            $action = 'updated';
        }

        $this->fieldMapper->syncSeo($category, $row);
        $this->syncFaqs($category, $row);

        return ['action' => $action, 'error' => null];
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function syncFaqs(ServiceCategory $category, array $row): void
    {
        $pairs = ImportSupport::parseFaqPairs($row['faq_pairs'] ?? null);
        if ($pairs === []) {
            return;
        }

        foreach ($pairs as $i => $pair) {
            ServiceCategoryFaq::query()->updateOrCreate(
                ['service_category_id' => $category->id, 'question' => $pair['question']],
                ['answer' => $pair['answer'], 'sort_order' => $i]
            );
        }
    }
}
