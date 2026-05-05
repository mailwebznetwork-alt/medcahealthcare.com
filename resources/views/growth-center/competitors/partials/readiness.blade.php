@php
    /** @var array<string, mixed> $growthReadinessReport */
    $report = $growthReadinessReport ?? [];
    $health = $report['health_row'] ?? [];

    $scoreBarWidth = static function (int $score): string {
        return min(100, max(0, $score)).'%';
    };

    $scoreBarClass = static function (int $score): string {
        if ($score >= 80) {
            return 'bg-[var(--success)]';
        }
        if ($score >= 55) {
            return 'bg-[var(--warning)]';
        }

        return 'bg-[var(--danger)]';
    };

    $badgeClass = static function (string $status): string {
        return match ($status) {
            'pass' => 'border-[rgba(98,195,112,0.45)] bg-[rgba(98,195,112,0.12)] text-[var(--success)]',
            'warn' => 'border-[rgba(226,184,92,0.45)] bg-[rgba(226,184,92,0.1)] text-[var(--warning)]',
            default => 'border-[rgba(226,92,92,0.45)] bg-[rgba(226,92,92,0.1)] text-[var(--danger)]',
        };
    };

    $badgeLabel = static function (string $status): string {
        return match ($status) {
            'pass' => __('OK'),
            'warn' => __('IMPROVE'),
            default => __('FIX'),
        };
    };
@endphp

<section class="mom-card mb-6 border border-[rgba(98,195,112,0.22)] bg-[rgba(98,195,112,0.06)] p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="mom-section-title">{{ __('Marketing & discovery integrations') }}</h2>
            <p class="mom-body-text mt-2 max-w-2xl text-[var(--text-secondary)]">
                {{ __('GA4, GTM, Meta, Clarity, CAPI, Gemini — configure under Admin settings or Marketing; saved integrations override defaults for this deploy.') }}
            </p>
        </div>
        @if (Route::has('admin.settings.integrations.index'))
            <a href="{{ route('admin.settings.integrations.index') }}" class="mom-cta-primary shrink-0 !px-4 !py-2.5 !text-xs">{{ __('Open Integrations') }}</a>
        @endif
    </div>
</section>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4" role="list" aria-label="{{ __('Growth health scores') }}">
    @foreach ($health as $h)
        <a
            href="{{ $h['href'] ?? '#' }}"
            role="listitem"
            class="mom-card group flex min-h-[112px] flex-col p-4 transition hover:border-[rgba(197,160,89,0.35)]"
        >
            <p class="mom-micro text-mom-gold">{{ __($h['label'] ?? '') }}</p>
            <p class="mom-metric mt-2 leading-none tabular-nums">
                {{ (int) ($h['score'] ?? 0) }}<span class="mom-subtext font-medium">/100</span>
            </p>
            <p class="mom-subtext mt-2 flex-1 leading-snug">{{ __($h['blurb'] ?? '') }} <span class="text-mom-gold opacity-80">→</span></p>
        </a>
    @endforeach
</div>

<section class="mom-card mb-6 p-5">
    <p class="mom-body-text max-w-3xl text-[var(--text-secondary)]">
        {{ __('Scores reflect config, Growth SEO forms, Marketing settings, and enabled Integrations — not live Google or Meta consoles. Re-check after launches and campaigns.') }}
    </p>
</section>

<section class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="mom-card flex min-h-[220px] flex-col bg-[var(--bg-card-matte)] p-6 lg:col-span-1">
        <p class="mom-micro text-mom-gold">{{ __('Overall readiness') }}</p>
        <p class="mom-metric mt-3 text-5xl tabular-nums sm:text-6xl">{{ (int) ($report['overall_score'] ?? 0) }}</p>
        <p class="mom-body-text mt-3 flex-1 text-[var(--text-secondary)]">
            {{ __('Average of SEO (incl. discovery) and tracking. Deep GA4 / Meta: use Growth tabs (GA4, Marketing).') }}
        </p>
    </div>
    <div class="lg:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($report['sections'] ?? [] as $section)
            <div class="mom-card flex flex-col p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="mom-section-title">{{ __($section['label'] ?? '') }}</h2>
                    <span class="mom-metric text-2xl tabular-nums">{{ (int) ($section['score'] ?? 0) }}</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-[var(--bg-card-track)]">
                    <div
                        class="h-full rounded-full {{ $scoreBarClass((int) ($section['score'] ?? 0)) }}"
                        style="width: {{ $scoreBarWidth((int) ($section['score'] ?? 0)) }}"
                    ></div>
                </div>
                <ul class="mom-scrollbar-main mt-4 max-h-64 space-y-2.5 overflow-y-auto pr-1 text-[13px]">
                    @foreach ($section['items'] ?? [] as $item)
                        @php $st = (string) ($item['status'] ?? ''); @endphp
                        <li class="flex gap-2">
                            <span class="inline-flex shrink-0 items-center justify-center self-start rounded border px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $badgeClass($st) }}">
                                {{ $badgeLabel($st) }}
                            </span>
                            <div class="min-w-0">
                                <span class="font-medium text-[var(--text-primary)]">{{ __($item['label'] ?? '') }}</span>
                                <p class="text-[var(--text-muted)]">{{ __($item['detail'] ?? '') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>

<section class="mb-6">
    <h2 class="mom-section-title mb-3 flex items-center">
        <span class="mr-2 h-7 w-1 rounded bg-mom-gold"></span>
        {{ __('Suggestions & next steps') }}
    </h2>
    <div class="mom-card divide-y divide-[var(--border-panel-soft)] overflow-hidden p-0">
        @forelse ($report['suggestions'] ?? [] as $s)
            <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex min-w-0 items-start gap-2">
                    @if (($s['priority'] ?? '') === 'high')
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[var(--danger)]" title="{{ __('High') }}"></span>
                    @elseif (($s['priority'] ?? '') === 'medium')
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[var(--warning)]" title="{{ __('Medium') }}"></span>
                    @else
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[var(--text-muted)]" title="{{ __('Low') }}"></span>
                    @endif
                    @if (! empty($s['href']))
                        <a href="{{ $s['href'] }}" class="text-left text-sm leading-snug text-[var(--text-primary)] hover:text-mom-gold sm:pr-2">
                            {{ __($s['text'] ?? '') }}
                        </a>
                    @else
                        <p class="text-sm text-[var(--text-primary)]">{{ __($s['text'] ?? '') }}</p>
                    @endif
                </div>
                @if (! empty($s['href']))
                    <a href="{{ $s['href'] }}" class="shrink-0 text-sm font-semibold text-[var(--success)] hover:underline" aria-hidden="true">→</a>
                @endif
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-[var(--text-muted)]">{{ __('No open items — still verify GTM Preview and GA4 after deploy.') }}</p>
        @endforelse
    </div>
</section>
