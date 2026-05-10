<div>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(98,195,120,0.28)] bg-[rgba(98,195,120,0.08)] px-4 py-3 text-sm text-[var(--success)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($mode === 'list')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Pages') }}</h2>
            <a
                href="{{ route('site-architect.pages.index') }}?create=1"
                wire:click.prevent="startCreate"
                role="button"
                class="inline-flex cursor-pointer rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.1)] px-4 py-2 text-sm font-medium text-mom-gold no-underline"
            >
                {{ __('Create page') }}
            </a>
        </div>

        <div class="mom-card overflow-x-auto p-0">
            <table class="mom-table w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3">{{ __('Page name') }}</th>
                        <th class="px-4 py-3">{{ __('Slug') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Preview') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr wire:key="page-row-{{ $page->id }}">
                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $page->title }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-[var(--text-muted)]">{{ $page->slug }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $page->id }})"
                                    class="text-xs font-semibold uppercase tracking-wide {{ $page->is_active ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}"
                                >
                                    {{ $page->is_active ? __('Live') : __('Off') }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('site-architect.pages.preview', $page) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-mom-gold hover:underline"
                                >{{ __('Preview') }}</a>
                                @if ($page->is_active)
                                    <span class="text-[var(--text-muted)]">·</span>
                                    <a
                                        href="{{ route('pages.public', ['slug' => $page->slug]) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-mom-gold hover:underline"
                                    >{{ __('Live') }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="startEdit({{ $page->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Edit') }}</button>
                                <button type="button" wire:click="duplicatePage({{ $page->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Duplicate') }}</button>
                                <button
                                    type="button"
                                    wire:click="deletePage({{ $page->id }})"
                                    wire:confirm="{{ __('Delete this page?') }}"
                                    class="text-[var(--danger)] hover:underline"
                                >{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-[var(--text-muted)]">{{ __('No pages yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $pages->links() }}
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? __('Edit page') : __('New page') }}</h2>
            <button type="button" wire:click="cancelForm" class="mom-subtext text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Back to list') }}</button>
        </div>

        <div class="space-y-8">
            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Basic') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Page title') }}</label>
                        <input type="text" wire:model.live="title" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]" />
                        @error('title') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Slug') }}</label>
                        <input type="text" wire:model="slug" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm text-[var(--text-primary)]" />
                        @error('slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="is_active" id="page-active" class="rounded border-[rgba(255,255,255,0.15)]" />
                        <label for="page-active" class="text-sm text-[var(--text-secondary)]">{{ __('Live') }}</label>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Blocks & modules (structure)') }}</h3>
                <p class="mom-subtext mb-4 max-w-2xl">{{ __('Order defines page structure. Blocks hold Blade/HTML; modules resolve via config.') }}</p>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="addBlock" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)]">
                        {{ __('Add block') }}
                    </button>
                    <div class="flex flex-wrap items-center gap-2">
                        <select wire:model.live="module_choice" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            <option value="">{{ __('Insert module…') }}</option>
                            @foreach ($modules as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="appendModule" wire:loading.attr="disabled" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] disabled:opacity-50">{{ __('Add module line') }}</button>
                    </div>
                    @if (count($modules) === 0)
                        <p class="mt-2 text-xs text-[var(--text-muted)]">{{ __('No modules registered in config/modules.php.') }}</p>
                    @else
                        <p class="mt-2 text-xs text-[var(--text-muted)]">{{ __('Pick a module from the list, then click Add module line.') }}</p>
                    @endif
                    @error('module_choice') <span class="mt-2 block text-xs text-[var(--danger)]">{{ $message }}</span> @enderror
                </div>

                <ul class="mt-6 space-y-2">
                    @foreach ($contentParts as $idx => $part)
                        <li wire:key="part-{{ $idx }}-{{ $part['type'] }}-{{ $part['slug'] }}" class="flex flex-wrap items-center justify-between gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] px-3 py-2 font-mono text-xs text-[var(--text-secondary)]">
                            <span>{{ '{{'.$part['type'].':'.$part['slug'].str_repeat('}', 2) }}</span>
                            <span class="flex flex-wrap gap-1">
                                @if ($part['type'] === 'block')
                                    <button type="button" wire:click="editBlockFromPart({{ $idx }})" class="text-mom-gold hover:underline">{{ __('Edit') }}</button>
                                @endif
                                <button type="button" wire:click="movePartUp({{ $idx }})" class="hover:text-[var(--text-primary)]">{{ __('Up') }}</button>
                                <button type="button" wire:click="movePartDown({{ $idx }})" class="hover:text-[var(--text-primary)]">{{ __('Down') }}</button>
                                <button type="button" wire:click="removePart({{ $idx }})" class="text-[var(--danger)] hover:underline">{{ __('Remove') }}</button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('SEO') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Meta title') }}</label>
                        <input type="text" wire:model="meta_title" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Meta description') }}</label>
                        <textarea wire:model="meta_description" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Keywords') }}</label>
                        <input type="text" wire:model="keywords" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    @foreach (['h1' => __('H1'), 'h2' => __('H2'), 'h3' => __('H3'), 'h4' => __('H4'), 'h5' => __('H5'), 'h6' => __('H6')] as $field => $label)
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ $label }}</label>
                            <input type="text" wire:model="{{ $field }}" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Technical & canonical') }}</h3>
                <p class="mom-subtext mb-4 max-w-2xl">{{ __('Override crawl directives for this URL only. Leave robots blank to inherit Growth Center global settings.') }}</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Canonical URL') }}</label>
                        <input type="text" wire:model="canonical_url" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="https://example.com/p/your-page" />
                        @error('canonical_url') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Robots meta') }}</label>
                        <select wire:model="robots_meta" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            <option value="">{{ __('Inherit global (Growth Center)') }}</option>
                            <option value="index, follow">index, follow</option>
                            <option value="noindex, nofollow">noindex, nofollow</option>
                            <option value="noindex, follow">noindex, follow</option>
                            <option value="index, nofollow">index, nofollow</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Social & sharing (Open Graph)') }}</h3>
                <p class="mom-subtext mb-4 max-w-2xl">{{ __('Optional image for this page in link previews. Use a full URL or a path under storage (e.g. after uploading in Media).') }}</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('OG / social image') }}</label>
                        <input type="text" wire:model="og_image" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="https://… or media/…" />
                        @error('og_image') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('OG image alt text') }}</label>
                        <input type="text" wire:model="og_image_alt" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @error('og_image_alt') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Images & alt text') }}</h3>
                <p class="mom-subtext max-w-2xl">{{ __('Set alt text on <img> tags inside block HTML, and use the OG image alt field above for the share card. The Media library can store assets; reference them in blocks with proper alt attributes for SEO and accessibility.') }}</p>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('AEO') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Question') }}</label>
                        <textarea wire:model="aeo_question" rows="2" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Answer snippet') }}</label>
                        <textarea wire:model="aeo_answer" rows="4" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Performance & language') }}</h3>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Readability (draft signal)') }}</p>
                        @if ($readabilityHint)
                            <p class="mt-2 text-sm text-[var(--text-primary)]">{{ __('Score') }}: {{ $readabilityHint['score'] }}/100 @if ($readabilityHint['avg_words_per_sentence'] !== null) · {{ __('Avg words / sentence') }}: {{ $readabilityHint['avg_words_per_sentence'] }} @endif</p>
                            <p class="mom-subtext mt-1">{{ $readabilityHint['note'] }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Internal linking ideas') }}</p>
                        <p class="mom-subtext mt-1 mb-2">{{ __('Copy paths into blocks as internal links to strengthen topical clusters.') }}</p>
                        <ul class="mom-subtext max-h-36 list-inside list-disc overflow-y-auto custom-scrollbar rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                            @forelse ($otherPagesForLinks as $op)
                                <li wire:key="link-hint-{{ $op->id }}">
                                    <span class="text-[var(--text-primary)]">{{ $op->title }}</span>
                                    — <code class="font-mono text-xs">{{ '/p/'.$op->slug }}</code>
                                </li>
                            @empty
                                <li>{{ __('No other pages yet — create another page to see suggestions.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Content freshness') }}</p>
                            <p class="mom-subtext mt-1">{{ __('Last marked reviewed') }}: {{ $content_reviewed_label !== '' ? $content_reviewed_label : __('—') }}</p>
                        </div>
                        @if ($editingId)
                            <button type="button" wire:click="markContentReviewed" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)]">{{ __('Mark reviewed now') }}</button>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Hreflang JSON') }}</label>
                        <p class="mom-subtext mb-2">{{ __('Locale keys map to absolute URLs (e.g. "en": "https://…", "hi": "https://…").') }}</p>
                        <textarea wire:model="hreflang_json_input" rows="5" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                        @error('hreflang_json_input') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('On-page SEO checklist') }}</h3>
                <p class="mom-subtext mb-4">{{ __('Meta length, canonical, OG social layer, and keyword alignment — heuristic signals similar to Rank Math-style guidance.') }}</p>
                @if ($onPageSeo)
                    <p class="text-lg font-semibold text-[var(--text-primary)]">{{ $onPageSeo['score'] }}/100</p>
                    @if (count($onPageSeo['checks']) > 0)
                        <ul class="mom-subtext mt-2 list-inside list-disc text-[var(--text-secondary)]">
                            @foreach ($onPageSeo['checks'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (count($onPageSeo['warnings']) > 0)
                        <ul class="mom-subtext mt-2 list-inside list-disc text-[var(--warning)]">
                            @foreach ($onPageSeo['warnings'] as $warn)
                                <li>{{ $warn }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('AEO+ / LLM readiness') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Entity tags') }}</label>
                        <p class="mom-subtext mb-2">{{ __('Comma-separated entities this page should reinforce for AI systems.') }}</p>
                        <textarea wire:model="entity_tags_input" rows="2" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="{{ __('e.g. Home nursing, Bangalore, Post-operative care') }}"></textarea>
                    </div>
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-[var(--text-secondary)]">
                        <input type="checkbox" wire:model.live="fact_check_verified" class="rounded border-[rgba(255,255,255,0.15)]" />
                        {{ __('Fact-check verified (editor attestation)') }}
                    </label>
                    @if ($llmReadiness)
                        <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('LLM readiness (heuristic)') }}</p>
                            <p class="mt-2 text-lg font-semibold text-[var(--text-primary)]">{{ $llmReadiness['score'] }}/100</p>
                            @if (count($llmReadiness['checks']) > 0)
                                <ul class="mom-subtext mt-2 list-inside list-disc">
                                    @foreach ($llmReadiness['checks'] as $check)
                                        <li>{{ $check }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mom-subtext mt-2">{{ __('Fill SEO, AEO, and schema fields to raise this score.') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('GEO (PIN codes)') }}</h3>
                <p class="mom-subtext mb-4">{{ __('Select coverage PINs; area and city are read-only from the directory.') }}</p>
                <div class="custom-scrollbar max-h-64 overflow-y-auto rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                    @foreach ($pinCodes as $pc)
                        <label class="flex cursor-pointer gap-3 border-b border-[var(--border-panel-soft)] py-2 last:border-0">
                            <input type="checkbox" wire:model.live="selectedPinIds" value="{{ $pc->id }}" class="mt-1 rounded border-[rgba(255,255,255,0.15)]" />
                            <span class="text-sm">
                                <span class="font-mono text-[var(--text-primary)]">{{ $pc->pincode }}</span>
                                — {{ $pc->area_name }}, {{ $pc->city ?? '—' }}
                            </span>
                        </label>
                    @endforeach
                </div>

                @foreach ($selectedPinIds as $pid)
                    @php $pinRow = $pinCodes->firstWhere('id', (int) $pid); @endphp
                    @if ($pinRow)
                        <div class="mom-card mt-4 border border-[var(--border-panel-soft)] p-4" wire:key="geo-{{ $pid }}">
                            <p class="font-mono text-sm text-[var(--text-primary)]">{{ $pinRow->pincode }}</p>
                            <p class="mom-subtext mt-1">{{ __('Area') }}: {{ $pinRow->area_name }} · {{ __('City') }}: {{ $pinRow->city ?? '—' }}</p>
                            <p class="mom-micro mt-2">{{ __('Suggested phrases') }}</p>
                            <ul class="mom-subtext mt-1 list-inside list-disc">
                                @foreach (\App\Livewire\SiteArchitect\Pages::defaultKeywordHints($pinRow) as $hint)
                                    <li>{{ $hint }}</li>
                                @endforeach
                            </ul>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                                    <input type="checkbox" wire:model="pinPivot.{{ $pid }}.serviceability" class="rounded border-[rgba(255,255,255,0.15)]" />
                                    {{ __('Serviceability') }}
                                </label>
                                <div>
                                    <label class="block text-xs text-[var(--text-muted)]">{{ __('Delivery charge') }}</label>
                                    <input type="text" wire:model="pinPivot.{{ $pid }}.delivery_charge" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1 text-sm" placeholder="0.00" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-[var(--text-muted)]">{{ __('Location keywords override') }}</label>
                                    <textarea wire:model="pinPivot.{{ $pid }}.location_keywords" rows="2" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1 text-sm" placeholder="{{ __('Optional; overrides auto-generated GEO phrases') }}"></textarea>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </section>

            @if ($editingId)
                <section class="mom-card p-6">
                    <h3 class="mom-section-title mb-4">{{ __('Revision history') }}</h3>
                    <p class="mom-subtext mb-4">{{ __('Snapshots are stored when you save (latest 40 kept). Restore loads values into this form — save to publish.') }}</p>
                    <div class="custom-scrollbar max-h-72 space-y-2 overflow-y-auto rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                        @forelse ($revisions as $rev)
                            <div wire:key="rev-{{ $rev->id }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--border-panel-soft)] py-2 text-sm last:border-0">
                                <span class="text-[var(--text-secondary)]">
                                    {{ $rev->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                    @if ($rev->user)
                                        · {{ $rev->user->name }}
                                    @endif
                                </span>
                                <button type="button" wire:click="restoreRevision({{ $rev->id }})" class="text-mom-gold hover:underline">{{ __('Restore into form') }}</button>
                            </div>
                        @empty
                            <p class="mom-subtext">{{ __('No snapshots yet — save the page once to create the first revision.') }}</p>
                        @endforelse
                    </div>
                </section>
            @endif

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Schema & tracking') }}</h3>
                <p class="mom-subtext mb-4">{{ __('JSON-LD below is emitted on the public page in addition to global organization schema.') }}</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Schema JSON') }}</label>
                        <textarea wire:model="schema_json_input" rows="8" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                        @error('schema_json_input') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('GTM code') }}</label>
                        <textarea wire:model="gtm_code" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Pixel code') }}</label>
                        <textarea wire:model="pixel_code" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="savePage" class="rounded-mom-chrome bg-[var(--accent-gold)] px-5 py-2.5 text-sm font-semibold text-[#120f0d]">{{ __('Save page') }}</button>
                <button type="button" wire:click="cancelForm" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-5 py-2.5 text-sm text-[var(--text-secondary)]">{{ __('Cancel') }}</button>
            </div>
        </div>
    @endif

    @if ($blockModalOpen)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4" wire:click.self="closeBlockModal">
            <div class="mom-card max-h-[90vh] w-full max-w-2xl overflow-y-auto p-6" @click.stop>
                <h4 class="mom-section-title">{{ $blockEditingSlug ? __('Edit block') : __('New block') }}</h4>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block name') }}</label>
                        <input type="text" wire:model.live="block_name" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @error('block_name') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block slug') }}</label>
                        <input
                            type="text"
                            wire:model="block_slug"
                            class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm"
                            @if ($blockEditingSlug !== null) disabled @endif
                        />
                        @error('block_slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Insert service…') }}</label>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <select wire:model.live="service_choice" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                                <option value="">{{ __('— Choose a service —') }}</option>
                                @foreach ($servicesForInsert as $svc)
                                    <option value="{{ $svc->service_code }}">{{ $svc->title }} ({{ $svc->service_code }})</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="appendServiceToken" wire:loading.attr="disabled" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] disabled:opacity-50">{{ __('Add service line') }}</button>
                        </div>
                        <p class="mom-subtext mt-2">{{ __('Adds a service token below in the form of double-braced service:code. Your Blade markup decides the layout — render the loaded $services collection or a per-code variable.') }}</p>
                        @error('service_choice') <span class="mt-1 block text-xs text-[var(--danger)]">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Code (HTML / Blade / Alpine)') }}</label>
                        <textarea wire:model="block_code" rows="14" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                        @error('block_code') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="button" wire:click="saveBlockInModal" class="rounded-mom-chrome bg-[var(--accent-gold)] px-4 py-2 text-sm font-semibold text-[#120f0d]">{{ __('Save block') }}</button>
                    <button type="button" wire:click="closeBlockModal" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-4 py-2 text-sm">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
