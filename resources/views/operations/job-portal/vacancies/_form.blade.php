@php
    /** @var \App\Models\Vacancy $vacancy */
    use App\Enums\EmploymentType;
    use App\Enums\VacancyVisibility;
    use App\Enums\VacancyWorkflowStatus;
@endphp

<div class="space-y-8">
    <div class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Role & location') }}</h2>
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="title" :value="__('Title')" variant="mom" />
                <x-text-input id="title" name="title" type="text" class="mt-2 block w-full" :value="old('title', $vacancy->title)" required variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('title')" variant="mom" />
            </div>
            <div>
                <x-input-label for="department" :value="__('Department')" variant="mom" />
                <x-text-input id="department" name="department" type="text" class="mt-2 block w-full" :value="old('department', $vacancy->department)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('department')" variant="mom" />
            </div>
            <div>
                <x-input-label for="city" :value="__('City')" variant="mom" />
                <x-text-input id="city" name="city" type="text" class="mt-2 block w-full" :value="old('city', $vacancy->city)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('city')" variant="mom" />
            </div>
            <div>
                <x-input-label for="area" :value="__('Area / locality')" variant="mom" />
                <x-text-input id="area" name="area" type="text" class="mt-2 block w-full" :value="old('area', $vacancy->area)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('area')" variant="mom" />
            </div>
            <div>
                <x-input-label for="pin_code" :value="__('PIN code')" variant="mom" />
                <x-text-input id="pin_code" name="pin_code" type="text" class="mt-2 block w-full" :value="old('pin_code', $vacancy->pin_code)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('pin_code')" variant="mom" />
            </div>
            <div>
                <x-input-label for="country_code" :value="__('Country code')" variant="mom" />
                <x-text-input id="country_code" name="country_code" type="text" class="mt-2 block w-full" :value="old('country_code', $vacancy->country_code)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('country_code')" variant="mom" />
            </div>
            <div>
                <x-input-label for="employment_type" :value="__('Employment type')" variant="mom" />
                <select id="employment_type" name="employment_type" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">
                    @foreach (EmploymentType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('employment_type', $vacancy->employment_type?->value ?? EmploymentType::FullTime->value) === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('employment_type')" variant="mom" />
            </div>
            <div class="md:col-span-2 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <x-input-label for="salary_min" :value="__('Salary min')" variant="mom" />
                    <x-text-input id="salary_min" name="salary_min" type="number" step="0.01" class="mt-2 block w-full" :value="old('salary_min', $vacancy->salary_min)" variant="mom" />
                    <x-input-error class="mt-2" :messages="$errors->get('salary_min')" variant="mom" />
                </div>
                <div>
                    <x-input-label for="salary_max" :value="__('Salary max')" variant="mom" />
                    <x-text-input id="salary_max" name="salary_max" type="number" step="0.01" class="mt-2 block w-full" :value="old('salary_max', $vacancy->salary_max)" variant="mom" />
                    <x-input-error class="mt-2" :messages="$errors->get('salary_max')" variant="mom" />
                </div>
                <div>
                    <x-input-label for="salary_currency" :value="__('Currency')" variant="mom" />
                    <x-text-input id="salary_currency" name="salary_currency" type="text" class="mt-2 block w-full" :value="old('salary_currency', $vacancy->salary_currency)" variant="mom" />
                    <x-input-error class="mt-2" :messages="$errors->get('salary_currency')" variant="mom" />
                </div>
            </div>
            <div>
                <x-input-label for="closing_date" :value="__('Closing date')" variant="mom" />
                <x-text-input id="closing_date" name="closing_date" type="date" class="mt-2 block w-full" :value="old('closing_date', optional($vacancy->closing_date)?->format('Y-m-d'))" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('closing_date')" variant="mom" />
            </div>
        </div>
    </div>

    <div class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Publishing & workflow') }}</h2>
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="workflow_status" :value="__('Workflow status')" variant="mom" />
                <select id="workflow_status" name="workflow_status" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">
                    @foreach (VacancyWorkflowStatus::cases() as $st)
                        <option value="{{ $st->value }}" @selected(old('workflow_status', $vacancy->workflow_status?->value ?? VacancyWorkflowStatus::Draft->value) === $st->value)>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('workflow_status')" variant="mom" />
            </div>
            <div>
                <x-input-label for="visibility" :value="__('Visibility')" variant="mom" />
                <select id="visibility" name="visibility" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">
                    @foreach (VacancyVisibility::cases() as $vis)
                        <option value="{{ $vis->value }}" @selected(old('visibility', $vacancy->visibility?->value ?? VacancyVisibility::Public->value) === $vis->value)>
                            {{ $vis->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('visibility')" variant="mom" />
            </div>
            <div class="flex items-center gap-3 pt-2">
                <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold focus:ring-1 focus:ring-[rgba(212,169,95,0.35)]" @checked(old('is_active', $vacancy->is_active)) />
                <x-input-label for="is_active" :value="__('Active listing (operational)')" variant="mom" />
            </div>
            <div>
                <x-input-label for="sort_order" :value="__('Sort order')" variant="mom" />
                <x-text-input id="sort_order" name="sort_order" type="number" class="mt-2 block w-full" :value="old('sort_order', $vacancy->sort_order)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('sort_order')" variant="mom" />
            </div>
            @if ($vacancy->exists)
                <div class="md:col-span-2">
                    <p class="mom-micro">{{ __('Public URL') }}</p>
                    <p class="mom-body-text mt-1 break-all text-[var(--text-secondary)]">{{ $vacancy->workflow_status === \App\Enums\VacancyWorkflowStatus::Published ? route('careers.show', ['slug' => $vacancy->slug]) : __('Publish to generate a public careers URL.') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Content') }}</h2>
        <div class="mt-6 space-y-6">
            <div>
                <x-input-label for="summary" :value="__('Summary')" variant="mom" />
                <textarea id="summary" name="summary" rows="3" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">{{ old('summary', $vacancy->summary) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('summary')" variant="mom" />
            </div>
            <div>
                <x-input-label for="description" :value="__('Full description')" variant="mom" />
                <textarea id="description" name="description" rows="10" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">{{ old('description', $vacancy->description) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('description')" variant="mom" />
            </div>
            <div>
                <x-input-label for="requirements" :value="__('Requirements')" variant="mom" />
                <textarea id="requirements" name="requirements" rows="6" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">{{ old('requirements', $vacancy->requirements) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('requirements')" variant="mom" />
            </div>
        </div>
    </div>

    <div class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('SEO & structured data') }}</h2>
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="seo_title" :value="__('SEO title')" variant="mom" />
                <x-text-input id="seo_title" name="seo_title" type="text" class="mt-2 block w-full" :value="old('seo_title', $vacancy->seo_title)" variant="mom" />
                <x-input-error class="mt-2" :messages="$errors->get('seo_title')" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="seo_description" :value="__('SEO description')" variant="mom" />
                <textarea id="seo_description" name="seo_description" rows="3" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">{{ old('seo_description', $vacancy->seo_description) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('seo_description')" variant="mom" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="focus_keywords" :value="__('Focus keywords')" variant="mom" />
                <x-text-input id="focus_keywords" name="focus_keywords" type="text" class="mt-2 block w-full" :value="old('focus_keywords', $vacancy->focus_keywords)" variant="mom" />
                <p class="mom-subtext mt-1">{{ __('Comma-separated. Used for on-page signals and future AI tooling.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('focus_keywords')" variant="mom" />
            </div>
        </div>
    </div>

    <div class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('WhatsApp ATS & AI context') }}</h2>
        <div class="mt-6 space-y-6">
            <div>
                <x-input-label for="whatsapp_apply_url" :value="__('WhatsApp apply URL')" variant="mom" />
                <x-text-input id="whatsapp_apply_url" name="whatsapp_apply_url" type="url" class="mt-2 block w-full" :value="old('whatsapp_apply_url', $vacancy->whatsapp_apply_url)" variant="mom" />
                <p class="mom-subtext mt-1">{{ __('wa.me link or deep link for applicant handoff.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_apply_url')" variant="mom" />
            </div>
            <div>
                <x-input-label for="ai_context" :value="__('AI context (internal)')" variant="mom" />
                <textarea id="ai_context" name="ai_context" rows="4" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner focus:border-[rgba(212,169,95,0.28)] focus:outline-none focus:ring-1 focus:ring-[rgba(212,169,95,0.22)]">{{ old('ai_context', $vacancy->ai_context) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('ai_context')" variant="mom" />
            </div>
        </div>
    </div>
</div>
