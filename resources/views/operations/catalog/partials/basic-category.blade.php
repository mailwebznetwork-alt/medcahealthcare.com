@php
    /** @var \App\Models\ServiceCategory $category */
    $category = $category ?? $service;
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-4">{{ __('Basic') }}</h3>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <x-input-label for="name" :value="__('Name')" variant="mom" />
            <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $category->name)" required autofocus variant="mom" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="code" :value="__('Category code')" variant="mom" />
            @if (($mode ?? 'create') === 'edit')
                <input type="hidden" name="code" value="{{ $category->code }}" />
                <input id="code" type="text" class="mt-2 block w-full cursor-not-allowed rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.5)] px-3 py-2.5 font-mono text-sm text-[var(--text-muted)]" value="{{ $category->code }}" readonly />
            @else
                <x-text-input id="code" name="code" type="text" class="mt-2 block w-full font-mono text-sm" :value="old('code', $category->code)" required variant="mom" />
            @endif
            <p class="mom-subtext mt-1">{{ __('URL: /service-categories/:code', ['code' => $category->code ?: 'your-code']) }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>
        <div>
            <x-input-label for="price_range" :value="__('Price range')" variant="mom" />
            <x-text-input id="price_range" name="price_range" type="text" class="mt-2 block w-full" :value="old('price_range', $category->price_range)" variant="mom" />
        </div>
        <div>
            <x-input-label for="sort_order" :value="__('Sort order')" variant="mom" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-2 block w-full" :value="old('sort_order', $category->sort_order ?? 0)" variant="mom" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="parent_id" :value="__('Parent category')" variant="mom" />
            <select id="parent_id" name="parent_id" class="rounded-mom-chrome mt-2 block w-full border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('None (top level)') }}</option>
                @foreach ($parentOptions ?? [] as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === (int) $parent->id)>
                        {{ $parent->breadcrumbLabel() }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
        </div>
    </div>
</section>
