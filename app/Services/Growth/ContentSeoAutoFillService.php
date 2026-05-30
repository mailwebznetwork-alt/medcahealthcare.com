<?php

namespace App\Services\Growth;

use App\Models\Blog;
use App\Models\BusinessProfile;
use App\Models\Page;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Models\SeoAiSignal;
use App\Models\SeoEntity;
use App\Models\Service;
use App\Models\ServiceSeo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ContentSeoAutoFillService
{
    public function applyToPage(Page $page): void
    {
        if (! config('growth.content_seo_auto_fill', true)) {
            return;
        }

        $fillEmptyOnly = (bool) config('growth.content_seo_fill_only_empty', true);
        $plain = $this->plainExcerptFromHtml((string) $page->content);
        $gemini = $this->maybeGeminiPayloadForPageOrBlog($page->title, $plain, $fillEmptyOnly, [
            'meta_description' => $page->meta_description,
            'aeo_question' => $page->aeo_question,
            'aeo_answer' => $page->aeo_answer,
        ]);

        if ($this->shouldFill((string) $page->meta_title, $fillEmptyOnly)) {
            $page->meta_title = mb_substr((string) $page->title, 0, 255);
        }

        if ($this->shouldFill((string) $page->meta_description, $fillEmptyOnly)) {
            $page->meta_description = $gemini['meta_description'] ?? $this->truncate($plain !== '' ? $plain : (string) $page->title, 320);
        }

        if ($this->shouldFill((string) $page->h1, $fillEmptyOnly)) {
            $page->h1 = mb_substr((string) $page->title, 0, 255);
        }

        if ($this->shouldFill((string) $page->canonical_url, $fillEmptyOnly)) {
            $page->canonical_url = $page->publicUrl();
        }

        if ($this->shouldFill((string) $page->robots_meta, $fillEmptyOnly)) {
            $page->robots_meta = 'index,follow';
        }

        if ($this->shouldFill((string) $page->aeo_question, $fillEmptyOnly)) {
            $page->aeo_question = $gemini['aeo_question'] ?? 'What is '.(string) $page->title.'?';
        }

        if ($this->shouldFill((string) $page->aeo_answer, $fillEmptyOnly)) {
            $page->aeo_answer = $gemini['aeo_answer'] ?? $this->truncate($plain !== '' ? $plain : (string) $page->title, 600);
        }

        if ($fillEmptyOnly ? blank($page->schema_json) : true) {
            $page->schema_json = $this->buildWebPageSchema(
                (string) $page->title,
                (string) $page->meta_description,
                $page->publicUrl()
            );
        }
    }

    public function applyToBlog(Blog $blog): void
    {
        if (! config('growth.content_seo_auto_fill', true)) {
            return;
        }

        $fillEmptyOnly = (bool) config('growth.content_seo_fill_only_empty', true);
        $plain = $this->plainExcerptFromHtml((string) $blog->content);
        if ($plain === '' && filled($blog->excerpt)) {
            $plain = $this->truncate((string) $blog->excerpt, 500);
        }

        $gemini = $this->maybeGeminiPayloadForPageOrBlog($blog->title, $plain, $fillEmptyOnly, [
            'meta_description' => $blog->meta_description,
            'aeo_question' => $blog->aeo_question,
            'aeo_answer' => $blog->aeo_answer,
        ]);

        if ($this->shouldFill((string) $blog->meta_title, $fillEmptyOnly)) {
            $blog->meta_title = mb_substr((string) $blog->title, 0, 255);
        }

        if ($this->shouldFill((string) $blog->meta_description, $fillEmptyOnly)) {
            $blog->meta_description = $gemini['meta_description'] ?? $this->truncate($plain !== '' ? $plain : (string) $blog->title, 320);
        }

        if ($this->shouldFill((string) $blog->h1, $fillEmptyOnly)) {
            $blog->h1 = mb_substr((string) $blog->title, 0, 255);
        }

        if ($this->shouldFill((string) $blog->aeo_question, $fillEmptyOnly)) {
            $blog->aeo_question = $gemini['aeo_question'] ?? 'What is '.(string) $blog->title.'?';
        }

        if ($this->shouldFill((string) $blog->aeo_answer, $fillEmptyOnly)) {
            $blog->aeo_answer = $gemini['aeo_answer'] ?? $this->truncate($plain !== '' ? $plain : (string) $blog->title, 600);
        }

        if ($fillEmptyOnly ? blank($blog->schema_json) : true) {
            $blog->schema_json = $this->buildWebPageSchema(
                (string) $blog->title,
                (string) $blog->meta_description,
                url('/blog/'.ltrim((string) $blog->slug, '/'))
            );
        }
    }

    public function syncPageGrowthArtifacts(Page $page): void
    {
        if (! config('growth.content_seo_auto_fill', true)) {
            return;
        }

        $slugPath = $page->publicPath();

        if (Schema::hasTable('page_seo') && Schema::hasTable('business_profiles')) {
            $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
                ?? BusinessProfile::query()->latest('id')->first();

            if ($profile instanceof BusinessProfile) {
                PageSeo::query()->updateOrCreate(
                    ['page_slug' => $slugPath],
                    [
                        'business_profile_id' => $profile->id,
                        'meta_title' => $page->meta_title,
                        'meta_description' => $page->meta_description,
                        'schema_json' => is_array($page->schema_json) ? $page->schema_json : [],
                    ]
                );
            }
        }

        $this->syncPageElementsForSlug($slugPath, [
            'meta_title' => (string) $page->meta_title,
            'meta_description' => (string) $page->meta_description,
            'aeo_question' => (string) $page->aeo_question,
            'aeo_answer' => (string) $page->aeo_answer,
        ]);
    }

    public function syncBlogGrowthArtifacts(Blog $blog): void
    {
        if (! config('growth.content_seo_auto_fill', true)) {
            return;
        }

        $slugPath = '/blog/'.ltrim((string) $blog->slug, '/');

        if (Schema::hasTable('page_seo') && Schema::hasTable('business_profiles')) {
            $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
                ?? BusinessProfile::query()->latest('id')->first();

            if ($profile instanceof BusinessProfile) {
                PageSeo::query()->updateOrCreate(
                    ['page_slug' => $slugPath],
                    [
                        'business_profile_id' => $profile->id,
                        'meta_title' => $blog->meta_title,
                        'meta_description' => $blog->meta_description,
                        'schema_json' => is_array($blog->schema_json) ? $blog->schema_json : [],
                    ]
                );
            }
        }

        $this->syncPageElementsForSlug($slugPath, [
            'meta_title' => (string) $blog->meta_title,
            'meta_description' => (string) $blog->meta_description,
            'aeo_question' => (string) $blog->aeo_question,
            'aeo_answer' => (string) $blog->aeo_answer,
        ]);
    }

    public function applyAndSyncService(Service $service): void
    {
        if (! config('growth.content_seo_auto_fill', true)) {
            return;
        }

        $fillEmptyOnly = (bool) config('growth.content_seo_fill_only_empty', true);
        $plain = $this->plainExcerptFromHtml((string) $service->description);
        if ($plain === '' && filled($service->short_summary)) {
            $plain = $this->truncate((string) $service->short_summary, 500);
        }

        $geminiDesc = null;
        if (config('growth.content_seo_gemini', true) && $this->geminiApiKey() !== '') {
            if (! $fillEmptyOnly || blank($service->seo?->meta_description)) {
                $geminiDesc = $this->geminiMetaDescriptionOnly((string) $service->title, $plain);
            }
        }

        $service->loadMissing('seo');

        $seoPayload = [
            'meta_title' => null,
            'meta_description' => null,
            'h1' => null,
            'ai_context' => null,
        ];

        if ($service->seo instanceof ServiceSeo) {
            if ($this->shouldFill((string) $service->seo->meta_title, $fillEmptyOnly)) {
                $seoPayload['meta_title'] = mb_substr((string) $service->title, 0, 255);
            }
            if ($this->shouldFill((string) $service->seo->meta_description, $fillEmptyOnly)) {
                $seoPayload['meta_description'] = $geminiDesc ?? $this->truncate($plain !== '' ? $plain : (string) $service->title, 320);
            }
            if ($this->shouldFill((string) $service->seo->h1, $fillEmptyOnly)) {
                $seoPayload['h1'] = mb_substr((string) $service->title, 0, 255);
            }
            if ($this->shouldFill((string) $service->seo->ai_context, $fillEmptyOnly)) {
                $seoPayload['ai_context'] = $this->truncate($plain !== '' ? $plain : (string) $service->short_summary, 800);
            }
        } else {
            $seoPayload = [
                'meta_title' => mb_substr((string) $service->title, 0, 255),
                'meta_description' => $geminiDesc ?? $this->truncate($plain !== '' ? $plain : (string) $service->title, 320),
                'h1' => mb_substr((string) $service->title, 0, 255),
                'ai_context' => $this->truncate($plain !== '' ? $plain : (string) $service->short_summary, 800),
            ];
        }

        $seoPayload = array_filter($seoPayload, fn (?string $v): bool => $v !== null && $v !== '');

        if ($seoPayload !== []) {
            $service->seo()->updateOrCreate(
                ['service_id' => $service->id],
                $seoPayload
            );
        }

        $service->refresh('seo');

        $slugPath = 'services/'.ltrim((string) $service->service_code, '/');

        if (Schema::hasTable('page_seo') && Schema::hasTable('business_profiles')) {
            $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
                ?? BusinessProfile::query()->latest('id')->first();

            if ($profile instanceof BusinessProfile && $service->seo instanceof ServiceSeo) {
                PageSeo::query()->updateOrCreate(
                    ['page_slug' => $slugPath],
                    [
                        'business_profile_id' => $profile->id,
                        'meta_title' => $service->seo->meta_title,
                        'meta_description' => $service->seo->meta_description,
                        'schema_json' => ['@type' => 'Service', 'name' => $service->title],
                    ]
                );
            }
        }

        $this->syncPageElementsForSlug($slugPath, [
            'meta_title' => (string) ($service->seo?->meta_title ?? $service->title),
            'meta_description' => (string) ($service->seo?->meta_description ?? ''),
            'aeo_question' => 'What is '.(string) $service->title.'?',
            'aeo_answer' => $this->truncate($plain !== '' ? $plain : (string) $service->short_summary, 600),
        ]);
    }

    public function refreshAggregateSignals(): void
    {
        if (! config('growth.content_seo_auto_fill', true)) {
            return;
        }

        if (! Schema::hasTable('seo_ai_signals') || ! Schema::hasTable('business_profiles')) {
            return;
        }

        $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();

        if (! $profile instanceof BusinessProfile) {
            return;
        }

        $richPages = Schema::hasTable('pages')
            ? Page::query()
                ->where('is_active', true)
                ->whereNotNull('meta_description')
                ->where('meta_description', '!=', '')
                ->count()
            : 0;

        $richBlogs = Schema::hasTable('blogs')
            ? Blog::query()
                ->where('is_published', true)
                ->whereNotNull('meta_description')
                ->where('meta_description', '!=', '')
                ->count()
            : 0;

        $services = Schema::hasTable('services') && Schema::hasTable('service_seo')
            ? Service::query()
                ->whereHas('seo', fn ($q) => $q->whereNotNull('meta_description')->where('meta_description', '!=', ''))
                ->count()
            : 0;

        $llmScore = min(100, $richPages * 8 + $richBlogs * 8 + $services * 10 + 10);

        $entityScore = Schema::hasTable('seo_entities')
            && SeoEntity::query()->where('business_profile_id', $profile->id)->exists()
            ? min(100, 40 + $richPages * 4)
            : min(60, $richPages * 6);

        $existingSignal = SeoAiSignal::query()->where('business_profile_id', $profile->id)->first();

        SeoAiSignal::query()->updateOrCreate(
            ['business_profile_id' => $profile->id],
            [
                'ai_crawl_enabled' => $existingSignal?->ai_crawl_enabled ?? false,
                'llm_visibility_score' => $llmScore,
                'entity_consistency_score' => $entityScore,
            ]
        );
    }

    /**
     * @param  array{meta_description?: mixed, aeo_question?: mixed, aeo_answer?: mixed}  $current
     * @return array{meta_description?: string, aeo_question?: string, aeo_answer?: string}
     */
    private function maybeGeminiPayloadForPageOrBlog(?string $title, string $plain, bool $fillEmptyOnly, array $current): array
    {
        $out = [];
        if (! config('growth.content_seo_gemini', true)) {
            return $out;
        }

        $key = $this->geminiApiKey();
        if ($key === '') {
            return $out;
        }

        $needMeta = $this->shouldFill((string) ($current['meta_description'] ?? ''), $fillEmptyOnly);
        $needQ = $this->shouldFill((string) ($current['aeo_question'] ?? ''), $fillEmptyOnly);
        $needA = $this->shouldFill((string) ($current['aeo_answer'] ?? ''), $fillEmptyOnly);

        if (! $needMeta && ! $needQ && ! $needA) {
            return $out;
        }

        $decoded = $this->geminiSeoJson($key, (string) $title, $plain);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{meta_description?: string, aeo_question?: string, aeo_answer?: string}|null
     */
    private function geminiSeoJson(string $apiKey, string $title, string $plain): ?array
    {
        $prompt = <<<PROMPT
Return ONLY a JSON object with keys meta_description (max 155 chars), aeo_question (max 120 chars), aeo_answer (max 400 chars).
No markdown. Language: match the title language if possible.
Title: {$title}
Body excerpt: {$plain}
PROMPT;

        $raw = trim($this->geminiGenerateText($apiKey, $prompt));
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\s*```$/', '', $raw) ?? $raw;

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        $out = [];
        foreach (['meta_description', 'aeo_question', 'aeo_answer'] as $k) {
            if (isset($data[$k]) && is_string($data[$k]) && trim($data[$k]) !== '') {
                $out[$k] = trim($data[$k]);
            }
        }

        return $out === [] ? null : $out;
    }

    private function geminiMetaDescriptionOnly(string $title, string $plain): ?string
    {
        $key = $this->geminiApiKey();
        if ($key === '') {
            return null;
        }

        $prompt = "Return ONLY plain text meta description max 155 characters for this service. Title: {$title}. Summary: {$plain}";
        $txt = trim($this->geminiGenerateText($key, $prompt));

        return $txt !== '' ? $this->truncate($txt, 155) : null;
    }

    private function geminiGenerateText(string $apiKey, string $prompt): string
    {
        if (trim($apiKey) === '') {
            return '';
        }

        try {
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
                Log::notice('Content SEO Gemini HTTP failure', ['status' => $res->status()]);

                return '';
            }

            $text = data_get($res->json(), 'candidates.0.content.parts.0.text');

            return is_string($text) ? $text : '';
        } catch (Throwable $e) {
            Log::notice('Content SEO Gemini exception', ['message' => $e->getMessage()]);

            return '';
        }
    }

    private function geminiApiKey(): string
    {
        $key = config('gemini.api_key');

        return is_string($key) ? trim($key) : '';
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function syncPageElementsForSlug(string $slugPath, array $fields): void
    {
        if (! Schema::hasTable('page_elements')) {
            return;
        }

        PageElement::query()->where('page_slug', $slugPath)->delete();

        $rows = [
            ['section' => 'meta', 'key' => 'title', 'value' => $fields['meta_title'] ?? '', 'type' => 'text'],
            ['section' => 'meta', 'key' => 'description', 'value' => $fields['meta_description'] ?? '', 'type' => 'text'],
            ['section' => 'aeo', 'key' => 'question', 'value' => $fields['aeo_question'] ?? '', 'type' => 'text'],
            ['section' => 'aeo', 'key' => 'answer', 'value' => $fields['aeo_answer'] ?? '', 'type' => 'text'],
        ];

        foreach ($rows as $row) {
            if (trim((string) $row['value']) === '') {
                continue;
            }
            PageElement::query()->create([
                'page_slug' => $slugPath,
                'section' => $row['section'],
                'key' => $row['key'],
                'value' => $row['value'],
                'type' => $row['type'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWebPageSchema(string $title, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $this->truncate($description, 300),
            'url' => $url,
        ];
    }

    private function plainExcerptFromHtml(string $html): string
    {
        $html = preg_replace('/\{\{[^}]+\}\}/', ' ', $html) ?? $html;
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
        $text = preg_replace('/\s+/', ' ', trim($text));

        return $text;
    }

    private function truncate(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }

    private function shouldFill(string $value, bool $fillEmptyOnly): bool
    {
        if (! $fillEmptyOnly) {
            return true;
        }

        return blank($value);
    }
}
