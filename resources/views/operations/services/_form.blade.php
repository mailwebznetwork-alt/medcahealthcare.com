@php
    use App\Services\Import\ImportSupport;

    /** @var \App\Models\Service $service */
    /** @var \Illuminate\Support\Collection<int, \App\Models\PinCode> $pinCodes */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Page> $detailPages */
    $mode = $mode ?? 'create';
    $catalogKind = $catalogKind ?? 'service';
    $activeTab = $activeTab ?? 'basic';
    $detailPages = isset($detailPages) ? $detailPages : collect();
    $pincodeDefaults = is_array($selectedPinIds ?? null) ? $selectedPinIds : [];
    if ($pincodeDefaults === [] && $service->exists) {
        if (in_array($catalogKind, ['service', 'category'], true) && method_exists($service, 'pincodes')) {
            $service->loadMissing('pincodes');
            $pincodeDefaults = $service->pincodes?->pluck('id')->all() ?? [];
        } elseif ($catalogKind === 'sub_service' && isset($subService) && method_exists($subService, 'includedPincodeIds')) {
            $pincodeDefaults = $subService->includedPincodeIds();
        }
    }
    $selectedPinIds = array_map(static fn ($v) => (int) $v, old('pincodes', $pincodeDefaults));
    $categoryOptions = $categoryOptions ?? collect();
    $categoryIdDefaults = [];
    if ($catalogKind === 'service' && $service->exists && method_exists($service, 'categories')) {
        $service->loadMissing('categories');
        $categoryIdDefaults = $service->categories?->pluck('id')->all() ?? [];
    }
    $selectedCategoryIds = array_map(static fn ($v) => (int) $v, old('category_ids', $categoryIdDefaults));
    $primaryCategoryDefault = 0;
    if ($catalogKind === 'service' && $service->exists) {
        $service->loadMissing('categories');
        $primaryCategoryDefault = (int) ($service->categories->firstWhere(fn ($cat) => (bool) $cat->pivot?->is_primary)?->id
            ?? $service->categories->first()?->id
            ?? 0);
    }
    $selectedPrimaryCategoryId = (int) old('primary_category_id', $primaryCategoryDefault);
    $service->loadMissing(['seo', 'schema', 'faqs']);
    $seo = $service->seo;
    $schema = $service->schema;

    $arrayLines = static function (mixed $items): string {
        $items = ImportSupport::normalizeLineArray($items);
        if ($items === []) {
            return '';
        }

        return implode("\n", $items);
    };

    $faqSeed = old('faqs');
    if ($faqSeed === null) {
        $faqSeed = $service->exists
            ? $service->faqs->map(static fn ($faq): array => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->values()->all()
            : [];
    }
    if ($faqSeed === []) {
        $faqSeed = [['question' => '', 'answer' => '']];
    }

    $schemaJsonDisplay = old('schema_json');
    if ($schemaJsonDisplay === null && $schema !== null && is_array($schema->schema_json) && $schema->schema_json !== []) {
        $schemaJsonDisplay = json_encode($schema->schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    if (! isset($linkedDetailPage)) {
        $detailPageId = (int) old('detail_page_id', $service->detail_page_id);
        $linkedDetailPage = $detailPageId > 0
            ? ($detailPages->firstWhere('id', $detailPageId) ?? \App\Models\Page::query()->find($detailPageId))
            : ($patternDetailPage ?? null);
    }
@endphp

<div x-data="{ tab: @js($activeTab) }" class="space-y-0">
    @include('operations.partials.architect-save-form-flags')

    @include('operations.services.partials.tabs-nav', [
        'mode' => $mode,
        'activeTab' => $activeTab,
        'catalogKind' => $catalogKind,
        'service' => $service,
        'serviceReviews' => $serviceReviews ?? collect(),
        'subServices' => $subServices ?? collect(),
        'managedModule' => $managedModule ?? null,
    ])

    @if ($mode === 'edit')
        @include($catalogKind === 'service' ? 'operations.services.partials.optimization-hub' : 'operations.catalog.partials.optimization-hub', [
            'service' => $service,
            'catalogKind' => $catalogKind,
            'optimizationScores' => $optimizationScores ?? [],
            'seoRecommendations' => $seoRecommendations ?? [],
            'locationPageCount' => $locationPageCount ?? 0,
        ])
    @endif

    <div x-show="tab === 'basic'" x-cloak class="space-y-8">
        @if ($catalogKind === 'category')
            @include('operations.catalog.partials.basic-category', ['category' => $category ?? $service, 'service' => $service, 'mode' => $mode, 'parentOptions' => $parentOptions ?? []])
        @elseif ($catalogKind === 'sub_service')
            @include('operations.catalog.partials.basic-sub-service', ['subService' => $subService ?? $service, 'service' => $service, 'parentService' => $parentService ?? null, 'mode' => $mode])
        @else
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
                        <input id="service_code" type="text" class="mt-2 block w-full cursor-not-allowed rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.5)] px-3 py-2.5 text-sm text-[var(--text-muted)]" value="{{ $service->service_code }}" readonly autocomplete="off" />
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
                <div class="md:col-span-2" x-data="{
                    catQ: '',
                    primary: @js($selectedPrimaryCategoryId),
                    pickPrimary(catId) {
                        this.primary = catId;
                        const box = document.getElementById('category-include-' + catId);
                        if (box) box.checked = true;
                    },
                    onIncludeToggle(catId, included) {
                        if (! included && this.primary === catId) {
                            this.primary = 0;
                            const radios = document.querySelectorAll('input[name=primary_category_id]');
                            radios.forEach((r) => { if (Number(r.value) === catId) r.checked = false; });
                        }
                    },
                }">
                    <x-input-label for="category_ids" :value="__('Categories')" variant="mom" />
                    <p class="mom-subtext mt-1">{{ __('Check categories to include this service. Mark one as Primary — it controls inherited pincodes (not a union across categories).') }}</p>
                    @if ($categoryOptions->isEmpty())
                        <p class="mt-3 text-sm text-[var(--text-muted)]">
                            {{ __('No categories yet.') }}
                            <a href="{{ route('operations.service-categories.create') }}" class="text-mom-gold hover:underline">{{ __('Create a category') }}</a>
                        </p>
                    @else
                        <input type="search" x-model="catQ" class="mom-input mt-3 block w-full text-sm" placeholder="{{ __('Filter categories…') }}" autocomplete="off" />
                        <div class="mt-3 max-h-48 overflow-y-auto custom-scrollbar rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                            <div class="mb-2 grid grid-cols-[auto_auto_1fr] items-center gap-x-3 gap-y-0 px-1 text-[10px] font-semibold uppercase tracking-wide text-[var(--text-muted)]">
                                <span class="w-5 text-center">{{ __('Include') }}</span>
                                <span class="w-5 text-center">{{ __('Primary') }}</span>
                                <span>{{ __('Category') }}</span>
                            </div>
                            <div class="space-y-2">
                            @foreach ($categoryOptions as $cat)
                                @php $blob = strtolower($cat->name.' '.$cat->code); @endphp
                                <label x-show="!catQ || @js($blob).includes(catQ.toLowerCase())" class="grid grid-cols-[auto_auto_1fr] items-start gap-x-3 gap-y-0 text-sm">
                                    <input
                                        id="category-include-{{ $cat->id }}"
                                        type="checkbox"
                                        name="category_ids[]"
                                        value="{{ $cat->id }}"
                                        class="mt-0.5 h-4 w-4 rounded border-[rgba(255,255,255,0.15)]"
                                        @checked(in_array((int) $cat->id, $selectedCategoryIds, true))
                                        @change="onIncludeToggle({{ $cat->id }}, $event.target.checked)"
                                    />
                                    <input
                                        type="radio"
                                        name="primary_category_id"
                                        value="{{ $cat->id }}"
                                        class="mt-0.5 h-4 w-4 rounded-full border-[rgba(255,255,255,0.15)]"
                                        title="{{ __('Primary category (pincodes)') }}"
                                        @checked($selectedPrimaryCategoryId === (int) $cat->id)
                                        @click="pickPrimary({{ $cat->id }})"
                                    />
                                    <span>
                                        <span class="font-medium text-[var(--text-primary)]">{{ $cat->name }}</span>
                                        <span class="block font-mono text-[10px] text-[var(--text-muted)]">{{ $cat->code }}</span>
                                    </span>
                                </label>
                            @endforeach
                            </div>
                        </div>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('category_ids')" />
                </div>
            </div>
        </section>
        @endif
    </div>

    <div x-show="tab === 'content'" x-cloak class="space-y-8">
        <section class="mom-card p-6">
            <h3 class="mom-section-title mb-4">{{ __('Content') }}</h3>
            <div class="grid gap-6">
                <div>
                    <x-input-label for="short_summary" :value="__('Short summary')" variant="mom" />
                    <textarea id="short_summary" name="short_summary" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('short_summary', $service->short_summary) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('short_summary')" />
                </div>
                <div>
                    <x-input-label for="description" :value="__('Description')" variant="mom" />
                    <textarea id="description" name="description" rows="10" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('description', $service->description) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>
                <div>
                    <x-input-label for="procedures_lines" :value="__('Procedures')" variant="mom" />
                    <textarea id="procedures_lines" name="procedures_lines" rows="6" placeholder="{{ __('One procedure per line') }}" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('procedures_lines', $service->exists ? $service->listingLines('procedures') : '') }}</textarea>
                    <p class="mom-subtext mt-1">{{ __('Shown on service detail layouts that include the procedures carousel block.') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('procedures_lines')" />
                </div>
                @include('operations.services.partials.master-content-fields', ['service' => $service, 'arrayLines' => $arrayLines])
            </div>
        </section>
    </div>

    <div x-show="tab === 'images'" x-cloak class="space-y-8">
        @include('operations.services.partials.image-seo-tab', ['service' => $service, 'catalogKind' => $catalogKind])
    </div>

    <div x-show="tab === 'media'" x-cloak class="space-y-8">
        <section class="mom-card p-6">
            <h3 class="mom-section-title mb-4">{{ __('Media') }}</h3>
            <div class="grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2 space-y-3">
                    @livewire('media.media-picker-field', [
                        'fieldName' => 'featured_media_id',
                        'value' => old('featured_media_id', $service->featured_media_id),
                        'label' => __('Featured image (library)'),
                    ], key('svc-featured-media-'.$service->id))
                    <x-input-label for="featured_image" :value="__('Or upload featured image')" variant="mom" />
                    <input id="featured_image" name="featured_image" type="file" accept="image/*" class="mt-2 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-chrome file:border-0 file:bg-[rgba(197,160,89,0.15)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[var(--text-primary)]" />
                    <x-input-error class="mt-2" :messages="$errors->get('featured_image')" />
                </div>
                <div>
                    <x-input-label for="image_alt" :value="__('Featured image alt text')" variant="mom" />
                    <x-text-input id="image_alt" name="image_alt" type="text" class="mt-2 block w-full" :value="old('image_alt', $service->image_alt)" variant="mom" />
                    <x-input-error class="mt-2" :messages="$errors->get('image_alt')" />
                </div>
                <div class="space-y-3">
                    @livewire('media.media-picker-field', [
                        'fieldName' => 'icon_media_id',
                        'value' => old('icon_media_id', $service->icon_media_id),
                        'label' => __('Icon (library)'),
                    ], key('svc-icon-media-'.$service->id))
                    <x-input-label for="icon" :value="__('Or upload icon')" variant="mom" />
                    <input id="icon" name="icon" type="file" accept="image/*" class="mt-2 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-chrome file:border-0 file:bg-[rgba(197,160,89,0.15)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[var(--text-primary)]" />
                    <x-input-error class="mt-2" :messages="$errors->get('icon')" />
                </div>
                <div class="md:col-span-2 space-y-3">
                    @livewire('media.media-gallery-picker-field', [], key('svc-gallery-pick-'.$service->id))
                    <x-input-label for="gallery_files" :value="__('Or upload gallery images')" variant="mom" />
                    @php $galleryItems = is_array($service->gallery) ? array_values(array_filter($service->gallery)) : []; @endphp
                    @if ($galleryItems !== [])
                        <ul class="mt-3 space-y-2">
                            @foreach ($galleryItems as $galleryPath)
                                @php
                                    $galleryPreview = \Illuminate\Support\Str::startsWith($galleryPath, ['http://', 'https://'])
                                        ? $galleryPath
                                        : asset('storage/'.$galleryPath);
                                @endphp
                                <li class="flex items-center gap-3 rounded-mom-chrome border border-[rgba(255,255,255,0.06)] p-2">
                                    <img src="{{ $galleryPreview }}" alt="" class="h-14 w-20 shrink-0 rounded object-cover" />
                                    <label class="flex flex-1 cursor-pointer items-center gap-2 text-sm text-[var(--text-secondary)]">
                                        <input type="checkbox" name="remove_gallery[]" value="{{ $galleryPath }}" class="rounded border-[rgba(255,255,255,0.15)]" />
                                        {{ __('Remove') }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <input id="gallery_files" name="gallery_files[]" type="file" accept="image/*" multiple class="mt-2 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-chrome file:border-0 file:bg-[rgba(197,160,89,0.15)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[var(--text-primary)]" />
                    <p class="mom-subtext mt-1">{{ __('Upload one or more images. New files are appended to the gallery.') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('gallery_files')" />
                    <x-input-error class="mt-2" :messages="$errors->get('gallery_files.*')" />
                </div>
            </div>
        </section>
    </div>

    <div x-show="tab === 'clinical'" x-cloak class="space-y-8">
        <section class="mom-card p-6">
            <h3 class="mom-section-title mb-4">{{ __('Clinical lists') }}</h3>
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="specialized_care_lines" :value="__('Specialized care')" variant="mom" />
                    <textarea id="specialized_care_lines" name="specialized_care_lines" rows="5" placeholder="{{ __('One item per line') }}" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('specialized_care_lines', $service->exists ? $service->listingLines('specialized_care') : '') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('specialized_care_lines')" />
                </div>
                <div>
                    <x-input-label for="shifts_lines" :value="__('Shifts / availability')" variant="mom" />
                    <textarea id="shifts_lines" name="shifts_lines" rows="5" placeholder="{{ __('One item per line') }}" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('shifts_lines', $service->exists ? $service->listingLines('shifts') : '') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('shifts_lines')" />
                </div>
            </div>
        </section>
    </div>

    <div x-show="tab === 'seo'" x-cloak class="space-y-8">
        <section class="mom-card p-6">
            <h3 class="mom-section-title mb-2">{{ __('SEO') }}</h3>
            @include('operations.services.partials._seo-canonical-banner', ['linkedDetailPage' => $linkedDetailPage ?? null])
            @php
                $pageSeoLocksFields = \App\Services\Operations\ServiceSeoOwnership::pageSeoOverridesService($linkedDetailPage ?? null);
                $seoLockedClass = 'mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner opacity-70';
                $seoOpenClass = 'mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner';
            @endphp
            @if (isset($linkedDetailPage) && $linkedDetailPage && ! $pageSeoLocksFields)
                <p class="mom-body-text mb-4 text-mom-gold">
                    <a href="{{ route('operations.services.detail-page.edit', $service) }}" class="underline">{{ __('Edit blocks & page SEO in Site Architect') }}</a>
                </p>
            @endif
            <div class="grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="seo_meta_title" :value="__('Meta title')" variant="mom" />
                    <x-text-input id="seo_meta_title" name="seo[meta_title]" type="text" :value="old('seo.meta_title', $seo?->meta_title)" variant="mom" @if ($pageSeoLocksFields) readonly class="mt-2 block w-full opacity-70" @else class="mt-2 block w-full" @endif />
                    <x-input-error class="mt-2" :messages="$errors->get('seo.meta_title')" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="seo_meta_description" :value="__('Meta description')" variant="mom" />
                    <textarea id="seo_meta_description" name="seo[meta_description]" rows="3" @class([$seoLockedClass => $pageSeoLocksFields, $seoOpenClass => ! $pageSeoLocksFields]) @readonly($pageSeoLocksFields)>{{ old('seo.meta_description', $seo?->meta_description) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('seo.meta_description')" />
                </div>
                <div>
                    <x-input-label for="seo_h1" :value="__('H1')" variant="mom" />
                    <x-text-input id="seo_h1" name="seo[h1]" type="text" :value="old('seo.h1', $seo?->h1)" variant="mom" @if ($pageSeoLocksFields) readonly class="mt-2 block w-full opacity-70" @else class="mt-2 block w-full" @endif />
                    <x-input-error class="mt-2" :messages="$errors->get('seo.h1')" />
                </div>
                <div>
                    <x-input-label for="seo_focus_keywords_lines" :value="__('Focus keywords')" variant="mom" />
                    <textarea id="seo_focus_keywords_lines" name="seo[focus_keywords_lines]" rows="3" placeholder="{{ __('One keyword per line') }}" @class([$seoLockedClass => $pageSeoLocksFields, $seoOpenClass => ! $pageSeoLocksFields, 'font-mono' => true]) @readonly($pageSeoLocksFields)>{{ old('seo.focus_keywords_lines', $arrayLines($seo?->focus_keywords)) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('seo.focus_keywords')" />
                </div>
                <div>
                    <x-input-label for="seo_h2_lines" :value="__('H2 headings')" variant="mom" />
                    <textarea id="seo_h2_lines" name="seo[h2_lines]" rows="4" placeholder="{{ __('One heading per line') }}" @class([$seoLockedClass => $pageSeoLocksFields, $seoOpenClass => ! $pageSeoLocksFields, 'font-mono' => true]) @readonly($pageSeoLocksFields)>{{ old('seo.h2_lines', $arrayLines($seo?->h2)) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('seo.h2')" />
                </div>
                <div>
                    <x-input-label for="seo_h3_lines" :value="__('H3 headings')" variant="mom" />
                    <textarea id="seo_h3_lines" name="seo[h3_lines]" rows="4" placeholder="{{ __('One heading per line') }}" @class([$seoLockedClass => $pageSeoLocksFields, $seoOpenClass => ! $pageSeoLocksFields, 'font-mono' => true]) @readonly($pageSeoLocksFields)>{{ old('seo.h3_lines', $arrayLines($seo?->h3)) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('seo.h3')" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="target_keywords_lines" :value="__('Target keywords (catalog)')" variant="mom" />
                    <textarea id="target_keywords_lines" name="target_keywords_lines" rows="3" placeholder="{{ __('One keyword per line') }}" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('target_keywords_lines', $arrayLines($service->target_keywords)) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('target_keywords')" />
                </div>
            </div>
            @include('operations.services.partials.master-seo-social', ['service' => $service])
        </section>
    </div>

    <div x-show="tab === 'trust'" x-cloak class="space-y-8">
        @include('operations.services.partials.trust-tab', ['service' => $service, 'catalogKind' => $catalogKind])
    </div>

    <div x-show="tab === 'aeo'" x-cloak class="space-y-8">
        <section class="mom-card p-6">
            <h3 class="mom-section-title mb-2">{{ __('AEO — Answer Engine Optimization') }}</h3>
            <p class="mom-subtext mb-6 max-w-3xl">{{ __('AI context, search intent, and answer keywords for assistants and rich results.') }}</p>
            <div class="grid gap-6">
                <div>
                    <x-input-label for="seo_ai_context" :value="__('AI context')" variant="mom" />
                    <textarea id="seo_ai_context" name="seo[ai_context]" rows="5" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('seo.ai_context', $seo?->ai_context) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('seo.ai_context')" />
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <x-input-label for="seo_search_intent" :value="__('Search intent')" variant="mom" />
                        <x-text-input id="seo_search_intent" name="seo[search_intent]" type="text" class="mt-2 block w-full" :value="old('seo.search_intent', $seo?->search_intent)" placeholder="informational, commercial, …" variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('seo.search_intent')" />
                    </div>
                    <div>
                        <x-input-label for="ai_keywords_lines" :value="__('AI / answer keywords')" variant="mom" />
                        <textarea id="ai_keywords_lines" name="ai_keywords_lines" rows="4" placeholder="{{ __('One phrase per line') }}" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('ai_keywords_lines', $arrayLines($service->ai_keywords)) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('ai_keywords')" />
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="tab === 'faq'" x-cloak class="space-y-8">
        <section class="mom-card p-6" x-data="{ faqs: @js($faqSeed) }">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="mom-section-title">{{ __('FAQs') }}</h3>
                    <p class="mom-subtext mt-2 max-w-3xl">{{ __('Service-level FAQs for fallback pages and schema. When a detail page is linked, empty page FAQs can inherit these.') }}</p>
                </div>
                <button type="button" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)]" @click="faqs.push({ question: '', answer: '' })">{{ __('Add FAQ') }}</button>
            </div>
            <div class="mt-6 space-y-4">
                <template x-for="(faq, index) in faqs" :key="index">
                    <div class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]" x-text="'{{ __('FAQ') }} ' + (index + 1)"></span>
                            <button type="button" class="text-xs font-semibold text-[var(--danger)] hover:underline" @click="faqs.splice(index, 1)" x-show="faqs.length > 1">{{ __('Remove') }}</button>
                        </div>
                        <div class="grid gap-4">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Question') }}</label>
                                <input type="text" class="mt-1 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" :name="`faqs[${index}][question]`" x-model="faq.question" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Answer') }}</label>
                                <textarea rows="3" class="mt-1 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" :name="`faqs[${index}][answer]`" x-model="faq.answer"></textarea>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <x-input-error class="mt-4" :messages="$errors->get('faqs')" />
        </section>
    </div>

    <div x-show="tab === 'schema'" x-cloak class="space-y-8">
        <section class="mom-card p-6">
            <h3 class="mom-section-title mb-2">{{ __('Structured data (Schema.org)') }}</h3>
            <p class="mom-subtext mb-6 max-w-3xl">{{ __('JSON-LD stored on the service record. Syncs to the linked page when page schema is empty.') }}</p>
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="schema_type" :value="__('Schema type')" variant="mom" />
                    <x-text-input id="schema_type" name="schema_type" type="text" class="mt-2 block w-full" :value="old('schema_type', $schema?->schema_type)" placeholder="Service, MedicalProcedure, …" variant="mom" />
                    <x-input-error class="mt-2" :messages="$errors->get('schema_type')" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="schema_json" :value="__('Schema JSON')" variant="mom" />
                    <textarea id="schema_json" name="schema_json" rows="8" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-xs text-[var(--text-primary)] shadow-mom-inner" placeholder="{ &quot;@type&quot;: &quot;Service&quot;, ... }">{{ $schemaJsonDisplay }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('schema_json')" />
                </div>
            </div>
        </section>
    </div>

    <div x-show="tab === 'related'" x-cloak class="space-y-8">
        @include('operations.services.partials.tab-related', [
            'service' => $service,
            'serviceCatalog' => $serviceCatalog ?? collect(),
            'selectedRelatedCodes' => $selectedRelatedCodes ?? [],
            'linkedDetailPage' => $linkedDetailPage ?? null,
            'suggestedDetailPageSlug' => $suggestedDetailPageSlug ?? null,
        ])
    </div>

    <div x-show="tab === 'geo'" x-cloak class="space-y-8">
        @if ($catalogKind !== 'service')
            @include('operations.catalog.partials.geo-catalog', [
                'service' => $service,
                'catalogKind' => $catalogKind,
                'parentService' => $parentService ?? null,
                'pinCodes' => $pinCodes ?? collect(),
                'selectedPinIds' => $selectedPinIds ?? [],
            ])
        @else
            @include('operations.partials.pincode-checklist', [
                'pinCodes' => $pinCodes,
                'selectedPinIds' => $selectedPinIds,
                'title' => __('GEO — serviceable pincodes'),
                'description' => __('Primary category pincodes are inherited. Add or remove pincodes here to override for this service only.'),
            ])
        @endif
    </div>

    <div x-show="tab === 'publishing'" x-cloak class="space-y-8">
        @if ($catalogKind !== 'service')
            @include('operations.catalog.partials.publishing-catalog', [
                'service' => $service,
                'catalogKind' => $catalogKind,
                'detailPages' => $detailPages ?? collect(),
            ])
        @else
        <section class="mom-card p-6">
            <h3 class="mom-section-title mb-4">{{ __('Publishing') }}</h3>
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
                    @php $serviceTokenHint = '{{service:'.($service->service_code ?: 'code').'}}'; @endphp
                    <p class="mom-subtext mt-1">{{ __('Public URL /services/:code renders the linked Site Architect page.', ['code' => $service->service_code ?: 'CODE']) }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('detail_page_id')" />
                </div>
                <div class="flex flex-col gap-4 pt-4 md:col-span-2">
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
        @endif
    </div>

    @if ($mode === 'edit')
        <div x-show="tab === 'sub_services'" x-cloak class="space-y-8">
            @if ($catalogKind === 'category')
                @include('operations.catalog.partials.tab-category-services', ['subServices' => $subServices ?? collect()])
            @elseif ($catalogKind === 'service')
                @include('operations.services.partials.tab-sub-services', [
                    'service' => $service,
                    'subServices' => $subServices ?? collect(),
                ])
            @else
                <section class="mom-card p-6">
                    <h3 class="mom-section-title mb-2">{{ __('Sub-services') }}</h3>
                    <p class="mom-subtext">{{ __('Nested sub-services are managed on the parent service.') }}</p>
                </section>
            @endif
        </div>

        <div x-show="tab === 'reviews'" x-cloak class="space-y-8">
            @if ($catalogKind === 'service')
                @include('operations.services.partials.tab-reviews', ['serviceReviews' => $serviceReviews ?? collect()])
            @else
                <section class="mom-card p-6">
                    <h3 class="mom-section-title mb-2">{{ __('Reviews') }}</h3>
                    <p class="mom-subtext">{{ __('Reviews are collected on parent services. Open the parent service Reviews tab to moderate.') }}</p>
                </section>
            @endif
        </div>
    @endif

    @isset($managedModule)
        <div x-show="tab === 'custom'" x-cloak class="space-y-8">
            <x-dynamic-fields.unified-table :module="$managedModule" :values="$customFieldValues ?? new stdClass()" />
        </div>
    @endisset
</div>
