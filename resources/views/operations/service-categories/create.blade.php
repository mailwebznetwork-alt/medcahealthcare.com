<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Add service category') }}</h2>
    <form method="post" action="{{ route('operations.service-categories.store') }}" class="space-y-8">
        @csrf
        @include('operations.service-categories._form', ['category' => $category, 'parentOptions' => $parentOptions, 'mode' => 'create'])
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom">{{ __('Save category') }}</x-primary-button>
            <a href="{{ route('operations.service-categories.index') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-operations.workspace>
