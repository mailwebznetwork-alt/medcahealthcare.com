<div>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(98,195,120,0.28)] bg-[rgba(98,195,120,0.08)] px-4 py-3 text-sm text-[var(--success)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($mode === 'list')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Blogs') }}</h2>
            <a
                href="{{ route('site-architect.blogs.index') }}?create=1"
                wire:click.prevent="startCreate"
                role="button"
                class="inline-flex cursor-pointer rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.1)] px-4 py-2 text-sm font-medium text-mom-gold no-underline"
            >
                {{ __('Create blog') }}
            </a>
        </div>

        <div class="mom-card overflow-x-auto p-0">
            <table class="mom-table w-full min-w-[800px] text-left text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3">{{ __('Blog title') }}</th>
                        <th class="px-4 py-3">{{ __('Slug') }}</th>
                        <th class="px-4 py-3">{{ __('Published status') }}</th>
                        <th class="px-4 py-3">{{ __('Publish date') }}</th>
                        <th class="px-4 py-3">{{ __('Preview') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blogs as $blog)
                        <tr wire:key="blog-row-{{ $blog->id }}">
                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $blog->title }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-[var(--text-muted)]">{{ $blog->slug }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="togglePublished({{ $blog->id }})"
                                    class="text-xs font-semibold uppercase tracking-wide {{ $blog->is_published ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}"
                                >
                                    {{ $blog->is_published ? __('On') : __('Off') }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-[var(--text-secondary)]">
                                {{ $blog->published_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('site-architect.blogs.preview', $blog) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-mom-gold hover:underline"
                                >{{ __('Preview') }}</a>
                                @if ($blog->is_published)
                                    <span class="text-[var(--text-muted)]">·</span>
                                    <a
                                        href="{{ route('blog.public', $blog) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-mom-gold hover:underline"
                                    >{{ __('Live') }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="startEdit({{ $blog->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Edit') }}</button>
                                <button type="button" wire:click="duplicateBlog({{ $blog->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Duplicate') }}</button>
                                <button
                                    type="button"
                                    wire:click="deleteBlog({{ $blog->id }})"
                                    wire:confirm="{{ __('Delete this blog?') }}"
                                    class="text-[var(--danger)] hover:underline"
                                >{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-[var(--text-muted)]">{{ __('No blogs yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $blogs->links() }}
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? __('Edit blog') : __('New blog') }}</h2>
            <button type="button" wire:click="cancelForm" class="mom-subtext text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Back to list') }}</button>
        </div>

        <div class="space-y-8">
            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Basic info') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Blog title') }}</label>
                        <input type="text" wire:model.live="title" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]" />
                        @error('title') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Slug') }}</label>
                        <input type="text" wire:model="slug" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm text-[var(--text-primary)]" />
                        @error('slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Excerpt') }}</label>
                        <textarea wire:model="excerpt" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Featured image') }}</label>
                        <input
                            type="file"
                            wire:model="featured_image_upload"
                            accept="image/*"
                            class="mt-2 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-chrome file:border file:border-[var(--border-panel-soft)] file:bg-[var(--bg-card-matte)] file:px-3 file:py-2 file:text-sm file:text-[var(--text-primary)]"
                        />
                        @error('featured_image_upload') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="featured_image_upload" class="mom-subtext mt-2 text-xs">{{ __('Uploading…') }}</div>
                        @if ($featured_image_upload)
                            <div class="mt-3">
                                <img src="{{ $featured_image_upload->temporaryUrl() }}" alt="" class="max-h-40 rounded-mom-chrome border border-[var(--border-panel-soft)] object-cover" />
                            </div>
                        @elseif ($featured_image_path)
                            <div class="mt-3 flex flex-wrap items-end gap-3">
                                <img
                                    src="{{ \Illuminate\Support\Str::startsWith($featured_image_path, ['http://', 'https://']) ? $featured_image_path : asset('storage/'.$featured_image_path) }}"
                                    alt=""
                                    class="max-h-40 rounded-mom-chrome border border-[var(--border-panel-soft)] object-cover"
                                />
                                <button type="button" wire:click="removeFeaturedImage" class="text-xs text-[var(--danger)] hover:underline">{{ __('Remove image') }}</button>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Author name') }}</label>
                        <input type="text" wire:model="author_name" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Publish date') }}</label>
                        <input type="datetime-local" wire:model="published_at_input" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    <div class="flex items-center gap-3 md:col-span-2">
                        <input type="checkbox" wire:model="is_published" id="blog-published" class="rounded border-[rgba(255,255,255,0.15)]" />
                        <label for="blog-published" class="text-sm text-[var(--text-secondary)]">{{ __('Published') }}</label>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Block builder') }}</h3>
                <p class="mom-subtext mb-4 max-w-2xl">{{ __('Order defines blog structure. Blocks hold Blade/HTML; modules resolve via config.') }}</p>

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
                    @endif
                    @error('module_choice') <span class="mt-2 block text-xs text-[var(--danger)]">{{ $message }}</span> @enderror
                </div>

                <ul class="mt-6 space-y-2">
                    @foreach ($contentParts as $idx => $part)
                        <li wire:key="blog-part-{{ $idx }}-{{ $part['type'] }}-{{ $part['slug'] }}" class="flex flex-wrap items-center justify-between gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] px-3 py-2 text-xs text-[var(--text-secondary)]">
                            <div class="flex min-w-0 flex-1 flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
                                <span class="font-mono">{{ '{{'.$part['type'].':'.$part['slug'].str_repeat('}', 2) }}</span>
                                <span class="text-[var(--text-primary)]">
                                    @if ($part['type'] === 'block')
                                        <span class="text-[var(--text-muted)]">{{ __('Block name') }}:</span>
                                        {{ $blockNameMap[$part['slug']] ?? '—' }}
                                        <span class="mx-2 text-[var(--text-muted)]">·</span>
                                        <span class="text-[var(--text-muted)]">{{ __('Slug') }}:</span>
                                        {{ $part['slug'] }}
                                    @else
                                        <span class="text-[var(--text-muted)]">{{ __('Module') }}:</span>
                                        {{ $part['slug'] }}
                                    @endif
                                </span>
                            </div>
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
                <h3 class="mom-section-title mb-4">{{ __('SEO / AEO') }}</h3>

                <p class="mom-micro mb-3 text-[var(--text-muted)]">{{ __('SEO') }}</p>
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
                </div>

                <p class="mom-micro mb-3 mt-8 text-[var(--text-muted)]">{{ __('Headings') }}</p>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach (['h1' => __('H1'), 'h2' => __('H2'), 'h3' => __('H3'), 'h4' => __('H4'), 'h5' => __('H5'), 'h6' => __('H6')] as $field => $label)
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ $label }}</label>
                            <input type="text" wire:model="{{ $field }}" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        </div>
                    @endforeach
                </div>

                <p class="mom-micro mb-3 mt-8 text-[var(--text-muted)]">{{ __('AEO') }}</p>
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

                <p class="mom-micro mb-3 mt-8 text-[var(--text-muted)]">{{ __('Schema') }}</p>
                <div>
                    <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Schema JSON') }}</label>
                    <textarea wire:model="schema_json_input" rows="8" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                    @error('schema_json_input') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="saveBlog" class="rounded-mom-chrome bg-[var(--accent-gold)] px-5 py-2.5 text-sm font-semibold text-[#120f0d]">{{ __('Save blog') }}</button>
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
