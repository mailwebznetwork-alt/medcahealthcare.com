<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('SEO') }}</h2>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Global site-wide SEO, technical files, and AI discovery. Page, service, and pincode SEO are managed elsewhere.') }}</p>

    <div class="mom-card mt-6 border border-[rgba(255,255,255,0.06)] p-4">
        <p class="mom-micro">{{ __('Public endpoints') }}</p>
        <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 text-[var(--text-secondary)]">
            <li><a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/robots.txt') }}</a></li>
            <li><a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/sitemap.xml') }}</a> — {{ __('404 when sitemap is disabled') }}</li>
            <li><a href="{{ url('/llm.txt') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/llm.txt') }}</a></li>
            <li><a href="{{ url('/ai-discovery') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/ai-discovery') }}</a> — {{ __('404 when AI discovery is disabled') }}</li>
        </ul>
    </div>

    <div class="mt-8 space-y-8">
        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Global entity, NAP, links & JSON-LD') }}</h3>
            <form method="post" action="{{ route('growth-center.seo.entity.store') }}" class="mt-4 space-y-6">
                @csrf

                <div>
                    <p class="mom-micro mb-2 text-mom-gold">{{ __('META & SOCIAL PREVIEW') }}</p>
                    <div class="space-y-3">
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ __('Organization name') }}</span>
                            <input type="text" name="organization_name" value="{{ old('organization_name', $seoEntity?->organization_name) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                        </label>
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ __('SEO description (meta)') }}</span>
                            <textarea name="meta_description" rows="4" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">{{ old('meta_description', $seoEntity?->meta_description) }}</textarea>
                        </label>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label class="block">
                                <span class="mom-micro mb-1 block">{{ __('Phone (E.164)') }}</span>
                                <input type="text" name="phone_e164" value="{{ old('phone_e164', $businessProfile?->phone_e164 ?? $businessProfile?->phone) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            </label>
                            <label class="block">
                                <span class="mom-micro mb-1 block">{{ __('Country code') }}</span>
                                <input type="text" name="country_code" value="{{ old('country_code', $businessProfile?->country_code) }}" placeholder="IN" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            </label>
                        </div>
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ __('Global meta title (fallback)') }}</span>
                            <input type="text" name="meta_title" value="{{ old('meta_title', $seoEntity?->meta_title) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                        </label>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label class="block">
                                <span class="mom-micro mb-1 block">{{ __('Logo URL') }}</span>
                                <input type="text" name="logo" value="{{ old('logo', $seoEntity?->logo) }}" placeholder="https://..." class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            </label>
                            <label class="block">
                                <span class="mom-micro mb-1 block">{{ __('Open Graph image URL') }}</span>
                                <input type="text" name="og_image_url" value="{{ old('og_image_url', $seoEntity?->og_image_url) }}" placeholder="https://..." class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="mom-micro mb-2 text-mom-gold">{{ __('NAP (ADDRESS)') }}</p>
                    <div class="space-y-3">
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ __('Street address') }}</span>
                            <input type="text" name="street_address" value="{{ old('street_address', $businessProfile?->street_address) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                        </label>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <label class="block">
                                <span class="mom-micro mb-1 block">{{ __('City') }}</span>
                                <input type="text" name="city" value="{{ old('city', $businessProfile?->city) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            </label>
                            <label class="block">
                                <span class="mom-micro mb-1 block">{{ __('Region') }}</span>
                                <input type="text" name="region" value="{{ old('region', $businessProfile?->region) }}" placeholder="KA" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            </label>
                            <label class="block">
                                <span class="mom-micro mb-1 block">{{ __('Postal code') }}</span>
                                <input type="text" name="postal_code" value="{{ old('postal_code', $businessProfile?->postal_code) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="mom-micro mb-2 text-mom-gold">{{ __('ENTITY LINKS (SAME AS)') }}</p>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('same_as JSON array') }}</span>
                        <textarea name="same_as_json" rows="5" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]">{{ old('same_as_json', json_encode($seoEntity?->same_as ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        <span class="mom-subtext mt-1 block">{{ __('Example: ["https://maps.google.com/...", "https://www.instagram.com/..."]') }}</span>
                    </label>
                </div>

                <div>
                    <p class="mom-micro mb-2 text-mom-gold">{{ __('CUSTOM JSON-LD (OPTIONAL)') }}</p>
                    <p class="mom-subtext mb-2">{{ __('Array of schema.org objects, or a single object — appended after the main organization graph on public pages.') }}</p>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('json_ld_schema (raw JSON)') }}</span>
                        <textarea name="custom_json_ld_raw" rows="8" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]">{{ old('custom_json_ld_raw', $seoEntity?->custom_json_ld ? json_encode($seoEntity->custom_json_ld, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                    </label>
                </div>

                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save global SEO entity') }}</button>
            </form>
        </article>

        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('GLOBAL TECHNICAL & PUBLIC FILES') }}</h3>
            <form method="post" action="{{ route('growth-center.seo.technical.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="sitemap_enabled" value="0">
                    <input type="checkbox" name="sitemap_enabled" value="1" @checked((bool) old('sitemap_enabled', $seoTechnical?->sitemap_enabled ?? true)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Public sitemap enabled') }}</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="indexable" value="0">
                    <input type="checkbox" name="indexable" value="1" @checked((bool) old('indexable', $seoTechnical?->indexable ?? true)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Site indexable (robots meta)') }}</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="ai_discovery_enabled" value="0">
                    <input type="checkbox" name="ai_discovery_enabled" value="1" @checked((bool) old('ai_discovery_enabled', $seoTechnical?->ai_discovery_enabled ?? true)) class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Public /ai-discovery enabled') }}</span>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Canonical URL (optional override)') }}</span>
                    <input type="url" name="canonical_url" value="{{ old('canonical_url', $seoTechnical?->canonical_url) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Google site verification content') }}</span>
                    <input type="text" name="google_site_verification" value="{{ old('google_site_verification', $seoTechnical?->google_site_verification) }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('robots.txt (raw)') }}</span>
                    <textarea name="robots_txt" rows="8" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]">{{ old('robots_txt', $seoTechnical?->robots_txt) }}</textarea>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('llm.txt (raw, optional)') }}</span>
                    <textarea name="llm_txt" rows="8" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]" placeholder="{{ __('Leave blank to use default bot allow-list.') }}">{{ old('llm_txt', $seoTechnical?->llm_txt) }}</textarea>
                </label>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save technical settings') }}</button>
            </form>
        </article>
    </div>
</section>
