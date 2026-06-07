<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MediaGeminiSuggestions
{
    /**
     * @return array{alt: string, title: string, caption: string, description: string}|null
     */
    public function suggest(Media $media, ?string $context = null): ?array
    {
        $apiKey = config('gemini.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $context = $context ?: $media->file_name;

        try {
            $response = Http::timeout(30)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.urlencode(trim($apiKey)),
                [
                    'contents' => [[
                        'parts' => [[
                            'text' => 'Return JSON only: {"alt":"","title":"","caption":"","description":""} for healthcare image: '.$context,
                        ]],
                    ]],
                ]
            );
            if (! $response->successful()) {
                return null;
            }
            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            if (! is_string($text)) {
                return null;
            }
            $decoded = json_decode(trim(preg_replace('/^```json\s*|\s*```$/', '', trim($text)) ?? ''), true);
            if (! is_array($decoded)) {
                return null;
            }

            return [
                'alt' => (string) ($decoded['alt'] ?? ''),
                'title' => (string) ($decoded['title'] ?? ''),
                'caption' => (string) ($decoded['caption'] ?? ''),
                'description' => (string) ($decoded['description'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Log::warning('MediaGeminiSuggestions', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
