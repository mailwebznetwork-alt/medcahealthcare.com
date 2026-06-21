@php
    $catalogKind = $catalogKind ?? 'category';
    $seo = $service->seo;
    $arrayLines = static function (mixed $items): string {
        $items = \App\Services\Import\ImportSupport::normalizeLineArray($items);

        return $items === [] ? '' : implode("\n", $items);
    };
@endphp

@if ($catalogKind === 'category')
    @include('operations.partials.country-checklist', [
        'pinCodes' => $pinCodes ?? collect(),
        'selectedPinIds' => $selectedPinIds ?? [],
        'title' => __('GEO — category pincodes (master)'),
        'description' => __('Master coverage for this category. Applies to all services whose primary category is this one. You can still override on individual services.'),
    ])
@elseif ($catalogKind === 'sub_service' && isset($parentService))
    @include('operations.partials.country-checklist', [
        'pinCodes' => $pinCodes ?? collect(),
        'selectedPinIds' => $selectedPinIds ?? [],
        'title' => __('GEO — sub-service coverage'),
        'description' => __('Inherited from parent service :title. Uncheck a country to exclude it from this sub-service only.', ['title' => $parentService->title]),
    ])
@endif

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-4">{{ __('GEO — location signals') }}</h3>
    @if ($catalogKind === 'category')
        <p class="mom-subtext mb-4 max-w-3xl">{{ __('GEO entities and signals for category discovery pages and local relevance.') }}</p>
    @endif
    <div class="mt-6 grid gap-6">
        <div>
            <x-input-label for="seo_geo_entities_lines" :value="__('GEO entities')" variant="mom" />
            <textarea id="seo_geo_entities_lines" name="seo[geo_entities_lines]" rows="4" placeholder="{{ __('One location entity per line') }}" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 font-mono text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('seo.geo_entities_lines', $arrayLines($seo?->geo_entities)) }}</textarea>
        </div>
        @if ($catalogKind === 'category')
            <div>
                <x-input-label for="seo_aeo_question" :value="__('AEO question (category)')" variant="mom" />
                <x-text-input id="seo_aeo_question" name="seo[aeo_question]" type="text" class="mt-2 block w-full" :value="old('seo.aeo_question', $seo?->aeo_question)" variant="mom" />
            </div>
            <div>
                <x-input-label for="seo_aeo_answer" :value="__('AEO answer (category)')" variant="mom" />
                <textarea id="seo_aeo_answer" name="seo[aeo_answer]" rows="4" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('seo.aeo_answer', $seo?->aeo_answer) }}</textarea>
            </div>
        @endif
    </div>
</section>
