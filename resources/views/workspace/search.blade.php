@php
    $q = $q ?? '';
    $groups = $groups ?? [];
    $queryTooShort = $q === '' || mb_strlen($q) < 2;
@endphp

<x-layouts.markonminds
    page-title="{{ __('Workspace search') }}"
    welcome-line="{{ __('Results respect your module access.') }}"
>
    <div class="mom-reveal w-full max-w-5xl space-y-8">
        <div class="space-y-1 pb-4 md:hidden">
            <h1 class="mom-title-page">{{ __('Workspace search') }}</h1>
            <p class="mom-subtext">{{ __('Results respect your module access.') }}</p>
        </div>

        @if ($queryTooShort)
            <p class="mom-card px-5 py-6 mom-body-text">
                {!! __('Type at least :num characters in the top bar search field, then press Enter.', ['num' => '<strong class="text-[var(--text-primary)]">2</strong>']) !!}
            </p>
        @elseif (count($groups) === 0)
            <p class="mom-card border border-[rgba(226,184,92,0.25)] bg-[rgba(226,184,92,0.06)] px-5 py-6 mom-body-text text-[var(--warning)]">
                {{ __('No matches in modules you can access.') }}
            </p>
        @else
            <div class="space-y-10">
                @foreach ($groups as $group)
                    <section>
                        <h2 class="mom-section-title border-b border-[color:var(--border-tabstrip-divider)] pb-2">{{ $group['heading'] }}</h2>
                        <ul class="mom-card mt-4 divide-y divide-[var(--border-panel-soft)] overflow-hidden p-0">
                            @foreach ($group['items'] as $item)
                                <li>
                                    <a
                                        href="{{ $item['url'] }}"
                                        class="flex flex-col gap-0.5 px-4 py-3 transition hover:bg-[var(--bg-hover)] sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <span class="font-semibold text-[var(--text-primary)]">{{ $item['title'] }}</span>
                                        <span class="mom-subtext sm:max-w-md sm:text-right">{{ $item['subtitle'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.markonminds>
