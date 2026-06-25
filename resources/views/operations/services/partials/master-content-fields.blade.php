@php
    use App\Services\Import\ImportSupport;

    $arrayLines = $arrayLines ?? static function (mixed $items): string {
        $items = ImportSupport::normalizeLineArray($items);
        if ($items === []) {
            return '';
        }

        return implode("\n", $items);
    };
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="key_benefits_lines" :value="__('Key benefits (one per line)')" variant="mom" />
        <textarea id="key_benefits_lines" name="key_benefits_lines" rows="4" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('key_benefits_lines', $arrayLines($service->key_benefits)) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="eligibility_lines" :value="__('Eligibility / suitable for (one per line)')" variant="mom" />
        <textarea id="eligibility_lines" name="eligibility_lines" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('eligibility_lines', $arrayLines($service->eligibility)) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="process_steps_lines" :value="__('Process / how it works (one step per line)')" variant="mom" />
        <textarea id="process_steps_lines" name="process_steps_lines" rows="4" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('process_steps_lines', $arrayLines($service->process_steps)) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="quick_answer" :value="__('Quick answer (AEO / featured snippet)')" variant="mom" />
        <textarea id="quick_answer" name="quick_answer" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('quick_answer', $service->quick_answer) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="why_medca" :value="__('Why Karnataka Diagnostics')" variant="mom" />
        <textarea id="why_medca" name="why_medca" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('why_medca', $service->why_medca) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="key_takeaways_lines" :value="__('Key takeaways (one per line)')" variant="mom" />
        <textarea id="key_takeaways_lines" name="key_takeaways_lines" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('key_takeaways_lines', $arrayLines($service->key_takeaways ?? null)) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="activities_included_lines" :value="__('Activities included (one per line)')" variant="mom" />
        <textarea id="activities_included_lines" name="activities_included_lines" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('activities_included_lines', $arrayLines($service->activities_included ?? null)) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="ai_summary" :value="__('AI summary (discovery / GEO)')" variant="mom" />
        <textarea id="ai_summary" name="ai_summary" rows="4" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('ai_summary', $service->ai_summary) }}</textarea>
        <p class="mom-subtext mt-1">{{ __('Used for AI Overviews, Copilot, Gemini, ChatGPT, and Perplexity-style discovery.') }}</p>
    </div>
</div>
