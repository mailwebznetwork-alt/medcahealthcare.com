<?php

namespace App\Services\Operations;

use App\Models\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceGeminiAssistant
{
    /**
     * @return array{ai_summary?: string, faq_suggestions?: list<array{question: string, answer: string}>, recommendations?: list<string>}|null
     */
    public function suggest(Service $service): ?array
    {
        if (! config('services_master.gemini_suggestions', true)) {
            return null;
        }

        $apiKey = config('gemini.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $service->loadMissing(['seo', 'faqs', 'pincodes']);

        $prompt = $this->buildPrompt($service);

        try {
            $response = Http::timeout(45)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.urlencode(trim($apiKey)),
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.4,
                            'maxOutputTokens' => 2048,
                        ],
                    ]
                );

            if (! $response->successful()) {
                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            if (! is_string($text) || trim($text) === '') {
                return null;
            }

            return $this->parseJsonResponse($text);
        } catch (\Throwable $e) {
            Log::warning('ServiceGeminiAssistant failed', ['service_id' => $service->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function buildPrompt(Service $service): string
    {
        $brand = config('medca.brand_name', 'MEDCA Consultancy');
        $location = app(\App\Services\Seo\LocalityContextResolver::class)->aiMarketContext()
            ?: config('medca.location_display', '');

        return <<<PROMPT
You are an SEO/AEO/GEO specialist for {$brand} ({$location}, India healthcare career consultancy).

Service: {$service->title} (code: {$service->service_code})
Summary: {$service->short_summary}
Description excerpt: {$this->excerpt($service->description)}

Return ONLY valid JSON:
{
  "ai_summary": "2-3 sentences for AI discovery (Google AI Overviews, Perplexity, ChatGPT)",
  "faq_suggestions": [{"question":"...","answer":"..."}],
  "recommendations": ["short actionable SEO tip", "..."]
}
Provide 3 FAQ suggestions and 3 recommendations. No markdown.
PROMPT;
    }

    /**
     * @return array{ai_summary?: string, faq_suggestions?: list<array{question: string, answer: string}>, recommendations?: list<string>}|null
     */
    private function parseJsonResponse(string $text): ?array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function excerpt(?string $html): string
    {
        $plain = trim(strip_tags((string) $html));

        return mb_substr($plain, 0, 600);
    }
}
