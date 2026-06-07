<?php

namespace App\Jobs;

use App\Models\CompetitorKeyword;
use App\Models\SeoEntity;
use App\Services\Growth\SeoEntityResolver;
use App\Support\GrowthReadinessReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AutonomousContentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $competitorKeywordId) {}

    public function handle(): void
    {
        if (! Schema::hasTable('seo_entities') || ! Schema::hasColumn('seo_entities', 'hijack_strategy')) {
            return;
        }

        $keyword = CompetitorKeyword::query()
            ->with('competitor:id,name,website')
            ->find($this->competitorKeywordId);

        if ($keyword === null || ! $keyword->isHighIntent()) {
            return;
        }

        $apiKey = config('gemini.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            Log::notice('AutonomousContentJob skipped: Gemini API key not configured.', [
                'competitor_keyword_id' => $keyword->id,
            ]);

            return;
        }

        $entity = app(SeoEntityResolver::class)->forCurrentBusiness();
        if ($entity === null) {
            return;
        }

        $strategies = $entity->hijackStrategies();
        $strategyKey = (string) $keyword->id;
        $strategy = $strategies[$strategyKey] ?? null;
        if (! is_array($strategy)) {
            return;
        }

        try {
            $raw = $this->geminiGenerateText(trim($apiKey), $this->buildPrompt($strategy));
        } catch (Throwable $e) {
            Log::notice('AutonomousContentJob Gemini exception', [
                'competitor_keyword_id' => $keyword->id,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        if ($raw === '') {
            return;
        }

        $parsed = $this->decodeJsonObjectFromGemini($raw);
        if ($parsed === null) {
            return;
        }

        $strategy['autonomous_content'] = [
            'meta_title' => $this->stringOrNull($parsed['meta_title'] ?? null),
            'meta_description' => $this->stringOrNull($parsed['meta_description'] ?? null),
            'h1' => $this->stringOrNull($parsed['h1'] ?? null),
            'status' => 'ready',
            'generated_at' => now()->toIso8601String(),
        ];

        $strategies[$strategyKey] = $strategy;
        $entity->forceFill([
            'hijack_strategy' => json_encode($strategies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])->save();

        GrowthReadinessReport::forget();
    }

    /**
     * @param  array<string, mixed>  $strategy
     */
    private function buildPrompt(array $strategy): string
    {
        $payload = json_encode($strategy, JSON_UNESCAPED_UNICODE) ?: '{}';
        $market = app(\App\Services\Seo\LocalityContextResolver::class)->aiMarketContext() ?: 'service area';

        return <<<TXT
You are an SEO content architect for Medca Health Care (premium home healthcare, {$market}).

Using the hijack opportunity JSON below, produce ONLY valid JSON with keys:
"meta_title","meta_description","h1"

Rules:
- meta_title: max 60 chars, local trust + primary keyword.
- meta_description: max 155 chars, booking CTA, no false medical claims.
- h1: one compelling H1 for the landing page.
- Healthcare compliance: no guaranteed outcomes.
- JSON only, no markdown fences.

Hijack strategy context:
{$payload}
TXT;
    }

    private function geminiGenerateText(string $apiKey, string $prompt): string
    {
        $res = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                ]
            );

        if (! $res->successful()) {
            Log::notice('AutonomousContentJob Gemini HTTP failure', [
                'status' => $res->status(),
            ]);

            return '';
        }

        $text = data_get($res->json(), 'candidates.0.content.parts.0.text');

        return is_string($text) ? trim($text) : '';
    }

    private function decodeJsonObjectFromGemini(string $raw): ?array
    {
        $trim = trim($raw);
        if ($trim === '') {
            return null;
        }

        if (str_starts_with($trim, '```')) {
            $trim = preg_replace('/^```(?:json)?\s*/i', '', $trim) ?? $trim;
            $trim = preg_replace('/\s*```\s*$/', '', $trim) ?? $trim;
        }

        try {
            $data = json_decode($trim, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trim = trim($value);

        return $trim !== '' ? $trim : null;
    }
}
