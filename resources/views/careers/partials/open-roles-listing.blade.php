@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Vacancy>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Vacancy> $vacancies */
    $vacancies = $vacancies ?? collect();
@endphp

<style>
    .mc-jobs-search {
        display: grid;
        gap: 0.75rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }
    @media (min-width: 640px) {
        .mc-jobs-search {
            grid-template-columns: 1fr 1fr auto;
            align-items: end;
        }
    }
    .mc-jobs-search label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.4rem;
    }
    .mc-jobs-search input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        padding: 0.65rem 0.75rem;
        font-size: 1rem;
        color: #0f172a;
        box-sizing: border-box;
    }
    .mc-jobs-search input:focus {
        outline: 2px solid #0046ad;
        border-color: #0046ad;
    }
    .mc-jobs-search button {
        border: none;
        border-radius: 0.5rem;
        background: #0046ad;
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        padding: 0.7rem 1.5rem;
        cursor: pointer;
        white-space: nowrap;
    }
    .mc-jobs-search button:hover {
        background: #001e5c;
    }
    .mc-jobs-count {
        margin: 1.5rem 0 1rem;
        font-size: 0.95rem;
        color: #64748b;
    }
    .mc-jobs-count strong {
        color: #0f172a;
    }
    .mc-jobs-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .mc-job-card {
        display: block;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.25rem;
        text-decoration: none;
        color: inherit;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .mc-job-card:hover {
        border-color: #0046ad;
        box-shadow: 0 4px 16px rgba(0, 70, 173, 0.12);
    }
    .mc-job-card h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: #0046ad;
        line-height: 1.3;
    }
    .mc-job-card:hover h2 {
        text-decoration: underline;
    }
    .mc-job-meta {
        margin: 0.4rem 0 0;
        font-size: 0.875rem;
        color: #64748b;
    }
    .mc-job-meta .sep::before {
        content: "·";
        margin: 0 0.5rem;
        font-weight: bold;
    }
    .mc-job-snippet {
        margin: 0.75rem 0 0;
        font-size: 0.875rem;
        line-height: 1.6;
        color: #334155;
    }
    .mc-job-footer {
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 1rem;
        font-size: 0.8rem;
        color: #64748b;
    }
    .mc-job-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        background: #e0f2fe;
        color: #001e5c;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .mc-job-cta {
        margin-left: auto;
        font-weight: 600;
        color: #0046ad;
        font-size: 0.875rem;
    }
    .mc-jobs-empty {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 0.75rem;
        padding: 3rem 1.5rem;
        text-align: center;
        color: #64748b;
    }
    .mc-jobs-no-match {
        display: none;
        margin-top: 1.5rem;
        text-align: center;
        color: #64748b;
        font-size: 0.95rem;
    }
    .mc-jobs-no-match.is-visible {
        display: block;
    }
</style>

<section
    class="w-full bg-slate-100 px-4 py-10 md:px-8 md:py-12 lg:px-12"
    aria-label="{{ __('Open positions') }}"
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
    <div class="mx-auto max-w-4xl">
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
                        $location = collect([$vacancy->area, $vacancy->city, $vacancy->pin_code])->filter()->implode(', ');
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
    </div>
</section>
