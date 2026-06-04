@php
    /** @var \App\Models\ServiceCategory $category */
    /** @var \Illuminate\Support\Collection<int, \App\Models\ServiceCategory> $parentOptions */
    $mode = $mode ?? 'create';
@endphp

<div class="mom-card p-6 space-y-6">
    <p class="mom-subtext max-w-2xl">{{ __('Categories organize services for navigation and filtering only. Service SEO remains the source of truth — no category-level SEO fields.') }}</p>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <x-input-label for="name" :value="__('Name')" variant="mom" />
            <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $category->name)" required autofocus variant="mom" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="code" :value="__('Code / slug')" variant="mom" />
            <x-text-input id="code" name="code" type="text" class="mt-2 block w-full font-mono text-sm" :value="old('code', $category->code)" required variant="mom" autocomplete="off" />
            <p class="mom-subtext mt-1">{{ __('Lowercase letters, numbers, hyphens. Used in URLs: /service-categories/your-code') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>
        <div>
            <x-input-label for="sort_order" :value="__('Sort order')" variant="mom" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-2 block w-full" :value="old('sort_order', $category->sort_order ?? 0)" variant="mom" />
            <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="parent_id" :value="__('Parent category')" variant="mom" />
            <select id="parent_id" name="parent_id" class="rounded-mom-chrome mt-2 block w-full border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('None (top level)') }}</option>
                @foreach ($parentOptions as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === (int) $parent->id)>
                        {{ $parent->breadcrumbLabel() }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('Description')" variant="mom" />
            <textarea
                id="description"
                name="description"
                rows="4"
                class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner"
            >{{ old('description', $category->description) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
        <div class="md:col-span-2 flex items-center gap-3">
            <input type="hidden" name="is_active" value="0" />
            <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold" @checked(old('is_active', $category->is_active ?? true)) />
            <x-input-label for="is_active" :value="__('Active')" variant="mom" />
        </div>
    </div>
</div>
