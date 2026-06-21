<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScoreLeadPayloadJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    public function handle(): void
    {
        $lead = $this->lead->fresh();
        if ($lead === null) {
            return;
        }

        $apiKey = config('gemini.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            Log::notice('ScoreLeadPayloadJob skipped: Gemini API key not configured.', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        $payload = $this->buildLeadContext($lead);
        $prompt = $this->buildPrompt($payload);

        try {
            $raw = $this->geminiGenerateText(trim($apiKey), $prompt);
        } catch (Throwable $e) {
            Log::notice('ScoreLeadPayloadJob Gemini exception', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        if ($raw === '') {
            return;
        }

        $parsed = $this->decodeJsonObjectFromGemini($raw);
        if ($parsed === null) {
            Log::notice('ScoreLeadPayloadJob could not parse Gemini JSON.', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        $score = $this->normalizeScore($parsed['ai_priority_score'] ?? null);
        $category = $this->normalizeIntentCategory($parsed['ai_intent_category'] ?? null);

        if ($score === null && $category === null) {
            return;
        }

        $lead->forceFill([
            'ai_priority_score' => $score,
            'ai_intent_category' => $category,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLeadContext(Lead $lead): array
    {
        $notes = $lead->relationLoaded('notes')
            ? $lead->notes
            : $lead->notes()->limit(10)->get();

        $noteBodies = $notes
            ->pluck('note')
            ->filter(fn ($note) => is_string($note) && trim($note) !== '')
            ->values()
            ->all();

        return [
            'name' => $lead->name,
            'service' => $lead->service,
            'message' => $lead->message,
            'source' => $lead->source instanceof \BackedEnum ? $lead->source->value : (string) $lead->source,
            'campaign' => $lead->campaign,
            'notes' => $noteBodies,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildPrompt(array $payload): string
    {
        $ctx = json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}';
        $city = app(\App\Services\Seo\LocalityContextResolver::class)->primaryCity() ?: 'service area';

        return <<<TXT
You are a healthcare lead triage analyst for Karnataka Diagnostic Centre in {$city}.

Analyze the lead payload and return ONLY valid JSON with exactly these keys:
"ai_priority_score","ai_intent_category"

Rules:
- ai_priority_score: integer from 1 (low) to 10 (critical follow-up).
- ai_intent_category: short label such as "High Urgency", "General Inquiry", "Price Shopping", "Spam", or "Follow-up Needed".
- Base urgency on message tone, service type, and any notes.
- Do not use markdown code fences. JSON only.

Lead payload:
{$ctx}
TXT;
    }

    private function geminiGenerateText(string $apiKey, string $prompt): string
    {
        $res = Http::timeout(25)
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
            Log::notice('ScoreLeadPayloadJob Gemini HTTP failure', [
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

    private function normalizeScore(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $score = (int) round((float) $value);

        return max(1, min(10, $score));
    }

    private function normalizeIntentCategory(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $category = trim($value);

        return $category !== '' ? mb_substr($category, 0, 120) : null;
    }
}
