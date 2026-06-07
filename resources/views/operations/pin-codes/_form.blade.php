@php
    /** @var \App\Models\PinCode $pinCode */
@endphp

<div class="space-y-8">
    <div class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Location & operations') }}</h2>
        <p class="mom-subtext mt-2 max-w-2xl">{{ __('Core coverage fields used for serviceability, routing, and future delivery logic.') }}</p>
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="pincode" :value="__('Pincode')" variant="mom" />
                <x-text-input id="pincode" name="pincode" type="text" inputmode="numeric" class="mt-2 block w-full" :value="old('pincode', $pinCode->pincode)" required variant="mom" autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('pincode')" variant="mom" />
            </div>
            <div>
                <x-input-label for="area_name" :value="__('Area name')" variant="mom" />
                <x-text-input id="area_name" name="area_name" type="text" class="mt-2 block w-full" :value="old('area_name', $pinCode->area_name)" required variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('area_name')" variant="mom" />
            </div>
            <div>
                <x-input-label for="city" :value="__('City')" variant="mom" />
                <x-text-input id="city" name="city" type="text" class="mt-2 block w-full" :value="old('city', $pinCode->city)" required variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('city')" variant="mom" />
            </div>
            <div>
                <x-input-label for="locality" :value="__('Locality')" variant="mom" />
                <x-text-input id="locality" name="locality" type="text" class="mt-2 block w-full" :value="old('locality', $pinCode->locality)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('locality')" variant="mom" />
            </div>
            <div>
                <x-input-label for="delivery_charge" :value="__('Delivery / service charge')" variant="mom" />
                <x-text-input id="delivery_charge" name="delivery_charge" type="number" step="0.01" min="0" class="mt-2 block w-full" :value="old('delivery_charge', $pinCode->delivery_charge)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('delivery_charge')" variant="mom" />
            </div>
            <div class="flex flex-col gap-4 pt-1 md:col-span-2 md:flex-row md:flex-wrap md:items-center">
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_serviceable" value="0" />
                    <input id="is_serviceable" name="is_serviceable" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold focus:ring-1 focus:ring-[rgba(197,160,89,0.35)]" @checked(old('is_serviceable', $pinCode->is_serviceable)) />
                    <x-input-label for="is_serviceable" :value="__('Serviceable area')" variant="mom" />
                </div>
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0" />
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold focus:ring-1 focus:ring-[rgba(197,160,89,0.35)]" @checked(old('is_active', $pinCode->is_active)) />
                    <x-input-label for="is_active" :value="__('Active record')" variant="mom" />
                </div>
            </div>
        </div>
    </div>

    <div class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Local SEO & geo readiness') }}</h2>
        <p class="mom-subtext mt-2 max-w-2xl">{{ __('Structured metadata for area-based indexing and future geo landing pages (for example /locations/arekere-bangalore).') }}</p>
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="meta_title" :value="__('Meta title')" variant="mom" />
                <x-text-input id="meta_title" name="meta_title" type="text" class="mt-2 block w-full" :value="old('meta_title', $pinCode->meta_title)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('meta_title')" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="meta_description" :value="__('Meta description')" variant="mom" />
                <textarea
                    id="meta_description"
                    name="meta_description"
                    rows="4"
                    class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner placeholder:text-[var(--text-muted)] focus:border-[rgba(197,160,89,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(197,160,89,0.22)]"
                >{{ old('meta_description', $pinCode->meta_description) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('meta_description')" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="seo_keywords" :value="__('SEO keywords')" variant="mom" />
                <textarea
                    id="seo_keywords"
                    name="seo_keywords"
                    rows="3"
                    class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner placeholder:text-[var(--text-muted)] focus:border-[rgba(197,160,89,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(197,160,89,0.22)]"
                >{{ old('seo_keywords', $pinCode->seo_keywords) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('seo_keywords')" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="slug" :value="__('URL slug (optional)')" variant="mom" />
                <x-text-input id="slug" name="slug" type="text" class="mt-2 block w-full" :value="old('slug', $pinCode->slug)" variant="mom" />
                <p class="mom-micro mt-1">{{ __('Leave blank to auto-generate from area and pincode. Clear to regenerate on save.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('slug')" variant="mom" />
            </div>
            <div class="flex items-center gap-3 md:col-span-2">
                <input type="hidden" name="geo_page_ready" value="0" />
                <input id="geo_page_ready" name="geo_page_ready" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold focus:ring-1 focus:ring-[rgba(197,160,89,0.35)]" @checked(old('geo_page_ready', $pinCode->geo_page_ready)) />
                <x-input-label for="geo_page_ready" :value="__('Marked ready for geo landing page')" variant="mom" />
            </div>
        </div>
    </div>

    @include('operations.pin-codes.partials.geo-enrichment')

    @isset($managedModule)
        <x-dynamic-fields.unified-table :module="$managedModule" :values="$customFieldValues ?? new stdClass()" />
    @endisset
</div>
