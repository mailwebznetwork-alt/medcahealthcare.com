<?php

namespace App\Jobs;

use App\Jobs\AutonomousContentJob;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorTracking;
use App\Models\SiteKeywordRanking;
use App\Services\Growth\SeoEntityResolver;
use App\Support\GrowthReadinessReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeHijackOpportunityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $competitorKeywordId) {}

    public function handle(): void
    {
        $keyword = CompetitorKeyword::query()
            ->with('competitor:id,name,website')
            ->find($this->competitorKeywordId);

        if ($keyword === null || ! $keyword->isHighIntent()) {
            return;
        }

        $priority = (int) ($keyword->hijack_priority ?? 0);
        if ($priority < 1) {
            return;
        }

        $apiKey = config('gemini.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            Log::notice('AnalyzeHijackOpportunityJob skipped: Gemini API key not configured.', [
                'competitor_keyword_id' => $keyword->id,
            ]);

            return;
        }

        $latestPositions = CompetitorTracking::latestPositionsByKeywordIds([$keyword->id]);
        $competitorPosition = $latestPositions->get($keyword->id);
        $ourPosition = SiteKeywordRanking::latestPositionForKeyword($keyword->keyword);

        if ($competitorPosition === null || $ourPosition === null || $competitorPosition >= $ourPosition) {
            return;
        }

        $context = [
            'keyword' => $keyword->keyword,
            'intent_type' => $keyword->intent_type,
            'hijack_priority' => $priority,
            'competitor_name' => $keyword->competitor?->name,
            'competitor_website' => $keyword->competitor?->website,
            'competitor_position' => $competitorPosition,
            'our_position' => $ourPosition,
            'position_gap' => $ourPosition - $competitorPosition,
            'search_volume' => $keyword->search_volume,
            'difficulty' => $keyword->difficulty,
        ];

        try {
            $raw = $this->geminiGenerateText(trim($apiKey), $this->buildPrompt($context));
        } catch (Throwable $e) {
            Log::notice('AnalyzeHijackOpportunityJob Gemini exception', [
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
            Log::notice('AnalyzeHijackOpportunityJob could not parse Gemini JSON.', [
                'competitor_keyword_id' => $keyword->id,
            ]);

            return;
        }

        $this->persistStrategy($keyword->id, array_merge($context, [
            'meta_title' => $this->stringOrNull($parsed['meta_title'] ?? null),
            'meta_description' => $this->stringOrNull($parsed['meta_description'] ?? null),
            'h1_suggestion' => $this->stringOrNull($parsed['h1_suggestion'] ?? null),
            'content_changes' => $this->stringList($parsed['content_changes'] ?? []),
            'schema_hint' => $this->stringOrNull($parsed['schema_hint'] ?? null),
            'generated_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(array $context): string
    {
        $payload = json_encode($context, JSON_UNESCAPED_UNICODE) ?: '{}';
        $market = app(\App\Services\Seo\LocalityContextResolver::class)->aiMarketContext() ?: 'service area';

        return <<<TXT
You are an SEO growth strategist for MEDCA Consultancy (premium healthcare career consultancy, {$market}).

A competitor outranks us on a high-intent keyword. Return ONLY valid JSON with these keys:
"meta_title","meta_description","h1_suggestion","content_changes","schema_hint"

Rules:
- meta_title: max 60 characters, local trust + keyword relevance.
- meta_description: max 155 characters, clear CTA for bookings.
- h1_suggestion: one H1 line for the target landing page.
- content_changes: array of 3-5 specific on-page edits (not generic advice).
- schema_hint: one sentence on LocalBusiness/MedicalOrganization JSON-LD tweak.
- Healthcare consultancy compliance: no false claims, no guaranteed outcomes.
- No markdown fences. JSON only.

Opportunity context:
{$payload}
TXT;
    }

    /**
     * @param  array<string, mixed>  $strategy
     */
    private function persistStrategy(int $competitorKeywordId, array $strategy): void
    {
        $entity = app(SeoEntityResolver::class)->ensureForCurrentBusiness();

        $existing = [];
        if (is_string($entity->hijack_strategy) && trim($entity->hijack_strategy) !== '') {
            try {
                $decoded = json_decode($entity->hijack_strategy, true, 512, JSON_THROW_ON_ERROR);
                $existing = is_array($decoded) ? $decoded : [];
            } catch (Throwable) {
                $existing = [];
            }
        }

        $existing[(string) $competitorKeywordId] = $strategy;

        $entity->forceFill([
            'hijack_strategy' => json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])->save();

        GrowthReadinessReport::forget();

        AutonomousContentJob::dispatch($competitorKeywordId);
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
            Log::notice('AnalyzeHijackOpportunityJob Gemini HTTP failure', [
                'status' => $res->status(),
                'body_preview' => mb_substr($res->body(), 0, 500),
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

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return $out;
    }
}
