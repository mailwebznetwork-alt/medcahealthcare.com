<x-operations.workspace>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap gap-3">
        @if ($category->is_active)
            <a href="{{ $category->publicUrl() }}" class="mom-cta-ghost" target="_blank" rel="noopener">{{ __('View public page') }}</a>
        @endif
        <a href="{{ route('operations.service-categories.index') }}" class="mom-cta-ghost">{{ __('All categories') }}</a>
    </div>

    <h2 class="mom-section-title mb-2">{{ __('Edit category') }}</h2>
    <p class="mom-subtext mb-8">{{ $category->name }} <span class="font-mono text-xs">({{ $category->code }})</span></p>

    <form id="category-edit-form" method="post" action="{{ route('operations.service-categories.update', $category) }}" enctype="multipart/form-data" class="space-y-8 pb-24">
        @csrf
        @method('PUT')
        @include('operations.services._form', array_merge(
            ['mode' => 'edit', 'category' => $category],
            compact('service', 'catalogKind', 'parentOptions', 'linkedDetailPage', 'detailPages', 'pinCodes', 'optimizationScores', 'seoRecommendations', 'locationPageCount', 'activeTab', 'categoryOptions', 'serviceCatalog', 'selectedRelatedCodes', 'serviceReviews', 'subServices', 'selectedPinIds')
        ))
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom" type="submit">{{ __('Save changes') }}</x-primary-button>
            <a href="{{ route('operations.service-categories.index') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>

    <div class="fixed inset-x-0 bottom-0 z-50 border-t border-[rgba(255,255,255,0.06)] bg-[rgba(10,8,6,0.96)] px-4 py-3 shadow-[0_-8px_32px_rgba(0,0,0,0.45)] backdrop-blur-md lg:left-[var(--sidebar-width,17rem)]">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-end gap-3">
            <a href="{{ route('operations.service-categories.index') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
            <x-primary-button variant="mom" type="submit" form="category-edit-form">{{ __('Save changes') }}</x-primary-button>
        </div>
    </div>
</x-operations.workspace>
