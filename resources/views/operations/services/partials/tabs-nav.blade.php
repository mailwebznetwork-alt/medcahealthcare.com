@php
    $activeTab = $activeTab ?? 'basic';
    $hasCustomFields = isset($managedModule) && ($managedModule->fieldDefinitions->isNotEmpty() || (auth()->user()?->canManageDynamicModuleSchema() ?? false));
    $reviewCount = isset($serviceReviews) ? $serviceReviews->count() : 0;

    $tabs = [
        'basic' => __('Basic'),
        'content' => __('Content'),
        'media' => __('Media'),
        'clinical' => __('Clinical'),
        'seo' => __('SEO'),
        'aeo' => __('AEO'),
        'faq' => __('FAQ'),
        'schema' => __('Schema'),
        'related' => __('Related'),
        'geo' => __('GEO'),
        'publishing' => __('Publishing'),
    ];

    if (($mode ?? 'create') === 'edit') {
        $tabs['reviews'] = $reviewCount > 0
            ? __('Reviews').' ('.$reviewCount.')'
            : __('Reviews');
    }

    if ($hasCustomFields) {
        $tabs['custom'] = __('Custom');
    }
@endphp

<nav class="mom-card mb-6 overflow-x-auto p-2" aria-label="{{ __('Service form sections') }}">
    <div class="flex flex-wrap gap-1">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}'
                    ? 'border-mom-gold bg-[rgba(197,160,89,0.12)] text-mom-gold'
                    : 'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]'"
                class="rounded-mom-chrome border px-3 py-2 text-sm font-semibold tracking-wide transition-colors"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>
    <input type="hidden" name="active_tab" x-model="tab" />
</nav>
