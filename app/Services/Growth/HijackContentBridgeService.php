<?php

namespace App\Services\Growth;

use App\Models\Page;
use App\Models\PageSeo;
use App\Models\SeoEntity;
use App\Support\GrowthReadinessReport;
use Illuminate\Support\Facades\Schema;

class HijackContentBridgeService
{
    public function __construct(
        private readonly HijackStrategyReader $strategyReader,
        private readonly SeoEntityResolver $entityResolver,
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function resolveStrategy(string $strategyKey): ?array
    {
        $strategies = $this->strategyReader->allStrategies();
        $strategy = $strategies[$strategyKey] ?? null;

        return is_array($strategy) ? $strategy : null;
    }

    /**
     * Merge hijack strategy with autonomous Gemini content when available.
     *
     * @param  array<string, mixed>  $strategy
     * @return array{meta_title: ?string, meta_description: ?string, h1: ?string, ai_context_note: ?string, schema_type: ?string}
     */
    public function extractPageSeoFields(array $strategy): array
    {
        $autonomous = is_array($strategy['autonomous_content'] ?? null) ? $strategy['autonomous_content'] : [];

        $metaTitle = $this->firstNonEmptyString(
            $autonomous['meta_title'] ?? null,
            $strategy['meta_title'] ?? null,
        );
        $metaDescription = $this->firstNonEmptyString(
            $autonomous['meta_description'] ?? null,
            $strategy['meta_description'] ?? null,
        );
        $h1 = $this->firstNonEmptyString(
            $autonomous['h1'] ?? null,
            $strategy['h1_suggestion'] ?? null,
        );

        $changes = $strategy['content_changes'] ?? [];
        $aiNote = null;
        if (is_array($changes) && $changes !== []) {
            $bullets = collect($changes)
                ->filter(fn ($line) => is_string($line) && trim($line) !== '')
                ->map(fn (string $line) => '- '.trim($line))
                ->implode("\n");

            $aiNote = __('Growth hijack content edits (:keyword):', [
                'keyword' => (string) ($strategy['keyword'] ?? ''),
            ])."\n".$bullets;
        }

        $schemaType = is_string($strategy['schema_hint'] ?? null) && trim($strategy['schema_hint']) !== ''
            ? 'ProfessionalService'
            : null;

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'h1' => $h1,
            'ai_context_note' => $aiNote,
            'schema_type' => $schemaType,
        ];
    }

    /**
     * One-click publish: persist page SEO fields and sync global seo_entities + page_seo.
     *
     * @return array{page: Page, path: string}
     */
    public function applyAndPublish(Page $page, string $strategyKey): array
    {
        $strategy = $this->resolveStrategy($strategyKey);
        if ($strategy === null) {
            throw new \InvalidArgumentException(__('That hijack strategy is no longer available.'));
        }

        $fields = $this->extractPageSeoFields($strategy);

        $updates = [];
        if ($fields['meta_title'] !== null) {
            $updates['meta_title'] = mb_substr($fields['meta_title'], 0, 255);
        }
        if ($fields['meta_description'] !== null) {
            $updates['meta_description'] = $fields['meta_description'];
        }
        if ($fields['h1'] !== null) {
            $updates['h1'] = mb_substr($fields['h1'], 0, 255);
        }
        if ($fields['schema_type'] !== null && blank($page->schema_type)) {
            $updates['schema_type'] = $fields['schema_type'];
        }
        if ($fields['ai_context_note'] !== null) {
            $existing = trim((string) $page->ai_context);
            $updates['ai_context'] = $existing !== ''
                ? $existing."\n\n".$fields['ai_context_note']
                : $fields['ai_context_note'];
        }

        $keyword = trim((string) ($strategy['keyword'] ?? ''));
        if ($keyword !== '') {
            $focus = is_array($page->focus_keywords) ? $page->focus_keywords : [];
            if (! in_array($keyword, $focus, true)) {
                array_unshift($focus, $keyword);
                $updates['focus_keywords'] = array_values(array_slice($focus, 0, 10));
                $updates['keywords'] = implode(', ', $updates['focus_keywords']);
            }
        }

        if ($updates !== []) {
            $page->update($updates);
            $page->refresh();
        }

        $this->syncSeoEntityFromPage($page, $fields);
        $this->contentSeoAutoFill->syncPageGrowthArtifacts($page);
        $this->markStrategyApplied($strategyKey);

        GrowthReadinessReport::forget();

        return [
            'page' => $page->fresh(),
            'path' => $page->publicPath(),
        ];
    }

    /**
     * @param  array{meta_title: ?string, meta_description: ?string, h1: ?string, ai_context_note: ?string, schema_type: ?string}  $fields
     */
    private function syncSeoEntityFromPage(Page $page, array $fields): void
    {
        if (! Schema::hasTable('seo_entities')) {
            return;
        }

        $entity = $this->entityResolver->ensureForCurrentBusiness();
        $entityUpdates = [];

        if ($fields['meta_title'] !== null && (blank($entity->meta_title) || $page->slug === 'home')) {
            $entityUpdates['meta_title'] = mb_substr($fields['meta_title'], 0, 255);
        }
        if ($fields['meta_description'] !== null && (blank($entity->meta_description) || $page->slug === 'home')) {
            $entityUpdates['meta_description'] = $fields['meta_description'];
        }

        if ($entityUpdates !== []) {
            $entity->forceFill($entityUpdates)->save();
        }

        if (Schema::hasTable('page_seo')) {
            PageSeo::query()->updateOrCreate(
                ['page_slug' => $page->publicPath()],
                array_filter([
                    'meta_title' => $fields['meta_title'],
                    'meta_description' => $fields['meta_description'],
                ], fn ($v) => $v !== null)
            );
        }
    }

    private function markStrategyApplied(string $strategyKey): void
    {
        if (! Schema::hasTable('seo_entities') || ! Schema::hasColumn('seo_entities', 'hijack_strategy')) {
            return;
        }

        $entity = $this->entityResolver->forCurrentBusiness();
        if ($entity === null) {
            return;
        }

        $strategies = $entity->hijackStrategies();
        if (! isset($strategies[$strategyKey]) || ! is_array($strategies[$strategyKey])) {
            return;
        }

        $strategies[$strategyKey]['applied_at'] = now()->toIso8601String();
        $strategies[$strategyKey]['autonomous_content']['status'] = 'published';

        $entity->forceFill([
            'hijack_strategy' => json_encode($strategies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])->save();
    }

    private function firstNonEmptyString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
