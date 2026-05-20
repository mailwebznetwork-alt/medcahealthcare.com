@php
    /** @var \App\Models\Service $service */
    /** @var \Illuminate\Support\Collection<int, \App\Models\PinCode> $pinCodes */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Page> $detailPages */
    $mode = $mode ?? 'create';

    $detailPages = isset($detailPages) ? $detailPages : collect();

    $selectedPinIds = array_map(static fn ($v) => (int) $v, old('pincodes', $service->exists ? $service->pincodes->pluck('id')->all() : []));

    $detailPageId = (int) old('detail_page_id', $service->detail_page_id);
    $linkedDetailPage = $detailPageId > 0
        ? $detailPages->firstWhere('id', $detailPageId)
        : ($patternDetailPage ?? null);
@endphp

<div class="space-y-8">
    @if ($mode === 'edit')
        <section class="mom-card border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.04)] p-6">
            <h3 class="mom-section-title mb-2">{{ __('Public page — blocks, SEO & schema') }}</h3>
            <p class="mom-body-text mb-4 max-w-3xl">
                {{ __('Content layout, meta tags, FAQs, JSON-LD, and OG image live on the linked Site Architect page. This form keeps service identity, publish rules, and GEO pincodes only.') }}
            </p>
            <div class="flex flex-wrap gap-3">
                @if ($linkedDetailPage)
                    <a href="{{ route('operations.services.detail-page.edit', $service) }}" class="mom-cta-primary">{{ __('Edit blocks & SEO') }}</a>
                    <span class="mom-subtext self-center">{{ $linkedDetailPage->title }} · {{ $linkedDetailPage->slug }}</span>
                @else
                    <form action="{{ route('operations.services.detail-page.store', $service) }}" method="post" class="inline">
                        @csrf
                        <x-primary-button variant="mom" type="submit">{{ __('Create detail page & open editor') }}</x-primary-button>
                    </form>
                @endif
                <a href="{{ route('site-architect.block-factory.index') }}" class="mom-cta-ghost">{{ __('Block Factory') }}</a>
                <a href="{{ route('site-architect.media.index') }}" class="mom-cta-ghost">{{ __('Media library') }}</a>
            </div>
        </section>
    @endif

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
        </div>
    </section>

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
                    {{ __('Public URL /services/:code renders the linked Site Architect page. Use :token in blocks for related offerings.', ['code' => $service->service_code ?: 'CODE', 'token' => $serviceTokenHint]) }}
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
