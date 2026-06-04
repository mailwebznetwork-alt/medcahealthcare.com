@props([
    'compact' => false,
])

@php
    $steps = \App\Support\SiteArchitectUxCopy::composeJourneySteps();
@endphp

<aside
    class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.05)] px-4 py-4"
    aria-label="{{ __('How to publish a page') }}"
>
    <p class="text-sm font-semibold text-mom-gold">{{ __('Quick path: Page → Section → Content → Preview → Live') }}</p>
    @unless ($compact)
        <p class="mom-subtext mt-1 max-w-3xl text-sm">{{ __('Advanced block tools are optional — everyday updates use Pages and Blocks Studio only.') }}</p>
    @endunless
    <ol class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($steps as $item)
            <li class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] px-3 py-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-mom-gold">{{ __('Step :n', ['n' => $item['step']]) }}</span>
                <p class="mt-1 text-sm font-semibold text-[var(--text-primary)]">{{ $item['title'] }}</p>
                <p class="mom-subtext mt-1 text-xs leading-relaxed">{{ $item['hint'] }}</p>
            </li>
        @endforeach
    </ol>
</aside>
