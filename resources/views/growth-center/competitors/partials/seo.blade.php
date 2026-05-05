<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('SEO') }}</h2>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Global Entity') }}</h3>
            <form method="post" action="{{ route('growth-center.seo.entity.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="text" name="organization_name" value="{{ old('organization_name', $seoEntity?->organization_name) }}" placeholder="{{ __('Organization name') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                <input type="text" name="logo" value="{{ old('logo', $seoEntity?->logo) }}" placeholder="{{ __('Logo URL or path') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <input type="text" name="same_as[]" value="{{ old('same_as.0', data_get($seoEntity?->same_as, '0')) }}" placeholder="{{ __('SameAs URL 1') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <input type="text" name="same_as[]" value="{{ old('same_as.1', data_get($seoEntity?->same_as, '1')) }}" placeholder="{{ __('SameAs URL 2') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <input type="text" name="meta_title" value="{{ old('meta_title', $seoEntity?->meta_title) }}" placeholder="{{ __('Global meta title') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <textarea name="meta_description" rows="4" placeholder="{{ __('Global meta description') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">{{ old('meta_description', $seoEntity?->meta_description) }}</textarea>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Entity') }}</button>
            </form>
        </article>

        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Global Technical') }}</h3>
            <form method="post" action="{{ route('growth-center.seo.technical.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="sitemap_enabled" value="0">
                    <input type="checkbox" name="sitemap_enabled" value="1" @checked((bool) old('sitemap_enabled', $seoTechnical?->sitemap_enabled ?? true)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Sitemap Enabled') }}</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="indexable" value="0">
                    <input type="checkbox" name="indexable" value="1" @checked((bool) old('indexable', $seoTechnical?->indexable ?? true)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Global Indexable') }}</span>
                </label>
                <input type="url" name="canonical_url" value="{{ old('canonical_url', $seoTechnical?->canonical_url) }}" placeholder="{{ __('Canonical URL') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                <textarea name="robots_txt" rows="6" placeholder="{{ __('robots.txt content') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">{{ old('robots_txt', $seoTechnical?->robots_txt) }}</textarea>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Technical') }}</button>
            </form>
        </article>
    </div>
</section>
