<?php

namespace App\Services\Discovery\Expansion;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Seo\ConversationalAeoFaqBuilder;

class AeoExpansionEngine
{
    public function __construct(
        private readonly ConversationalAeoFaqBuilder $conversationalFaqs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forCategoryPage(ServiceCategory $category, Page $page): array
    {
        $category->loadMissing(['faqs', 'seo']);
        $first = $category->faqs->first();

        return array_filter([
            'aeo_question' => $first?->question ?: $category->seo?->aeo_question,
            'aeo_answer' => $first?->answer ?: $category->seo?->aeo_answer,
            'ai_context' => $category->seo?->ai_context,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSubServicePage(SubService $sub, Page $page): array
    {
        $sub->loadMissing(['faqs', 'seo']);
        $first = $sub->faqs->first();

        return array_filter([
            'aeo_question' => $first?->question,
            'aeo_answer' => $first?->answer,
            'ai_context' => $sub->seo?->ai_context,
        ]);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function faqsForCategory(ServiceCategory $category): array
    {
        $category->loadMissing('faqs');

        return $category->faqs->map(fn ($f): array => [
            'question' => $f->question,
            'answer' => $f->answer,
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function conversationalForLocation(Service $service, PinCode $pin): array
    {
        return $this->conversationalFaqs->forLocation($service, $pin);
    }
}
