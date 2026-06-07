@php
    /** @var \App\Models\Service $service */
    /** @var \App\Models\SubService $subService */
    $mode = $mode ?? 'create';
    $seo = $subService->seo;
    $faqSeed = old('faqs');
    if ($faqSeed === null) {
        $faqSeed = $subService->exists
            ? $subService->faqs->map(static fn ($faq): array => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->values()->all()
            : [];
    }
    if ($faqSeed === []) {
        $faqSeed = [['question' => '', 'answer' => '']];
    }
@endphp

<div class="space-y-8">
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Basic') }}</h3>
        <p class="mom-subtext mb-4">{{ __('Parent service: :title (:code)', ['title' => $service->title, 'code' => $service->service_code]) }}</p>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="sub_service_code" :value="__('Sub-service code')" variant="mom" />
                <x-text-input id="sub_service_code" name="sub_service_code" type="text" class="mt-2 block w-full font-mono text-sm" :value="old('sub_service_code', $subService->sub_service_code)" required :readonly="$mode === 'edit'" variant="mom" />
                <p class="mom-subtext mt-1">{{ __('URL: /services/:parent/sub/:code', ['parent' => $service->service_code, 'code' => old('sub_service_code', $subService->sub_service_code ?: 'your-code')]) }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('sub_service_code')" />
            </div>
            <div>
                <x-input-label for="sort_order" :value="__('Sort order')" variant="mom" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-2 block w-full" :value="old('sort_order', $subService->sort_order ?? 0)" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="title" :value="__('Title')" variant="mom" />
                <x-text-input id="title" name="title" type="text" class="mt-2 block w-full" :value="old('title', $subService->title)" required autofocus variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="short_summary" :value="__('Short summary')" variant="mom" />
                <textarea id="short_summary" name="short_summary" rows="2" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('short_summary', $subService->short_summary) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <x-input-label for="description" :value="__('Description')" variant="mom" />
                <textarea id="description" name="description" rows="6" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('description', $subService->description) }}</textarea>
            </div>
        </div>
    </section>

    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('SEO') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="seo_meta_title" :value="__('Meta title')" variant="mom" />
                <x-text-input id="seo_meta_title" name="seo[meta_title]" type="text" class="mt-2 block w-full" :value="old('seo.meta_title', $seo?->meta_title)" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="seo_meta_description" :value="__('Meta description')" variant="mom" />
                <textarea id="seo_meta_description" name="seo[meta_description]" rows="3" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('seo.meta_description', $seo?->meta_description) }}</textarea>
            </div>
            <div>
                <x-input-label for="seo_h1" :value="__('H1 override')" variant="mom" />
                <x-text-input id="seo_h1" name="seo[h1]" type="text" class="mt-2 block w-full" :value="old('seo.h1', $seo?->h1)" variant="mom" />
            </div>
            <div>
                <x-input-label for="seo_focus_keywords" :value="__('Focus keywords')" variant="mom" />
                <x-text-input id="seo_focus_keywords" name="seo[focus_keywords]" type="text" class="mt-2 block w-full" :value="old('seo.focus_keywords', is_array($seo?->focus_keywords) ? implode(', ', $seo->focus_keywords) : '')" variant="mom" />
            </div>
        </div>
    </section>

    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('FAQ') }}</h3>
        <div class="space-y-4">
            @foreach ($faqSeed as $i => $faq)
                <div class="grid gap-3 rounded-mom-chrome border border-[rgba(255,255,255,0.06)] p-4 md:grid-cols-2">
                    <div>
                        <x-input-label :for="'faq_q_'.$i" :value="__('Question')" variant="mom" />
                        <x-text-input :id="'faq_q_'.$i" name="faqs[{{ $i }}][question]" type="text" class="mt-2 block w-full" :value="$faq['question'] ?? ''" variant="mom" />
                    </div>
                    <div>
                        <x-input-label :for="'faq_a_'.$i" :value="__('Answer')" variant="mom" />
                        <textarea :id="'faq_a_'.$i" name="faqs[{{ $i }}][answer]" rows="2" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ $faq['answer'] ?? '' }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Publishing') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="publish_status" :value="__('Publish status')" variant="mom" />
                <select id="publish_status" name="publish_status" class="rounded-mom-chrome mt-2 block w-full border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">
                    @foreach (\App\Enums\PublishStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('publish_status', $subService->publish_status?->value ?? 'published') === $status->value)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="visibility" :value="__('Visibility')" variant="mom" />
                <select id="visibility" name="visibility" class="rounded-mom-chrome mt-2 block w-full border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">
                    @foreach (\App\Enums\ServiceVisibility::cases() as $vis)
                        <option value="{{ $vis->value }}" @selected(old('visibility', $subService->visibility?->value ?? 'public') === $vis->value)>{{ ucfirst($vis->value) }}</option>
                    @endforeach
                </select>
            </div>
            @foreach ([
                'is_active' => __('Active'),
                'is_featured' => __('Featured'),
                'is_top_rated' => __('Top rated'),
                'show_on_homepage' => __('Show on homepage'),
                'show_on_about' => __('Show on about'),
                'show_on_contact' => __('Show on contact'),
            ] as $field => $label)
                <div class="flex items-center gap-3">
                    <input type="hidden" name="{{ $field }}" value="0" />
                    <input id="{{ $field }}" name="{{ $field }}" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold" @checked(old($field, $subService->{$field} ?? false)) />
                    <x-input-label :for="$field" :value="$label" variant="mom" />
                </div>
            @endforeach
        </div>
    </section>
</div>
