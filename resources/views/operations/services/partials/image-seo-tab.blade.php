@php
    $featuredMeta = is_array($service->featured_image_meta) ? $service->featured_image_meta : [];
    $galleryMeta = is_array($service->gallery_meta) ? $service->gallery_meta : [];
    $suggestions = app(\App\Services\Operations\ServiceImageSeoService::class)->suggestFeatured($service);
    $imageScore = (int) ($service->seo?->image_seo_score ?? app(\App\Services\Operations\ServiceImageSeoService::class)->score($service));
@endphp

<section class="mom-card p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="mom-section-title">{{ __('Image SEO') }}</h3>
        <span class="rounded-full bg-[rgba(197,160,89,0.12)] px-3 py-1 text-sm font-semibold text-mom-gold">{{ __('Image score') }}: {{ $imageScore }}/100</span>
    </div>
    <p class="mom-subtext mt-2 text-sm">{{ __('Alt text is required for accessibility and SEO. Use suggestions or AI recommend on save.') }}</p>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div>
            <x-input-label for="featured_image_alt" :value="__('Featured alt text')" variant="mom" />
            <x-text-input id="featured_image_alt" name="featured_image_meta[alt]" type="text" class="mt-2 block w-full"
                :value="old('featured_image_meta.alt', $featuredMeta['alt'] ?? $service->image_alt ?? $suggestions['alt'])" variant="mom" />
            <x-input-error class="mt-2" :messages="$errors->get('featured_image_meta.alt')" />
        </div>
        <div>
            <x-input-label for="featured_image_title" :value="__('Image title')" variant="mom" />
            <x-text-input id="featured_image_title" name="featured_image_meta[title]" type="text" class="mt-2 block w-full"
                :value="old('featured_image_meta.title', $featuredMeta['title'] ?? $suggestions['title'])" variant="mom" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="featured_image_caption" :value="__('Caption')" variant="mom" />
            <x-text-input id="featured_image_caption" name="featured_image_meta[caption]" type="text" class="mt-2 block w-full"
                :value="old('featured_image_meta.caption', $featuredMeta['caption'] ?? $suggestions['caption'])" variant="mom" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="featured_image_description" :value="__('Description')" variant="mom" />
            <textarea id="featured_image_description" name="featured_image_meta[description]" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('featured_image_meta.description', $featuredMeta['description'] ?? $suggestions['description']) }}</textarea>
        </div>
    </div>

    @php $galleryItems = is_array($service->gallery) ? array_values(array_filter($service->gallery)) : []; @endphp
    @if ($galleryItems !== [])
        <div class="mt-8 border-t border-[color:var(--border-tabstrip-divider)] pt-8">
            <h4 class="text-sm font-semibold text-mom-gold">{{ __('Gallery image SEO') }}</h4>
            <div class="mt-4 space-y-6">
                @foreach ($galleryItems as $index => $path)
                    @php $meta = $galleryMeta[$path] ?? $galleryMeta[basename($path)] ?? []; @endphp
                    <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] p-4">
                        <p class="mb-3 font-mono text-xs text-[var(--text-muted)]">{{ $path }}</p>
                        <input type="hidden" name="gallery_meta_paths[]" value="{{ $path }}" />
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase text-[var(--text-muted)]">{{ __('Alt') }}</label>
                                <input type="text" name="gallery_meta[{{ $path }}][alt]" class="mom-input mt-1 w-full text-sm"
                                    value="{{ old('gallery_meta.'.$path.'.alt', $meta['alt'] ?? '') }}" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-[var(--text-muted)]">{{ __('Title') }}</label>
                                <input type="text" name="gallery_meta[{{ $path }}][title]" class="mom-input mt-1 w-full text-sm"
                                    value="{{ old('gallery_meta.'.$path.'.title', $meta['title'] ?? '') }}" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
