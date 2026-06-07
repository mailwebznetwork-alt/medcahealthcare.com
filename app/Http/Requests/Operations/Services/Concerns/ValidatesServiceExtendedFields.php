<?php

namespace App\Http\Requests\Operations\Services\Concerns;

trait ValidatesServiceExtendedFields
{
    /**
     * @return array<string, mixed>
     */
    protected function extendedServiceFieldRules(): array
    {
        return [
            'key_benefits_lines' => ['nullable', 'string'],
            'key_benefits' => ['nullable', 'array'],
            'key_benefits.*' => ['string', 'max:500'],
            'eligibility_lines' => ['nullable', 'string'],
            'eligibility' => ['nullable', 'array'],
            'eligibility.*' => ['string', 'max:500'],
            'process_steps_lines' => ['nullable', 'string'],
            'process_steps' => ['nullable', 'array'],
            'process_steps.*' => ['string', 'max:500'],
            'ai_summary' => ['nullable', 'string', 'max:65535'],
            'gallery_meta' => ['nullable', 'array'],
            'trust_signals' => ['nullable', 'array'],
            'trust_signals.years_experience' => ['nullable', 'string', 'max:40'],
            'trust_signals.certifications' => ['nullable', 'string', 'max:65535'],
            'trust_signals.accreditations' => ['nullable', 'string', 'max:65535'],
            'trust_signals.awards' => ['nullable', 'string', 'max:65535'],
            'trust_signals.google_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'trust_signals.review_count' => ['nullable', 'integer', 'min:0'],
            'specialized_care_lines' => ['nullable', 'string'],
            'specialized_care' => ['nullable', 'array'],
            'specialized_care.*' => ['string', 'max:500'],
            'shifts_lines' => ['nullable', 'string'],
            'shifts' => ['nullable', 'array'],
            'shifts.*' => ['string', 'max:500'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'featured_image_meta' => ['nullable', 'array'],
            'featured_image_meta.alt' => ['nullable', 'string', 'max:255'],
            'featured_image_meta.title' => ['nullable', 'string', 'max:255'],
            'featured_image_meta.caption' => ['nullable', 'string', 'max:500'],
            'featured_image_meta.description' => ['nullable', 'string', 'max:65535'],
            'gallery_meta' => ['nullable', 'array'],
            'gallery_meta.*.alt' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'target_keywords_lines' => ['nullable', 'string'],
            'target_keywords' => ['nullable', 'array'],
            'target_keywords.*' => ['string', 'max:120'],
            'ai_keywords_lines' => ['nullable', 'string'],
            'ai_keywords' => ['nullable', 'array'],
            'ai_keywords.*' => ['string', 'max:120'],
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:65535'],
            'seo.h1' => ['nullable', 'string', 'max:255'],
            'seo.ai_context' => ['nullable', 'string', 'max:65535'],
            'seo.search_intent' => ['nullable', 'string', 'max:120'],
            'seo.secondary_keywords_lines' => ['nullable', 'string'],
            'seo.secondary_keywords' => ['nullable', 'array'],
            'seo.secondary_keywords.*' => ['string', 'max:120'],
            'seo.canonical_url' => ['nullable', 'string', 'max:500'],
            'seo.robots_index' => ['nullable', 'boolean'],
            'seo.og_title' => ['nullable', 'string', 'max:255'],
            'seo.og_description' => ['nullable', 'string', 'max:500'],
            'seo.og_image' => ['nullable', 'string', 'max:500'],
            'seo.twitter_card' => ['nullable', 'string', 'max:40'],
            'seo.entity_tags_lines' => ['nullable', 'string'],
            'seo.entity_tags' => ['nullable', 'array'],
            'seo.entity_tags.*' => ['string', 'max:120'],
            'seo.geo_entities_lines' => ['nullable', 'string'],
            'seo.geo_entities' => ['nullable', 'array'],
            'seo.geo_entities.*' => ['string', 'max:120'],
            'seo.focus_keywords_lines' => ['nullable', 'string'],
            'seo.focus_keywords' => ['nullable', 'array'],
            'seo.focus_keywords.*' => ['string', 'max:120'],
            'seo.h2_lines' => ['nullable', 'string'],
            'seo.h2' => ['nullable', 'array'],
            'seo.h2.*' => ['string', 'max:255'],
            'seo.h3_lines' => ['nullable', 'string'],
            'seo.h3' => ['nullable', 'array'],
            'seo.h3.*' => ['string', 'max:255'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'string', 'max:65535'],
            'schema_type' => ['nullable', 'string', 'max:120'],
            'active_tab' => ['nullable', 'string', 'max:40'],
            'apply_related_to_page' => ['nullable', 'boolean'],
            'related_service_codes' => ['nullable', 'array'],
            'related_service_codes.*' => ['string', 'max:120', 'regex:/^[a-zA-Z][a-zA-Z0-9_-]*$/'],
            'review_moderation' => ['nullable', 'array'],
            'review_moderation.*.id' => ['nullable', 'integer'],
            'review_moderation.*.status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'schema_json' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail(__('Schema JSON must be valid JSON.'));
                    }
                },
            ],
        ];
    }
}
