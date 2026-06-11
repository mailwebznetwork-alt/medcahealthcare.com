@php
    $catalogKind = $catalogKind ?? 'category';
    $entity = $service;
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-4">{{ __('Publishing') }}</h3>
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="publish_status" :value="__('Publish status')" variant="mom" />
            <select id="publish_status" name="publish_status" class="rounded-mom-chrome mt-2 block w-full border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                @foreach (\App\Enums\PublishStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('publish_status', $entity->publish_status?->value ?? \App\Enums\PublishStatus::Published->value) === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="visibility" :value="__('Visibility')" variant="mom" />
            <select id="visibility" name="visibility" class="rounded-mom-chrome mt-2 block w-full border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                @foreach (\App\Enums\ServiceVisibility::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('visibility', $entity->visibility?->value ?? \App\Enums\ServiceVisibility::Public->value) === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        {{-- sort_order lives on the Basic tab for categories and sub-services; duplicate fields here overwrote user edits on save --}}
        <div class="md:col-span-2">
            <x-input-label for="page_id" :value="__('Linked discovery page')" variant="mom" />
            <select id="page_id" name="page_id" class="rounded-mom-chrome mt-2 block w-full border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ $catalogKind === 'sub_service' ? __('— Auto-provision sub-service page —') : __('— Auto-provision category page —') }}</option>
                @foreach ($detailPages ?? [] as $p)
                    <option value="{{ $p->id }}" @selected((int) old('page_id', $entity->page_id) === (int) $p->id)>{{ $p->title }} ({{ $p->slug }})</option>
                @endforeach
            </select>
            <p class="mom-subtext mt-1">{{ $catalogKind === 'sub_service' ? __('Saving syncs the sub-service discovery page.') : __('Public URL uses category slug/code. Saving syncs the discovery page.') }}</p>
        </div>
        <div class="flex flex-col gap-4 pt-4 md:col-span-2">
            <input type="hidden" name="is_active" value="0" />
            <label class="flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="is_active" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('is_active', $entity->is_active ?? true)) />
                <span class="text-sm text-[var(--text-secondary)]">{{ __('Active') }}</span>
            </label>
            <input type="hidden" name="is_featured" value="0" />
            <label class="flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="is_featured" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('is_featured', $entity->is_featured ?? false)) />
                <span class="text-sm text-[var(--text-secondary)]">{{ __('Featured') }}</span>
            </label>
            <input type="hidden" name="show_on_homepage" value="0" />
            <label class="flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="show_on_homepage" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('show_on_homepage', $entity->show_on_homepage ?? false)) />
                <span class="text-sm text-[var(--text-secondary)]">{{ __('Show on homepage') }}</span>
            </label>
            <input type="hidden" name="show_on_about" value="0" />
            <label class="flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="show_on_about" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('show_on_about', $entity->show_on_about ?? false)) />
                <span class="text-sm text-[var(--text-secondary)]">{{ __('Show on about') }}</span>
            </label>
            <input type="hidden" name="show_on_contact" value="0" />
            <label class="flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="show_on_contact" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('show_on_contact', $entity->show_on_contact ?? false)) />
                <span class="text-sm text-[var(--text-secondary)]">{{ __('Show on contact') }}</span>
            </label>
            @if ($catalogKind === 'sub_service')
                <input type="hidden" name="is_top_rated" value="0" />
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_top_rated" value="1" class="rounded border-[rgba(255,255,255,0.15)]" @checked(old('is_top_rated', $entity->is_top_rated ?? false)) />
                    <span class="text-sm text-[var(--text-secondary)]">{{ __('Top rated') }}</span>
                </label>
            @endif
        </div>
    </div>
</section>
