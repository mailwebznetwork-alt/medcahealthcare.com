<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('SEO') }}</h2>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Global SEO, coverage, AEO signals, technical files, and AI discovery — one place. Page and Operations SEO are managed elsewhere.') }}</p>

    <div class="mom-card mt-6 border border-[rgba(255,255,255,0.06)] p-4">
        <p class="mom-micro">{{ __('Public endpoints') }}</p>
        <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 text-[var(--text-secondary)]">
            <li><a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/robots.txt') }}</a></li>
            <li><a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/sitemap.xml') }}</a> — {{ __('sitemap index; 404 when sitemap is disabled') }}</li>
            <li><a href="{{ url('/sitemap-pages.xml') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/sitemap-pages.xml') }}</a></li>
            <li><a href="{{ url('/sitemap-blogs.xml') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/sitemap-blogs.xml') }}</a></li>
            <li><a href="{{ url('/sitemap-services.xml') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/sitemap-services.xml') }}</a></li>
            <li><a href="{{ url('/sitemap-images.xml') }}" target="_blank" rel="noopener noreferrer" class="text-[var(--success)] underline">{{ url('/sitemap-images.xml') }}</a></li>
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
                    <p class="mom-micro mb-2 text-mom-gold">{{ __('GOOGLE BUSINESS PROFILE (MANUAL)') }}</p>
                    <p class="mom-subtext mb-2">{{ __('Paste values from your Google Business Profile (no API). Profile URL is merged into sameAs for JSON-LD when not already listed.') }}</p>
                    <div class="space-y-3">
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ __('Google Place ID') }}</span>
                            <input type="text" name="google_place_id" value="{{ old('google_place_id', $seoEntity?->google_place_id) }}" placeholder="ChIJ..." class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                        </label>
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ __('Google Business Profile URL') }}</span>
                            <input type="url" name="google_business_profile_url" value="{{ old('google_business_profile_url', $seoEntity?->google_business_profile_url) }}" placeholder="https://www.google.com/maps?cid=..." class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                        </label>
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ __('Map listing URL (hasMap in JSON-LD)') }}</span>
                            <input type="url" name="has_map_url" value="{{ old('has_map_url', $seoEntity?->has_map_url) }}" placeholder="https://maps.google.com/?q=..." class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                        </label>
                    </div>
                </div>

                <div>
                    <p class="mom-micro mb-2 text-mom-gold">{{ __('ENTITY LINKS (SAME AS)') }}</p>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('same_as JSON array') }}</span>
                        <textarea name="same_as_json" rows="5" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]">{{ old('same_as_json', json_encode($seoEntity?->same_as ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        <span class="mom-subtext mt-1 block">{{ __('Example: ["https://www.instagram.com/...", "https://www.youtube.com/..."]') }}</span>
                    </label>
                </div>

                <div>
                    <p class="mom-micro mb-2 text-mom-gold">{{ __('GLOBAL FAQ (FAQPage JSON-LD)') }}</p>
                    <p class="mom-subtext mb-2">{{ __('JSON array of objects with "question" and "answer" strings (max 40). Shown as FAQPage structured data on public pages.') }}</p>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('faqs_json') }}</span>
                        <textarea name="entity_faqs_json" rows="10" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]">{{ old('entity_faqs_json', $seoEntity?->entity_faqs ? json_encode($seoEntity->entity_faqs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
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

        @include('growth-center.competitors.partials.geo')

        @include('growth-center.competitors.partials.aeo')

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

    {{-- PDF §8 — SEO/AEO/GEO/LLM എഞ്ചിൻ: പൂർണ്ണ മാപ്പ് (മലയാളം) --}}
    <article class="mom-card mt-8 border border-[rgba(197,160,89,0.12)] p-5">
        <h3 class="mom-section-title">{{ __('PDF ഭാഗം 8 — SEO / AEO / GEO / LLM എഞ്ചിൻ (പൂർണ്ണ മാപ്പ്)') }}</h3>
        <p class="mom-body-text mt-3 text-[var(--text-secondary)]">
            {{ __('ഗ്ലോബൽ SEO, ടെക്‌നിക്കൽ ഫയലുകൾ, ജിയോ കവർേജ്, AEO സിഗ്നലുകൾ, LLM നയങ്ങൾ എന്നിവയുടെ പ്രധാന നിയന്ത്രണങ്ങൾ ഒരേ Growth Center ടാബിൽ ലഭ്യമാണ്: «SEO» (URL: ') }}
            <a href="{{ route('growth-center.competitors.index', ['tab' => 'seo']) }}" class="text-[var(--success)] underline">{{ url('/growth-center/competitors') }}?tab=seo</a>{{ __('). പഴയ ലിങ്കുകൾ ') }}<code class="rounded bg-[rgba(0,0,0,0.25)] px-1 py-0.5 text-xs">?tab=geo</code>{{ __(' അല്ലെങ്കിൽ ') }}<code class="rounded bg-[rgba(0,0,0,0.25)] px-1 py-0.5 text-xs">?tab=aeo</code>{{ __(' ആണെങ്കിൽ സിസ്റ്റം സ്വയം «SEO» ടാബിലേക്ക് തിരിച്ചുവിടും.') }}
        </p>

        <div class="mt-6 space-y-5 text-[var(--text-secondary)]">
            <div>
                <p class="mom-micro text-mom-gold">{{ __('① SEO (ഗ്ലോബൽ ഓൺ-സൈറ്റ്)') }}</p>
                <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 pl-1">
                    <li>{{ __('ഓർഗനൈസേഷൻ, NAP, sameAs, FAQ JSON-LD, കസ്റ്റം സ്കീമ — ഈ പേജിലെ «ഗ്ലോബൽ എൻ്റിറ്റി» ഫോം.') }}</li>
                    <li>{{ __('robots, സൈറ്റ്മാപ് ടോഗിൾ, ഇൻഡക്സിങ്, canonical, Google വെരിഫിക്കേഷൻ — «ഗ്ലോബൽ ടെക്‌നിക്കൽ» ഫോം.') }}</li>
                    <li>{{ __('പബ്ലിക് സൈറ്റ്മാപ് ഇൻഡക്സ്, ബ്ലോഗ്/സർവീസ്/ചിത്ര സൈറ്റ്മാപ്പുകൾ — മുകളിലെ പബ്ലിക് ലിങ്കുകൾ വഴി പരിശോധിക്കാം.') }}</li>
                </ul>
            </div>
            <div>
                <p class="mom-micro text-mom-gold">{{ __('② GEO (സ്ഥാനം, പിൻകോഡ്, ലാൻഡിങ്)') }}</p>
                <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 pl-1">
                    <li>{{ __('ലാറ്റിറ്റ്യൂഡ്/ലോങ്ങിറ്റ്യൂഡ്, ആരം കി.മീ., പിൻകോഡ് പട്ടിക, ലാൻഡിങ് പാത്ത് — ഈ ടാബിനുള്ളിൽ താഴെയുള്ള «ലൊക്കേഷൻ ആൻഡ് കവർേജ്» ബ്ലോക്ക്.') }}</li>
                </ul>
            </div>
            <div>
                <p class="mom-micro text-mom-gold">{{ __('③ AEO (AI ആൻസർ എഞ്ചിനുകളിലെ ദൃശ്യപ്രാപ്തി)') }}</p>
                <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 pl-1">
                    <li>{{ __('LLM വിസിബിലിറ്റി / എൻ്റിറ്റി കൺസിസ്റ്റൻസി സ്കോറുകൾ — «AEO — AI വിസിബിലിറ്റി സിഗ്നലുകൾ» ബ്ലോക്ക്.') }}</li>
                    <li>{{ __('യഥാർത്ഥ ബോട്ട് നയം: ') }}<code class="text-xs">/llm.txt</code>{{ __(' ടെക്‌നിക്കൽ സെറ്റിങ്സിൽ എഡിറ്റുചെയ്യുക; ') }}<code class="text-xs">/ai-discovery</code>{{ __(' ടോഗിൾ അവിടെത്തന്നെ.') }}</li>
                </ul>
            </div>
            <div>
                <p class="mom-micro text-mom-gold">{{ __('④ LLM / AI ഡിസ്കവർബിലിറ്റി') }}</p>
                <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 pl-1">
                    <li>{{ __('പബ്ലിക് ') }}<code class="text-xs">llm.txt</code>{{ __(', ') }}<code class="text-xs">ai-discovery</code>{{ __(' JSON — മുകളിലെ എൻഡ്‌പോയിന്റ് ലിസ്റ്റ് + ടെക്‌നിക്കൽ ടോഗിളുകൾ.') }}</li>
                    <li>{{ __('സൈറ്റ് ആർക്കിടെക്ചർ ലെ വേഗം, ലിങ്കുകൾ, AEO സംക്ഷിപ്തം — വേറൊരു ടാബ്: ') }}
                        <a href="{{ route('growth-center.competitors.index', ['tab' => 'ai-pulse']) }}" class="text-[var(--success)] underline">{{ __('AI Pulse') }}</a>
                        {{ __(' (PDF §19.6 ബ്രീഫ്).') }}</li>
                </ul>
            </div>
            <div>
                <p class="mom-micro text-mom-gold">{{ __('⑤ ഇവിടെ ഉൾപ്പെടാത്തത് (വേറെ മൊഡ്യൂളുകൾ)') }}</p>
                <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 pl-1">
                    <li>{{ __('വെബ് അനലിറ്റിക്സ് / അക്വിസിഷൻ ഡാഷ്ബോർഡ് — ') }}
                        <a href="{{ route('growth-center.competitors.index', ['tab' => 'ga4']) }}" class="text-[var(--success)] underline">{{ __('GA4 ടാബ്') }}</a>{{ __('.') }}</li>
                    <li>{{ __('ഓരോ പേജ്/ബ്ലോഗിന്റെ മെറ്റാ, OG, സ്കീമ — ') }}<a href="{{ route('site-architect.pages.index') }}" class="text-[var(--success)] underline">{{ __('Site Architect → Pages / Blogs') }}</a>{{ __('.') }}</li>
                    <li>{{ __('ഓപ്പറേഷൻസ് സേവനങ്ങൾ / ജോബ് പോർട്ടൽ തുടങ്ങിയ മറ്റു SEO — അനുയോജ്യമായ ഹബുകളിൽ.') }}</li>
                </ul>
            </div>
            <div class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.35)] p-4">
                <p class="mom-micro text-[var(--text-muted)]">{{ __('സേവ് API പാതകൾ (പരിശോധന)') }}</p>
                <ul class="mom-body-text mt-2 space-y-1 font-mono text-[11px] text-[var(--text-secondary)]">
                    <li>POST {{ url('/growth-center/seo/entity') }}</li>
                    <li>POST {{ url('/growth-center/seo/technical') }}</li>
                    <li>POST {{ url('/growth-center/aeo') }}</li>
                    <li>POST {{ url('/growth-center/geo/location') }} · POST {{ url('/growth-center/geo/pincode') }}</li>
                </ul>
            </div>
        </div>
    </article>
</section>
