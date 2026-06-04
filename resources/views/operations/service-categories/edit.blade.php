<x-operations.workspace>
    <h2 class="mom-section-title mb-2">{{ __('Edit category') }}</h2>
    <p class="mom-subtext mb-8">{{ $category->name }} <span class="font-mono text-xs">({{ $category->code }})</span></p>

    <form method="post" action="{{ route('operations.service-categories.update', $category) }}" class="space-y-8">
        @csrf
        @method('PUT')
        @include('operations.service-categories._form', ['category' => $category, 'parentOptions' => $parentOptions, 'mode' => 'edit'])
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom">{{ __('Save changes') }}</x-primary-button>
            <a href="{{ route('operations.service-categories.index') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
            @if ($category->is_active)
                <a href="{{ $category->publicUrl() }}" class="mom-cta-ghost" target="_blank" rel="noopener">{{ __('View public page') }}</a>
            @endif
        </div>
    </form>
</x-operations.workspace>
