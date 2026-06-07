@php
    $landmarks = old('landmarks', $pinCode->relationLoaded('landmarks') ? $pinCode->landmarks->map(fn ($r) => ['name' => $r->name, 'landmark_type' => $r->landmark_type])->all() : [['name' => '']]);
    $hospitals = old('hospitals', $pinCode->relationLoaded('hospitals') ? $pinCode->hospitals->map(fn ($r) => ['name' => $r->name, 'specialty' => $r->specialty, 'address' => $r->address])->all() : [['name' => '']]);
    $faqs = old('location_faqs', $pinCode->relationLoaded('locationFaqs') ? $pinCode->locationFaqs->map(fn ($r) => ['question' => $r->question, 'answer' => $r->answer])->all() : [['question' => '', 'answer' => '']]);
    $nearby = old('nearby_areas', $pinCode->relationLoaded('nearbyAreas') ? $pinCode->nearbyAreas->map(fn ($r) => ['area_name' => $r->area_name])->all() : [['area_name' => '']]);
@endphp

<div class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('GEO enrichment dataset') }}</h2>
    <p class="mom-subtext mt-2 max-w-2xl">{{ __('Local landmarks, hospitals, FAQs, and nearby areas power unique location pages.') }}</p>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="state" :value="__('State')" variant="mom" />
            <x-text-input id="state" name="state" type="text" class="mt-2 block w-full" :value="old('state', $pinCode->state)" variant="mom" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="coverage_text" :value="__('Coverage text')" variant="mom" />
            <textarea id="coverage_text" name="coverage_text" rows="3" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('coverage_text', $pinCode->coverage_text) }}</textarea>
        </div>
        <div class="md:col-span-2">
            <x-input-label for="emergency_coverage_text" :value="__('Emergency coverage text')" variant="mom" />
            <textarea id="emergency_coverage_text" name="emergency_coverage_text" rows="3" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('emergency_coverage_text', $pinCode->emergency_coverage_text) }}</textarea>
        </div>
    </div>

    <div class="mt-8 space-y-6">
        <div>
            <h3 class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Nearby areas') }}</h3>
            @foreach ($nearby as $i => $row)
                <div class="mt-2">
                    <input type="text" name="nearby_areas[{{ $i }}][area_name]" value="{{ $row['area_name'] ?? '' }}" placeholder="{{ __('Area name') }}" class="block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                </div>
            @endforeach
            <div class="mt-2">
                <input type="text" name="nearby_areas[{{ count($nearby) }}][area_name]" value="" placeholder="{{ __('Add another area') }}" class="block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Landmarks') }}</h3>
            @foreach ($landmarks as $i => $row)
                <div class="mt-2 grid gap-2 md:grid-cols-2">
                    <input type="text" name="landmarks[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="{{ __('Landmark name') }}" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    <input type="text" name="landmarks[{{ $i }}][landmark_type]" value="{{ $row['landmark_type'] ?? '' }}" placeholder="{{ __('Type') }}" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                </div>
            @endforeach
        </div>

        <div>
            <h3 class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Hospitals') }}</h3>
            @foreach ($hospitals as $i => $row)
                <div class="mt-2 space-y-2 rounded-lg border border-[rgba(255,255,255,0.06)] p-3">
                    <input type="text" name="hospitals[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="{{ __('Hospital name') }}" class="block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    <input type="text" name="hospitals[{{ $i }}][specialty]" value="{{ $row['specialty'] ?? '' }}" placeholder="{{ __('Specialty') }}" class="block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    <input type="text" name="hospitals[{{ $i }}][address]" value="{{ $row['address'] ?? '' }}" placeholder="{{ __('Address') }}" class="block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                </div>
            @endforeach
        </div>

        <div>
            <h3 class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Area-specific FAQs') }}</h3>
            @foreach ($faqs as $i => $row)
                <div class="mt-2 space-y-2 rounded-lg border border-[rgba(255,255,255,0.06)] p-3">
                    <input type="text" name="location_faqs[{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="{{ __('Question') }}" class="block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    <textarea name="location_faqs[{{ $i }}][answer]" rows="2" placeholder="{{ __('Answer') }}" class="block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">{{ $row['answer'] ?? '' }}</textarea>
                </div>
            @endforeach
        </div>
    </div>
</div>
