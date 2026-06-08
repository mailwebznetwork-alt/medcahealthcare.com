@php
    $seo = $service->seo;
    $secondaryLines = old('seo.secondary_keywords_lines', is_array($seo?->secondary_keywords) ? implode("\n", $seo->secondary_keywords) : '');
    $entityLines = old('seo.entity_tags_lines', is_array($seo?->entity_tags) ? implode("\n", $seo->entity_tags) : '');
    $geoEntityLines = old('seo.geo_entities_lines', is_array($seo?->geo_entities) ? implode("\n", $seo->geo_entities) : '');
@endphp

<div class="mt-8 grid gap-6 border-t border-[color:var(--border-tabstrip-divider)] pt-8 md:grid-cols-2">
    <div class="md:col-span-2">
        <h4 class="text-sm font-semibold text-mom-gold">{{ __('Technical SEO') }}</h4>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="seo_canonical_url" :value="__('Canonical URL')" variant="mom" />
        <x-text-input id="seo_canonical_url" name="seo[canonical_url]" type="url" class="mt-2 block w-full" :value="old('seo.canonical_url', $seo?->canonical_url ?: $service->publicUrl())" variant="mom" />
    </div>
    <div>
        <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
            <input type="hidden" name="seo[robots_index]" value="0" />
            <input type="checkbox" name="seo[robots_index]" value="1" class="rounded" @checked(old('seo.robots_index', $seo?->robots_index ?? true)) />
            {{ __('Allow search indexing (robots index)') }}
        </label>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="seo_secondary_keywords_lines" :value="__('Secondary keywords (one per line)')" variant="mom" />
        <textarea id="seo_secondary_keywords_lines" name="seo[secondary_keywords_lines]" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ $secondaryLines }}</textarea>
    </div>

    <div class="md:col-span-2">
        <h4 class="text-sm font-semibold text-mom-gold">{{ __('Social sharing') }}</h4>
    </div>
    <div>
        <x-input-label for="seo_og_title" :value="__('Open Graph title')" variant="mom" />
        <x-text-input id="seo_og_title" name="seo[og_title]" type="text" class="mt-2 block w-full" :value="old('seo.og_title', $seo?->og_title)" variant="mom" />
    </div>
    <div>
        <x-input-label for="seo_twitter_card" :value="__('Twitter / X card')" variant="mom" />
        <select id="seo_twitter_card" name="seo[twitter_card]" class="mom-input mt-2 block w-full text-sm">
            @foreach (['summary_large_image' => 'Summary large image', 'summary' => 'Summary', 'player' => 'Player'] as $val => $label)
                <option value="{{ $val }}" @selected(old('seo.twitter_card', $seo?->twitter_card ?: 'summary_large_image') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="seo_og_description" :value="__('Open Graph description')" variant="mom" />
        <textarea id="seo_og_description" name="seo[og_description]" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('seo.og_description', $seo?->og_description) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="seo_og_image" :value="__('Open Graph image URL or storage path')" variant="mom" />
        <x-text-input id="seo_og_image" name="seo[og_image]" type="text" class="mt-2 block w-full" :value="old('seo.og_image', $seo?->og_image ?: $service->featured_image)" variant="mom" />
    </div>

    <div class="md:col-span-2">
        <h4 class="text-sm font-semibold text-mom-gold">{{ __('GEO entities') }}</h4>
    </div>
    <div>
        <x-input-label for="seo_entity_tags_lines" :value="__('Entity tags (one per line)')" variant="mom" />
        <textarea id="seo_entity_tags_lines" name="seo[entity_tags_lines]" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ $entityLines }}</textarea>
    </div>
    <div>
        <x-input-label for="seo_geo_entities_lines" :value="__('Service intent / GEO entities')" variant="mom" />
        <textarea id="seo_geo_entities_lines" name="seo[geo_entities_lines]" rows="3" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ $geoEntityLines }}</textarea>
    </div>
</div>
