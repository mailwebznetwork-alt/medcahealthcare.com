@php
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $content = is_array($settings['content'] ?? null) ? $settings['content'] : [];

    $mindset = array_values(array_filter(array_map(
        static fn (mixed $line): string => trim((string) $line),
        is_array($content['mindset_beliefs'] ?? null) ? $content['mindset_beliefs'] : []
    )));
    $philosophy = array_values(array_filter(array_map(
        static fn (mixed $line): string => trim((string) $line),
        is_array($content['philosophy_lines'] ?? null) ? $content['philosophy_lines'] : []
    )));
    $journey = is_array($content['journey_steps'] ?? null) ? $content['journey_steps'] : [];
    $why = is_array($content['why_blocks'] ?? null) ? $content['why_blocks'] : [];
    $who = array_values(array_filter(array_map(
        static fn (mixed $line): string => trim((string) $line),
        is_array($content['who_we_work_with'] ?? null) ? $content['who_we_work_with'] : []
    )));
    $message = array_values(array_filter(array_map(
        static fn (mixed $line): string => trim((string) $line),
        is_array($content['message_lines'] ?? null) ? $content['message_lines'] : []
    )));
@endphp

<x-public.section class="bg-white">
    <div class="space-y-12">
        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm md:p-8">
            <p class="medca-eyebrow">{{ $content['mindset_eyebrow'] ?? __('The MarkOnMinds Mindset') }}</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">{{ $content['mindset_headline'] ?? __('We Believe Businesses Deserve Better Than Generic Marketing.') }}</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($mindset as $belief)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-relaxed text-slate-700 shadow-sm">{{ $belief }}</div>
                @endforeach
            </div>
            @if (filled($content['mindset_closing'] ?? null))
                <p class="mt-5 max-w-4xl text-sm leading-relaxed text-slate-600 md:text-base">{{ $content['mindset_closing'] }}</p>
            @endif
        </section>

        <section class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-3xl bg-medca-primary p-6 text-white shadow-sm md:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">{{ __('Our Philosophy') }}</p>
                <h2 class="mt-3 text-2xl font-semibold md:text-3xl">{{ $content['philosophy_headline'] ?? __('A Brand Is Not A Logo.') }}</h2>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <div class="space-y-3 text-sm leading-relaxed text-slate-700 md:text-base">
                    @foreach ($philosophy as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm md:p-8">
            <p class="medca-eyebrow">{{ __('The MarkOnMinds Growth Journey') }}</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">{{ $content['journey_headline'] ?? __('Every successful business follows a similar path.') }}</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($journey as $index => $step)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-medca-primary text-sm font-bold text-white">{{ $index + 1 }}</span>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $step['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
            @if (filled($content['journey_closing'] ?? null))
                <p class="mt-5 text-sm leading-relaxed text-slate-600 md:text-base">{{ $content['journey_closing'] }}</p>
            @endif
        </section>

        <section class="space-y-6">
            <div>
                <p class="medca-eyebrow">{{ __('Why Businesses Choose MarkOnMinds') }}</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">{{ $content['why_headline'] ?? __('Because We Think Beyond Marketing.') }}</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($why as $block)
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $block['title'] ?? '' }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600 md:text-base">{{ $block['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <p class="medca-eyebrow">{{ __('Who We Work With') }}</p>
                <ul class="mt-5 space-y-3 text-sm font-semibold text-slate-800 md:text-base">
                    @foreach ($who as $item)
                        <li class="rounded-xl bg-slate-50 px-4 py-3">{{ $item }}</li>
                    @endforeach
                </ul>
                @if (filled($content['who_closing'] ?? null))
                    <p class="mt-5 text-sm leading-relaxed text-slate-600">{{ $content['who_closing'] }}</p>
                @endif
            </div>
            <div class="rounded-3xl bg-[#0a0f1c] p-6 text-white shadow-sm md:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#f5d28a]">{{ __('A Message From MarkOnMinds') }}</p>
                <div class="mt-5 space-y-3 text-sm leading-relaxed text-white/82 md:text-base">
                    @foreach ($message as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-public.section>
