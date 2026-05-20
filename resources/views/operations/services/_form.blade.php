@php
    /** @var \App\Models\Service $service */
    /** @var \Illuminate\Support\Collection<int, \App\Models\PinCode> $pinCodes */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Page> $detailPages */
    $mode = $mode ?? 'create';

    $detailPages = isset($detailPages) ? $detailPages : collect();

    $selectedPinIds = array_map(static fn ($v) => (int) $v, old('pincodes', $service->exists ? $service->pincodes->pluck('id')->all() : []));

    $faqRows = old('faqs', ($service->exists && $service->faqs->isNotEmpty())
        ? $service->faqs->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])->values()->all()
        : []);

    if (count($faqRows) < 3) {
        $faqRows = array_pad($faqRows, 3, ['question' => '', 'answer' => '']);
    }

    $schemaJsonPretty = old(
        'schema_json',
        ($service->schema && $service->schema->schema_json)
            ? json_encode($service->schema->schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : ''
    );

    $tk = old('target_keywords', $service->target_keywords ?? []);
    $ak = old('ai_keywords', $service->ai_keywords ?? []);
    $fk = old('seo.focus_keywords', optional($service->seo)->focus_keywords ?? []);
@endphp

<div class="space-y-8">
    {{-- Basic --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Basic') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="title" :value="__('Title')" variant="mom" />
                <x-text-input id="title" name="title" type="text" class="mt-2 block w-full" :value="old('title', $service->title)" required autofocus variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
            </div>
            <div>
                <x-input-label for="service_code" :value="__('Service code')" variant="mom" />
                @if ($mode === 'edit')
                    <input type="hidden" name="service_code" value="{{ $service->service_code }}" />
                    <input
                        id="service_code"
                        type="text"
                        class="mt-2 block w-full cursor-not-allowed rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.5)] px-3 py-2.5 text-sm text-[var(--text-muted)]"
                        value="{{ $service->service_code }}"
                        readonly
                        autocomplete="off"
                    />
                    <p class="mom-subtext mt-1">{{ __('Immutable identifier for Block Factory — cannot be changed.') }}</p>
                @else
                    <x-text-input id="service_code" name="service_code" type="text" class="mt-2 block w-full" :value="old('service_code')" required variant="mom" />
                    <p class="mom-subtext mt-1">{{ __('Letters, numbers, underscore, hyphen. Starts with a letter.') }}</p>
                @endif
                <x-input-error class="mt-2" :messages="$errors->get('service_code')" />
            </div>
            <div>
                <x-input-label for="price_range" :value="__('Price range')" variant="mom" />
                <x-text-input id="price_range" name="price_range" type="text" class="mt-2 block w-full" :value="old('price_range', $service->price_range)" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="short_summary" :value="__('Short summary')" variant="mom" />
                <textarea id="short_summary" name="short_summary" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('short_summary', $service->short_summary) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <x-input-label for="description" :value="__('Description')" variant="mom" />
                <textarea id="description" name="description" rows="8" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('description', $service->description) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <h4 class="mom-section-title text-base">{{ __('Detail carousel lists') }}</h4>
                <p class="mom-micro mt-1">{{ __('One item per line — shown on the public service detail carousel.') }}</p>
            </div>
            <div>
                <x-input-label for="procedures_lines" :value="__('Procedures')" variant="mom" />
                <textarea id="procedures_lines" name="procedures_lines" rows="4" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('procedures_lines', $service->listingLines('procedures')) }}</textarea>
            </div>
            <div>
                <x-input-label for="specialized_care_lines" :value="__('Specialized care')" variant="mom" />
                <textarea id="specialized_care_lines" name="specialized_care_lines" rows="4" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('specialized_care_lines', $service->listingLines('specialized_care')) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <x-input-label for="shifts_lines" :value="__('Shifts')" variant="mom" />
                <textarea id="shifts_lines" name="shifts_lines" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('shifts_lines', $service->listingLines('shifts')) }}</textarea>
            </div>
        </div>
    </section>

    {{-- Media --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Media') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="featured_image" :value="__('Featured image')" variant="mom" />
                <input id="featured_image" name="featured_image" type="file" accept="image/*" class="mom-subtext mt-2 block w-full text-sm" />
                @if ($mode === 'edit' && $service->featured_image)
                    <p class="mom-subtext mt-2">{{ __('Current file on disk; uploading replaces it.') }}</p>
                    <label class="mom-subtext mt-2 inline-flex items-center gap-2">
                        <input type="checkbox" name="clear_featured_image" value="1" class="rounded border-[rgba(255,255,255,0.15)]" />
                        {{ __('Clear featured image') }}
                    </label>
                @endif
            </div>
            <div>
                <x-input-label for="icon" :value="__('Icon')" variant="mom" />
                <input id="icon" name="icon" type="file" accept="image/*" class="mom-subtext mt-2 block w-full text-sm" />
                @if ($mode === 'edit' && $service->icon)
                    <label class="mom-subtext mt-2 inline-flex items-center gap-2">
                        <input type="checkbox" name="clear_icon" value="1" class="rounded border-[rgba(255,255,255,0.15)]" />
                        {{ __('Clear icon') }}
                    </label>
                @endif
            </div>
            <div class="md:col-span-2">
                <x-input-label for="gallery_files" :value="__('Gallery (append images)')" variant="mom" />
                <input id="gallery_files" name="gallery_files[]" type="file" accept="image/*" multiple class="mom-subtext mt-2 block w-full text-sm" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="image_alt" :value="__('Featured image alt text')" variant="mom" />
                <x-text-input id="image_alt" name="image_alt" type="text" class="mt-2 block w-full" :value="old('image_alt', $service->image_alt)" variant="mom" />
            </div>
        </div>
    </section>

    {{-- Keywords --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Keywords & quality') }}</h3>
        <div class="grid gap-6 md:grid-cols-3">
            @foreach (range(0, 11) as $i)
                <div>
                    @if ($i === 0)
                        <x-input-label :value="__('Target keywords')" variant="mom" />
                    @endif
                    <x-text-input name="target_keywords[]" type="text" class="{{ $i === 0 ? 'mt-2' : 'mt-2' }} block w-full" :value="data_get($tk, $i)" variant="mom" />
                </div>
            @endforeach
            @foreach (range(0, 11) as $i)
                <div>
                    @if ($i === 0)
                        <x-input-label :value="__('AI keywords')" variant="mom" />
                    @endif
                    <x-text-input name="ai_keywords[]" type="text" class="mt-2 block w-full" :value="data_get($ak, $i)" variant="mom" />
                </div>
            @endforeach
            <div class="md:col-span-2">
                <x-input-label for="quality_score" :value="__('Quality score (0–100)')" variant="mom" />
                <x-text-input id="quality_score" name="quality_score" type="number" min="0" max="100" class="mt-2 block w-full" :value="old('quality_score', $service->quality_score)" variant="mom" />
            </div>
        </div>
    </section>

    {{-- Control --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Control') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="publish_status" :value="__('Publish status')" variant="mom" />
                <select id="publish_status" name="publish_status" class="rounded-mom-chrome mt-2 block w-full border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                    @foreach (\App\Enums\PublishStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('publish_status', $service->publish_status?->value ?? \App\Enums\PublishStatus::Draft->value) === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="visibility" :value="__('Visibility')" variant="mom" />
                <select id="visibility" name="visibility" class="rounded-mom-chrome mt-2 block w-full border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                    @foreach (\App\Enums\ServiceVisibility::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('visibility', $service->visibility?->value ?? \App\Enums\ServiceVisibility::Public->value) === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="sort_order" :value="__('Sort order')" variant="mom" />
                <x-text-input id="sort_order" name="sort_order" type="number" class="mt-2 block w-full" :value="old('sort_order', $service->sort_order)" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="detail_page_id" :value="__('Detail page (blocks layout for /services/CODE)')" variant="mom" />
                <select id="detail_page_id" name="detail_page_id" class="rounded-mom-chrome mt-2 block w-full border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                    <option value="">{{ __('— Auto: page slug :slug if it exists —', ['slug' => $suggestedDetailPageSlug ?? 'service-{code}']) }}</option>
                    @foreach ($detailPages as $p)
                        <option value="{{ $p->id }}" @selected((int) old('detail_page_id', $service->detail_page_id) === (int) $p->id)>{{ $p->title }} ({{ $p->slug }})</option>
                    @endforeach
                </select>
                @php
                    $serviceTokenHint = '{{service:'.($service->service_code ?: 'code').'}}';
                @endphp
                <p class="mom-subtext mt-1">
                    {{ __('Public URL /services/:code renders the linked Site Architect page (canvas + blocks). Use :token in blocks; add other services via Insert service for related rows.', ['code' => $service->service_code ?: 'CODE', 'token' => $serviceTokenHint]) }}
                </p>
                @if (isset($patternDetailPage) && $patternDetailPage !== null && (int) $service->detail_page_id !== (int) $patternDetailPage->id)
                    <p class="mom-subtext mt-1">{{ __('An active page exists at slug :slug and will be used when no page is selected above.', ['slug' => $patternDetailPage->slug]) }}</p>
                @endif
                <x-input-error class="mt-2" :messages="$errors->get('detail_page_id')" />
            </div>
            <div class="flex flex-col gap-4 pt-8">
                <input type="hidden" name="is_active" value="0" />
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('is_active', $service->is_active ?? true)) />
                    <span class="text-sm text-[var(--text-secondary)]">{{ __('Active') }}</span>
                </label>
                <input type="hidden" name="is_featured" value="0" />
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('is_featured', $service->is_featured ?? false)) />
                    <span class="text-sm text-[var(--text-secondary)]">{{ __('Featured') }}</span>
                </label>
            </div>
        </div>
    </section>

    {{-- SEO --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('SEO') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="seo_meta_title" :value="__('Meta title')" variant="mom" />
                <x-text-input id="seo_meta_title" name="seo[meta_title]" type="text" class="mt-2 block w-full" :value="old('seo.meta_title', optional($service->seo)->meta_title)" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="seo_meta_description" :value="__('Meta description')" variant="mom" />
                <textarea id="seo_meta_description" name="seo[meta_description]" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('seo.meta_description', optional($service->seo)->meta_description) }}</textarea>
            </div>
            @foreach (range(0, 9) as $i)
                <div>
                    @if ($i === 0)
                        <x-input-label :value="__('Focus keywords')" variant="mom" />
                    @endif
                    <x-text-input name="seo[focus_keywords][]" type="text" class="mt-2 block w-full" :value="data_get($fk, $i)" variant="mom" />
                </div>
            @endforeach
            <div class="md:col-span-2">
                <x-input-label for="seo_h1" :value="__('H1')" variant="mom" />
                <x-text-input id="seo_h1" name="seo[h1]" type="text" class="mt-2 block w-full" :value="old('seo.h1', optional($service->seo)->h1)" variant="mom" />
            </div>
            @foreach (range(0, 7) as $i)
                <div class="md:col-span-2">
                    @if ($i === 0)
                        <x-input-label :value="__('H2 headings')" variant="mom" />
                    @endif
                    <x-text-input name="seo[h2][]" type="text" class="mt-2 block w-full" :value="old('seo.h2.'.$i, data_get(optional($service->seo)->h2, $i))" variant="mom" />
                </div>
            @endforeach
            @foreach (range(0, 7) as $i)
                <div class="md:col-span-2">
                    @if ($i === 0)
                        <x-input-label :value="__('H3 headings')" variant="mom" />
                    @endif
                    <x-text-input name="seo[h3][]" type="text" class="mt-2 block w-full" :value="old('seo.h3.'.$i, data_get(optional($service->seo)->h3, $i))" variant="mom" />
                </div>
            @endforeach
        </div>
    </section>

    {{-- AEO --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('AEO') }}</h3>
        <div class="space-y-6">
            @foreach ($faqRows as $idx => $row)
                <div class="rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-4">
                    <p class="mom-micro mb-2">{{ __('FAQ') }} #{{ $idx + 1 }}</p>
                    <x-input-label :for="'faq_q_'.$idx" :value="__('Question')" variant="mom" />
                    <textarea id="{{ 'faq_q_'.$idx }}" name="faqs[{{ $idx }}][question]" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('faqs.'.$idx.'.question', $row['question'] ?? '') }}</textarea>
                    <x-input-label class="mt-3" :for="'faq_a_'.$idx" :value="__('Answer')" variant="mom" />
                    <textarea id="{{ 'faq_a_'.$idx }}" name="faqs[{{ $idx }}][answer]" rows="4" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('faqs.'.$idx.'.answer', $row['answer'] ?? '') }}</textarea>
                </div>
            @endforeach
            <div>
                <x-input-label for="seo_ai_context" :value="__('AI context')" variant="mom" />
                <textarea id="seo_ai_context" name="seo[ai_context]" rows="5" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('seo.ai_context', optional($service->seo)->ai_context) }}</textarea>
            </div>
            <div>
                <x-input-label for="seo_search_intent" :value="__('Search intent')" variant="mom" />
                <x-text-input id="seo_search_intent" name="seo[search_intent]" type="text" class="mt-2 block w-full" :value="old('seo.search_intent', optional($service->seo)->search_intent)" variant="mom" />
            </div>
        </div>
    </section>

    {{-- Schema --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Structured data (JSON-LD)') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="schema_type" :value="__('Schema type')" variant="mom" />
                <x-text-input id="schema_type" name="schema_type" type="text" class="mt-2 block w-full" :value="old('schema_type', optional($service->schema)->schema_type ?? 'MedicalBusiness')" placeholder="MedicalBusiness, Service, …" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="schema_json" :value="__('Schema JSON')" variant="mom" />
                <textarea id="schema_json" name="schema_json" rows="14" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-xs text-[var(--text-primary)] shadow-mom-inner" placeholder='{"@@context":"https://schema.org","@@type":"Service"}'>{{ $schemaJsonPretty }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('schema_json')" />
            </div>
        </div>
    </section>

    {{-- GEO --}}
    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('GEO — serviceable pincodes') }}</h3>
        <p class="mom-body-text mb-4 max-w-3xl">{{ __('Select existing coverage areas from your pin code directory. No manual pin strings.') }}</p>
        <div x-data="{ q: '' }" class="space-y-3">
            <input
                type="search"
                x-model="q"
                class="block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner"
                placeholder="{{ __('Filter by pincode, area, city…') }}"
                autocomplete="off"
            />
            <div class="custom-scrollbar max-h-72 overflow-y-auto rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(0,0,0,0.12)] p-3">
                @forelse ($pinCodes as $pc)
                    @php $blob = strtolower($pc->pincode.' '.$pc->area_name.' '.$pc->city.' '.(string) $pc->locality); @endphp
                    <label
                        class="flex cursor-pointer gap-3 rounded px-2 py-1.5 hover:bg-[var(--bg-hover)]"
                        x-show='q.trim() === "" || {{ json_encode($blob) }}.toLowerCase().includes(q.toLowerCase())'
                    >
                        <input
                            type="checkbox"
                            name="pincodes[]"
                            value="{{ $pc->id }}"
                            class="mt-1 rounded border-[rgba(255,255,255,0.15)]"
                            @checked(in_array((int) $pc->id, $selectedPinIds, true))
                        />
                        <span class="text-sm text-[var(--text-secondary)]">
                            <span class="font-mono text-[var(--text-primary)]">{{ $pc->pincode }}</span>
                            — {{ $pc->area_name }}, {{ $pc->city }}
                            @if ($pc->locality)
                                <span class="text-[var(--text-muted)]">({{ $pc->locality }})</span>
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="mom-subtext text-sm">
                        {{ __('No pin codes in the directory yet. Add or import pin codes first, then return here.') }}
                        <a href="{{ route('operations.pin-codes.directory') }}" class="text-[var(--accent)] underline underline-offset-2">{{ __('Open pin code directory') }}</a>
                    </p>
                @endforelse
            </div>
        </div>
    </section>
</div>
