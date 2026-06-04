@php
    /** @var \App\Models\Service $service */
    $selectedRelatedCodes = $selectedRelatedCodes ?? [];
    $serviceCatalog = $serviceCatalog ?? collect();
    $tokenPreview = collect($selectedRelatedCodes)
        ->map(static fn (string $code): string => '{{service:'.$code.'}}')
        ->implode("\n");
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-2">{{ __('Related services') }}</h3>
    <p class="mom-subtext mb-6 max-w-3xl">
        {{ __('Pick other catalog services to show in the “Related services” carousel. Tokens are written to the linked detail page (or auto slug page) when you check Apply on save.') }}
    </p>

    @if (! $service->exists)
        <p class="mom-body-text text-[var(--text-muted)]">{{ __('Save the service once, then return here to link related offerings.') }}</p>
    @else
        <div x-data="{ q: '' }" class="space-y-4">
            <input
                type="search"
                x-model="q"
                class="block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner"
                placeholder="{{ __('Filter by title or code…') }}"
                autocomplete="off"
            />
            <div class="custom-scrollbar max-h-64 overflow-y-auto rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(0,0,0,0.12)] p-3">
                @forelse ($serviceCatalog as $row)
                    @php $blob = strtolower($row->title.' '.$row->service_code); @endphp
                    <label
                        class="flex cursor-pointer gap-3 rounded px-2 py-1.5 hover:bg-[var(--bg-hover)]"
                        x-show='q.trim() === "" || {{ json_encode($blob) }}.toLowerCase().includes(q.toLowerCase())'
                    >
                        <input
                            type="checkbox"
                            name="related_service_codes[]"
                            value="{{ $row->service_code }}"
                            class="mt-1 rounded border-[rgba(255,255,255,0.15)]"
                            @checked(in_array($row->service_code, $selectedRelatedCodes, true))
                        />
                        <span class="text-sm text-[var(--text-secondary)]">
                            <span class="text-[var(--text-primary)]">{{ $row->title }}</span>
                            <span class="font-mono text-xs text-[var(--text-muted)]">({{ $row->service_code }})</span>
                        </span>
                    </label>
                @empty
                    <p class="mom-subtext text-sm">{{ __('No other services in the catalog yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="mt-6">
            <x-input-label :value="__('Token preview')" variant="mom" />
            <pre class="mt-2 overflow-x-auto rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(0,0,0,0.2)] p-3 font-mono text-xs text-[var(--text-secondary)]">{{ $tokenPreview !== '' ? $tokenPreview : __('— select services above —') }}</pre>
        </div>

        <label class="mt-6 flex cursor-pointer items-start gap-3">
            <input type="hidden" name="apply_related_to_page" value="0" />
            <input type="checkbox" name="apply_related_to_page" value="1" class="mt-1 rounded border-[rgba(255,255,255,0.15)]" @checked(old('apply_related_to_page')) />
            <span class="text-sm text-[var(--text-secondary)]">
                {{ __('Apply tokens to linked detail page on save') }}
                @if (isset($linkedDetailPage) && $linkedDetailPage)
                    <span class="block text-xs text-[var(--text-muted)]">{{ __('Page: :title (:slug)', ['title' => $linkedDetailPage->title, 'slug' => $linkedDetailPage->slug]) }}</span>
                @else
                    <span class="block text-xs text-[var(--text-muted)]">{{ __('Uses page slug :slug when it exists.', ['slug' => $suggestedDetailPageSlug ?? 'service-{code}']) }}</span>
                @endif
            </span>
        </label>
    @endif
</section>
