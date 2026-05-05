<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('AEO') }}</h2>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('AI answer engine optimization signals.') }}</p>
    <form method="post" action="{{ route('growth-center.aeo.store') }}" class="mt-6 space-y-4">
        @csrf
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="ai_crawl_enabled" value="0">
            <input type="checkbox" name="ai_crawl_enabled" value="1" @checked((bool) old('ai_crawl_enabled', $seoAiSignal?->ai_crawl_enabled ?? false)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
            <span class="mom-micro">{{ __('AI Crawl Toggle') }}</span>
        </label>
        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            <label class="block">
                <span class="mom-micro mb-1 block">{{ __('LLM Visibility Score') }}</span>
                <input type="number" min="0" max="100" name="llm_visibility_score" value="{{ old('llm_visibility_score', $seoAiSignal?->llm_visibility_score ?? 0) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
            </label>
            <label class="block">
                <span class="mom-micro mb-1 block">{{ __('Entity Consistency Score') }}</span>
                <input type="number" min="0" max="100" name="entity_consistency_score" value="{{ old('entity_consistency_score', $seoAiSignal?->entity_consistency_score ?? 0) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
            </label>
        </div>
        <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save AEO Signals') }}</button>
    </form>

    <div class="mt-6 rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.45)] p-4">
        <p class="mom-micro">{{ __('Global LLM access') }}</p>
        <pre class="mom-body-text mt-3 whitespace-pre-wrap text-[var(--text-secondary)]">User-agent: GPTBot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: ClaudeBot
Allow: /</pre>
    </div>
</section>
