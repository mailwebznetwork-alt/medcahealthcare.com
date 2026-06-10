@php
    /** @var \App\Models\SubService $subService */
    $subService = $subService ?? $service;
    $parentService = $parentService ?? null;
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-4">{{ __('Basic') }}</h3>
    @if ($parentService)
        <p class="mom-subtext mb-4">{{ __('Parent service: :title (:code)', ['title' => $parentService->title, 'code' => $parentService->service_code]) }}</p>
    @endif
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="sub_service_code" :value="__('Sub-service code')" variant="mom" />
            @if (($mode ?? 'create') === 'edit')
                <input type="hidden" name="sub_service_code" value="{{ $subService->sub_service_code }}" />
                <input id="sub_service_code" type="text" class="mt-2 block w-full cursor-not-allowed rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.5)] px-3 py-2.5 font-mono text-sm text-[var(--text-muted)]" value="{{ $subService->sub_service_code }}" readonly />
            @else
                <x-text-input id="sub_service_code" name="sub_service_code" type="text" class="mt-2 block w-full font-mono text-sm" :value="old('sub_service_code', $subService->sub_service_code)" required variant="mom" />
            @endif
            <p class="mom-subtext mt-1">{{ __('URL: /services/:parent/sub/:code', ['parent' => $parentService?->service_code ?? 'parent', 'code' => $subService->sub_service_code ?: 'your-code']) }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('sub_service_code')" />
        </div>
        <div>
            <x-input-label for="price_range" :value="__('Price range')" variant="mom" />
            <x-text-input id="price_range" name="price_range" type="text" class="mt-2 block w-full" :value="old('price_range', $subService->price_range)" variant="mom" />
        </div>
        <div>
            <x-input-label for="sort_order" :value="__('Sort order')" variant="mom" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-2 block w-full" :value="old('sort_order', $subService->sort_order ?? 0)" variant="mom" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="title" :value="__('Title')" variant="mom" />
            <x-text-input id="title" name="title" type="text" class="mt-2 block w-full" :value="old('title', $subService->title)" required variant="mom" />
            <x-input-error class="mt-2" :messages="$errors->get('title')" />
        </div>
    </div>
</section>
