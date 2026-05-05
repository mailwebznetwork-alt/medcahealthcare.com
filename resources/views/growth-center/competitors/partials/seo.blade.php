<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('SEO') }}</h2>
    <div class="mt-2 flex flex-wrap gap-2">
        <a href="{{ route('growth-center.competitors.index', ['tab' => 'seo', 'seo_mode' => 'entity']) }}" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">{{ __('Entity') }}</a>
        <a href="{{ route('growth-center.competitors.index', ['tab' => 'seo', 'seo_mode' => 'technical']) }}" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">{{ __('Technical') }}</a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Entity') }}</h3>
            <form method="post" action="{{ route('growth-center.seo.entity.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="text" name="organization_name" value="{{ old('organization_name', $seoEntity?->organization_name) }}" placeholder="{{ __('Organization name') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                <input type="url" name="website" value="{{ old('website', $seoEntity?->website) }}" placeholder="{{ __('Website URL') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <input type="text" name="default_language" value="{{ old('default_language', $seoEntity?->default_language ?? 'en') }}" placeholder="{{ __('Default language') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <input type="text" name="knowledge_graph_id" value="{{ old('knowledge_graph_id', $seoEntity?->knowledge_graph_id) }}" placeholder="{{ __('Knowledge Graph ID') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Entity') }}</button>
            </form>
        </article>

        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Technical') }}</h3>
            <form method="post" action="{{ route('growth-center.seo.technical.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="robots_enabled" value="0">
                    <input type="checkbox" name="robots_enabled" value="1" @checked((bool) old('robots_enabled', $seoTechnical?->robots_enabled ?? true)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Robots.txt Enabled') }}</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="sitemap_enabled" value="0">
                    <input type="checkbox" name="sitemap_enabled" value="1" @checked((bool) old('sitemap_enabled', $seoTechnical?->sitemap_enabled ?? true)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Sitemap Enabled') }}</span>
                </label>
                <select name="canonical_mode" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                    <option value="self" @selected(old('canonical_mode', $seoTechnical?->canonical_mode) === 'self')>self</option>
                    <option value="domain" @selected(old('canonical_mode', $seoTechnical?->canonical_mode) === 'domain')>domain</option>
                </select>
                <textarea name="robots_content" rows="3" placeholder="{{ __('robots.txt content') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">{{ old('robots_content', $seoTechnical?->robots_content) }}</textarea>
                <input type="url" name="sitemap_url" value="{{ old('sitemap_url', $seoTechnical?->sitemap_url) }}" placeholder="{{ __('Sitemap URL') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Technical') }}</button>
            </form>
        </article>
    </div>
</section>
