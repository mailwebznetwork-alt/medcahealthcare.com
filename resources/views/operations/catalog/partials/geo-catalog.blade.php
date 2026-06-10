@php
    $catalogKind = $catalogKind ?? 'category';
    $seo = $service->seo;
    $arrayLines = static function (mixed $items): string {
        $items = \App\Services\Import\ImportSupport::normalizeLineArray($items);

        return $items === [] ? '' : implode("\n", $items);
    };
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-4">{{ __('GEO — location signals') }}</h3>
    @if ($catalogKind === 'sub_service' && isset($parentService))
        <p class="mom-subtext mb-4 max-w-3xl">{{ __('Sub-services inherit serviceable areas from the parent service (:title). Edit pincodes on the parent service GEO tab.', ['title' => $parentService->title]) }}</p>
        <ul class="space-y-2 text-sm text-[var(--text-secondary)]">
            @forelse ($parentService->pincodes ?? [] as $pc)
                <li><span class="font-mono text-[var(--text-primary)]">{{ $pc->pincode }}</span> — {{ $pc->area_name }}, {{ $pc->city }}</li>
            @empty
                <li class="mom-subtext">{{ __('No pincodes on parent service yet.') }}</li>
            @endforelse
        </ul>
    @else
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
