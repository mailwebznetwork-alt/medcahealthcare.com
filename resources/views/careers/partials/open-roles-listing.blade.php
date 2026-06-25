@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Vacancy>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Vacancy> $vacancies */
    $vacancies = $vacancies ?? collect();
@endphp

<x-public.section class="bg-slate-50" aria-label="{{ __('Open positions') }}"
    x-data="{
        q: '',
        city: '',
        updateVisibility() {
            const q = this.q.trim().toLowerCase();
            const city = this.city.trim().toLowerCase();
            let visible = 0;
            this.$refs.list?.querySelectorAll('[data-job-card]').forEach((el) => {
                const li = el.closest('li');
                if (! li) return;
                const title = (el.dataset.title || '').toLowerCase();
                const loc = (el.dataset.location || '').toLowerCase();
                const summary = (el.dataset.summary || '').toLowerCase();
                const match = (!q || title.includes(q) || summary.includes(q)) && (!city || loc.includes(city));
                li.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (this.$refs.noMatch) {
                this.$refs.noMatch.classList.toggle('is-visible', visible === 0 && this.$refs.list !== undefined);
            }
        }
    }"
    x-init="$watch('q', () => updateVisibility()); $watch('city', () => updateVisibility())"
>
        <form class="mc-jobs-search" role="search" @submit.prevent="updateVisibility()">
            <div>
                <label for="mc-job-q">{{ __('What') }}</label>
                <input id="mc-job-q" type="search" x-model="q" placeholder="{{ __('Job title, keywords') }}" autocomplete="off" />
            </div>
            <div>
                <label for="mc-job-city">{{ __('Where') }}</label>
                <input id="mc-job-city" type="search" x-model="city" placeholder="{{ __('City or area') }}" autocomplete="off" />
            </div>
            <button type="submit">{{ __('Find jobs') }}</button>
        </form>

        @if ($vacancies->isEmpty())
            <div class="mc-jobs-empty mt-6">
                <p><strong>{{ __('No open roles right now') }}</strong></p>
                <p>{{ __('Check back soon or contact us for upcoming opportunities.') }}</p>
            </div>
        @else
            <p class="mc-jobs-count">
                <strong>{{ $vacancies->count() }}</strong> {{ __('jobs at') }} {{ config('careers.organization_name') }}
            </p>

            <ul class="mc-jobs-list" x-ref="list">
                @foreach ($vacancies as $vacancy)
                    @php
                        $location = collect([$vacancy->area, $vacancy->city])->filter()->implode(', ');
                        $summary = $vacancy->summary ?: $vacancy->description;
                        $salary = null;
                        if ($vacancy->salary_min !== null || $vacancy->salary_max !== null) {
                            $cur = $vacancy->salary_currency ?: 'INR';
                            if ($vacancy->salary_min !== null && $vacancy->salary_max !== null) {
                                $salary = $cur.' '.number_format((float) $vacancy->salary_min).' – '.number_format((float) $vacancy->salary_max);
                            } elseif ($vacancy->salary_min !== null) {
                                $salary = $cur.' '.number_format((float) $vacancy->salary_min).'+';
                            } else {
                                $salary = __('Up to').' '.$cur.' '.number_format((float) $vacancy->salary_max);
                            }
                        }
                    @endphp
                    <li>
                        <a
                            href="{{ route('careers.show', ['slug' => $vacancy->slug]) }}"
                            class="mc-job-card"
                            data-job-card
                            data-title="{{ $vacancy->title }}"
                            data-location="{{ $location }}"
                            data-summary="{{ \Illuminate\Support\Str::limit(strip_tags((string) $summary), 200) }}"
                        >
                            <h2>{{ $vacancy->title }}</h2>
                            <p class="mc-job-meta">
                                <span>{{ config('careers.organization_name') }}</span>
                                @if ($location !== '')
                                    <span class="sep"></span><span>{{ $location }}</span>
                                @endif
                            </p>
                            @if (filled($summary))
                                <p class="mc-job-snippet">{{ \Illuminate\Support\Str::limit(strip_tags((string) $summary), 140) }}</p>
                            @endif
                            <div class="mc-job-footer">
                                @if ($vacancy->employment_type !== null)
                                    <span class="mc-job-badge">{{ $vacancy->employment_type->label() }}</span>
                                @endif
                                @if ($vacancy->department)
                                    <span>{{ $vacancy->department }}</span>
                                @endif
                                @if ($salary)
                                    <span>{{ $salary }}</span>
                                @endif
                                @if ($vacancy->published_at !== null)
                                    <span>{{ __('Posted') }} {{ $vacancy->published_at->diffForHumans() }}</span>
                                @endif
                                <span class="mc-job-cta">{{ __('View job') }} →</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <p class="mc-jobs-no-match" x-ref="noMatch">{{ __('No jobs match your search. Try different keywords or location.') }}</p>
        @endif
</x-public.section>
