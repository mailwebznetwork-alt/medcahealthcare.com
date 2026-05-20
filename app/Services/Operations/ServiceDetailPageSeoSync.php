<?php

namespace App\Services\Operations;

use App\Models\Page;
use App\Models\PageFaq;
use App\Models\Service;

/**
 * Copies legacy Operations → Services SEO/AEO/schema into the linked Site Architect page.
 */
class ServiceDetailPageSeoSync
{
    public function migrateFromServiceIfEmpty(Service $service, Page $page): bool
    {
        $service->loadMissing(['seo', 'faqs', 'schema']);

        $changed = false;

        if ($this->pageFieldUnset($page, 'meta_title') && filled($service->seo?->meta_title)) {
            $page->meta_title = $service->seo->meta_title;
            $changed = true;
        }

        if ($this->pageFieldUnset($page, 'meta_description') && filled($service->seo?->meta_description)) {
            $page->meta_description = $service->seo->meta_description;
            $changed = true;
        }

        $focus = $this->normalizeStringList($service->seo?->focus_keywords);
        if ($page->focus_keywords === null && $focus !== []) {
            $page->focus_keywords = $focus;
            $page->keywords = implode(', ', $focus);
            $changed = true;
        }

        if ($this->pageFieldUnset($page, 'h1') && filled($service->seo?->h1)) {
            $page->h1 = $service->seo->h1;
            $changed = true;
        }

        $h2 = $this->normalizeStringList($service->seo?->h2);
        if ($page->heading_h2 === null && $h2 !== []) {
            $page->heading_h2 = $h2;
            if (! filled($page->h2)) {
                $page->h2 = $h2[0];
            }
            $changed = true;
        }

        $h3 = $this->normalizeStringList($service->seo?->h3);
        if ($page->heading_h3 === null && $h3 !== []) {
            $page->heading_h3 = $h3;
            if (! filled($page->h3)) {
                $page->h3 = $h3[0];
            }
            $changed = true;
        }

        if (! filled($page->ai_context) && filled($service->seo?->ai_context)) {
            $page->ai_context = $service->seo->ai_context;
            $changed = true;
        }

        if (! filled($page->search_intent) && filled($service->seo?->search_intent)) {
            $page->search_intent = $service->seo->search_intent;
            $changed = true;
        }

        if (! filled($page->aeo_question) && $service->faqs->isNotEmpty()) {
            $first = $service->faqs->first();
            if ($first !== null && filled($first->question)) {
                $page->aeo_question = $first->question;
                $page->aeo_answer = $first->answer;
                $changed = true;
            }
        }

        if ($page->schema_json === null && $service->schema !== null && is_array($service->schema->schema_json) && $service->schema->schema_json !== []) {
            $page->schema_json = $service->schema->schema_json;
            $changed = true;
        }

        if (! filled($page->schema_type) && filled($service->schema?->schema_type)) {
            $page->schema_type = $service->schema->schema_type;
            $changed = true;
        }

        if (! filled($page->canonical_url)) {
            $page->canonical_url = $service->publicUrl();
            $changed = true;
        }

        if ($changed) {
            $page->save();
        }

        if ($page->faqs()->count() === 0 && $service->faqs->isNotEmpty()) {
            foreach ($service->faqs->values() as $index => $faq) {
                if (! filled($faq->question) || ! filled($faq->answer)) {
                    continue;
                }
                PageFaq::query()->create([
                    'page_id' => $page->id,
                    'sort_order' => $index,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ]);
            }
        }

        return $changed || $page->faqs()->exists();
    }

    private function pageFieldUnset(Page $page, string $field): bool
    {
        $value = $page->{$field};

        if (! filled($value)) {
            return true;
        }

        if (in_array($field, ['meta_title', 'h1'], true)) {
            return trim((string) $value) === trim((string) $page->title);
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($v) => is_string($v) && $v !== ''));
    }
}
