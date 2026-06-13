@push('scripts')
    @vite(['resources/js/dashboard.js'])
@endpush

@php
    $w = $dashboardWidgets;
    $m = $metrics;
    $sep = false;
    $midCount = (int) $w['site_architect'] + (int) $w['marketing'] + (int) $w['security'];
    $midGrid = match (true) {
        $midCount >= 3 => 'xl:grid-cols-3',
        $midCount === 2 => 'md:grid-cols-2',
        default => 'grid-cols-1',
    };
    $bottomAny = $w['user_management'] || $w['growth_center'] || $w['dashboard'];
@endphp

<x-layouts.markonminds
    page-title="Dashboard Overview"
    welcome-line="Welcome back ? intelligence surfaces reflect your assigned modules."
>
    <div class="mom-reveal w-full max-w-full">
        <div class="space-y-1 pb-8 md:hidden md:pb-0">
            <h1 class="mom-title-page">Dashboard Overview</h1>
            <p class="mom-subtext">Welcome back ? intelligence surfaces reflect your assigned modules.</p>
        </div>

        @if ($w['dashboard'])
            <section class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @if ($m['users_total'] !== null)
                    <a href="{{ route('user-management.index') }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
                        <p class="mom-micro">{{ __('Team members') }}</p>
                        <p class="mom-metric mt-2 leading-none">{{ number_format((int) $m['users_total']) }}</p>
                        <p class="mom-subtext mt-2">{{ __('All accounts in the workspace directory.') }}</p>
                    </a>
                    <a href="{{ route('user-management.index') }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
                        <p class="mom-micro">{{ __('Active accounts') }}</p>
                        <p class="mom-metric mt-2 leading-none">{{ number_format((int) $m['users_active']) }}</p>
                        <p class="mom-subtext mt-2">{{ __('Users with sign-in privileges.') }}</p>
                    </a>
                    <a href="{{ route('user-management.index') }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
                        <p class="mom-micro">{{ __('Verified emails') }}</p>
                        <p class="mom-metric mt-2 leading-none">{{ number_format((int) $m['users_verified']) }}</p>
                        <p class="mom-subtext mt-2">{{ __('Addresses that completed verification.') }}</p>
                    </a>
                    <a href="{{ route('user-management.index') }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
                        <p class="mom-micro">{{ __('Inactive accounts') }}</p>
                        <p class="mom-metric mt-2 leading-none">{{ number_format((int) $m['users_inactive']) }}</p>
                        <p class="mom-subtext mt-2">{{ __('Suspended or deactivated users.') }}</p>
                    </a>
                @else
                    <a href="{{ route('profile.edit') }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline sm:col-span-2">
                        <p class="mom-micro">{{ __('Your session') }}</p>
                        <p
                            id="mom-live-clock"
                            class="mom-metric mt-2 leading-none tabular-nums"
                            data-timezone="Asia/Kolkata"
                            aria-live="polite"
                        >{{ now()->timezone('Asia/Kolkata')->format('M j, H:i:s') }}</p>
                        <p class="mom-subtext mt-2">
                            {{ __('IST') }}
                            @if (auth()->user()->last_login_at)
                                · {{ __('Last sign-in') }}: {{ auth()->user()->last_login_at->timezone('Asia/Kolkata')->format('M j, H:i') }}
                            @else
                                · {{ __('First visit') }}
                            @endif
                        </p>
                    </a>
                    <div class="mom-card mom-card-interactive px-5 py-4 sm:col-span-2">
                        <p class="mom-micro">{{ __('Workspace') }}</p>
                        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                            {{ __('Module access is managed centrally in User Management. Request changes from an administrator if you need additional surfaces.') }}
                        </p>
                    </div>
                @endif
            </section>
            @php $sep = true; @endphp
        @endif

        @if ($w['growth_center'] || $w['operations'])
            @if ($sep)
                <hr class="mom-section-separator" aria-hidden="true" />
            @endif
            @php
                $pair = $w['growth_center'] && $w['operations'];
            @endphp
            <section class="grid grid-cols-1 gap-6 {{ $pair ? 'lg:grid-cols-12' : '' }}">
                @if ($w['growth_center'])
                    <a href="{{ route('growth-center.competitors.index', ['tab' => 'ga4']) }}" class="mom-card mom-apex block p-6 no-underline {{ $pair ? 'lg:col-span-8' : '' }}">
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p class="mom-micro">{{ __('Performance intelligence') }}</p>
                                <h2 class="mom-section-title mt-2">{{ __('Analytics overview') }}</h2>
                                <p class="mom-subtext mt-2 max-w-xl">
                                    {{ __('Charts activate when acquisition and retention signals are connected. No sample data is shown here.') }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-8 rounded-mom-chrome border border-dashed border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.02)] px-6 py-12 text-center text-sm text-[var(--text-muted)]">
                            {{ __('Awaiting connected analytics pipeline.') }}
                        </div>
                    </a>
                @endif

                @if ($w['operations'])
                    <aside class="mom-card flex flex-col p-6 {{ $pair ? 'lg:col-span-4' : '' }}">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="mom-micro">{{ __('Operations') }}</p>
                                <h2 class="mom-section-title mt-2">{{ __('Hiring snapshot') }}</h2>
                            </div>
                        </div>
                        @if ($m['vacancies_total'] !== null)
                            <div class="mt-6 space-y-4">
                                <a href="{{ route('operations.job-portal.vacancies.index') }}" class="mom-backend-hairline-b flex items-center justify-between gap-3 pb-4 no-underline transition hover:opacity-90">
                                    <span class="mom-body-text text-[var(--text-secondary)]">{{ __('Vacancies') }}</span>
                                    <span class="mom-metric text-xl">{{ number_format((int) $m['vacancies_total']) }}</span>
                                </a>
                                <a href="{{ route('operations.job-portal.vacancies.index') }}" class="mom-backend-hairline-b flex items-center justify-between gap-3 pb-4 no-underline transition hover:opacity-90">
                                    <span class="mom-body-text text-[var(--text-secondary)]">{{ __('Published') }}</span>
                                    <span class="mom-metric text-xl">{{ number_format((int) $m['vacancies_published']) }}</span>
                                </a>
                                @if ($m['applications_recent'] !== null)
                                    <a href="{{ route('operations.job-portal.applications.index') }}" class="flex items-center justify-between gap-3 no-underline transition hover:opacity-90">
                                        <span class="mom-body-text text-[var(--text-secondary)]">{{ __('Applications (7d)') }}</span>
                                        <span class="mom-metric text-xl">{{ number_format((int) $m['applications_recent']) }}</span>
                                    </a>
                                @endif
                            </div>
                            <a
                                href="{{ route('modules.operations') }}"
                                class="mom-subtext mt-6 inline-flex items-center gap-1 text-mom-gold hover:underline"
                            >{{ __('Open operations') }} <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a>
                        @else
                            <p class="mom-body-text mt-6 text-[var(--text-secondary)]">
                                {{ __('Job portal metrics appear when the hiring workspace is provisioned.') }}
                            </p>
                        @endif
                    </aside>
                @endif
            </section>
            @php $sep = true; @endphp
        @endif

        @if ($midCount > 0)
            @if ($sep)
                <hr class="mom-section-separator" aria-hidden="true" />
            @endif
            <section class="grid grid-cols-1 gap-6 {{ $midGrid }}">
                @if ($w['site_architect'])
                    <a href="{{ route('site-architect.pages.index') }}" class="mom-card mom-card-interactive block p-6 no-underline">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="mom-section-title">{{ __('Site architect') }}</h2>
                            <i data-lucide="layers" class="h-[18px] w-[18px] text-[var(--text-muted)]"></i>
                        </div>
                        <p class="mom-body-text mt-4 text-[var(--text-secondary)]">
                            {{ __('Structure, services, and composition tools open here when the experience blueprint is connected.') }}
                        </p>
                        <span class="mom-subtext mt-6 inline-flex items-center gap-1 text-mom-gold">
                            {{ __('Open workspace') }} <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </span>
                    </a>
                @endif

                @if ($w['marketing'])
                    <a href="{{ route('modules.marketing') }}" class="mom-card mom-apex block p-6 no-underline">
                        <h2 class="mom-section-title">{{ __('Marketing') }}</h2>
                        <p class="mom-subtext mt-2">
                            {{ __('Attribution and campaign intelligence render after acquisition sources are linked.') }}
                        </p>
                        <div class="mt-8 rounded-mom-chrome border border-dashed border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.02)] px-6 py-12 text-center text-sm text-[var(--text-muted)]">
                            {{ __('No campaign telemetry ingested yet.') }}
                        </div>
                    </a>
                @endif

                @if ($w['security'])
                    <a href="{{ route('modules.security') }}" class="mom-card block p-6 no-underline">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="mom-section-title">{{ __('Security posture') }}</h2>
                            <i data-lucide="activity" class="h-[18px] w-[18px] text-[var(--text-muted)]"></i>
                        </div>
                        <p class="mom-body-text mt-4 text-[var(--text-secondary)]">
                            {{ __('Live node tables, latency envelopes, and alert routing will surface when monitoring integrations are configured.') }}
                        </p>
                    </a>
                @endif
            </section>
            @php $sep = true; @endphp
        @endif

        @if ($bottomAny)
            @if ($sep)
                <hr class="mom-section-separator" aria-hidden="true" />
            @endif
            <section class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @if ($w['user_management'])
                    <div class="mom-card p-6 sm:col-span-2 xl:col-span-1">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="mom-micro">{{ __('Directory') }}</p>
                                <h2 class="mom-section-title mt-2">{{ __('Recent users') }}</h2>
                            </div>
                            <a
                                href="{{ route('user-management.index') }}"
                                class="rounded-mom-chrome border border-[rgba(255,255,255,0.045)] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.16)] hover:text-[var(--text-primary)]"
                            >{{ __('Manage users') }}</a>
                        </div>
                        <div class="mom-table mt-6 overflow-hidden rounded-mom-chrome border border-[var(--border-panel-soft)]">
                            <table class="w-full text-left text-[13px]">
                                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">{{ __('User') }}</th>
                                        <th class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                                        <th class="px-4 py-3 font-medium text-right">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                                    @forelse ($m['recent_users'] as $ru)
                                        <tr class="text-[var(--text-secondary)]">
                                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">
                                                <a href="{{ \App\Support\AdminMetricLinks::userManagement($ru) }}" class="text-[var(--text-primary)] hover:text-mom-gold hover:underline">{{ $ru->name }}</a>
                                            </td>
                                            <td class="px-4 py-3">{{ $ru->role_label ?: '?' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ \App\Support\AdminMetricLinks::userManagement($ru) }}" class="inline-flex items-center gap-2 justify-end hover:text-mom-gold">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $ru->is_active ? 'bg-[var(--success)]' : 'bg-[var(--danger)]' }}"></span>
                                                    <span>{{ $ru->is_active ? __('Active') : __('Inactive') }}</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-[var(--text-muted)]">{{ __('No users yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($w['growth_center'])
                    <a href="{{ route('growth-center.competitors.index', ['tab' => 'competitors']) }}" class="mom-card block p-6 no-underline sm:col-span-2 xl:col-span-1">
                        <div class="flex items-center gap-2">
                            <i data-lucide="orbit" class="h-[18px] w-[18px] text-mom-gold"></i>
                            <h2 class="mom-section-title">{{ __('Experimentation') }}</h2>
                        </div>
                        <p class="mom-subtext mt-2">
                            {{ __('Ring scorecards populate when experimentation programs emit outcomes.') }}
                        </p>
                        <div class="mt-8 rounded-mom-chrome border border-dashed border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.02)] px-6 py-10 text-center text-sm text-[var(--text-muted)]">
                            {{ __('No active experiments recorded.') }}
                        </div>
                    </a>
                @endif

                @if ($w['growth_center'])
                    <a href="{{ route('growth-center.competitors.index', ['tab' => 'readiness']) }}" class="mom-card flex flex-col p-6 no-underline sm:col-span-2 xl:col-span-1">
                        <h2 class="mom-section-title">{{ __('North-star readiness') }}</h2>
                        <p class="mom-body-text mt-3 flex-1 text-[var(--text-secondary)]">
                            {{ __('KPI scorecards stay empty until leadership metrics are wired from your analytics warehouse.') }}
                        </p>
                    </a>
                @endif

                @if ($w['dashboard'])
                    <div class="mom-card flex flex-col p-6 sm:col-span-2 xl:col-span-1">
                        <h2 class="mom-section-title">{{ __('Shortcuts') }}</h2>
                        <div class="mt-6 grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                            @if ($w['user_management'])
                                <a
                                    href="{{ route('user-management.index') }}"
                                    class="mom-card-interactive flex flex-col items-start gap-2 rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-3 text-left shadow-none transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.16)]"
                                >
                                    <span class="flex h-9 w-9 items-center justify-center rounded-mom-chrome border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.08)] text-mom-gold">
                                        <i data-lucide="users-round" class="h-[16px] w-[16px]"></i>
                                    </span>
                                    <span class="text-[13px] font-medium leading-snug text-[var(--text-primary)]">{{ __('User management') }}</span>
                                    <span class="mom-micro text-[var(--text-muted)]">{{ __('Directory & access') }}</span>
                                </a>
                            @endif
                            @if ($w['operations'])
                                <a
                                    href="{{ route('modules.operations') }}"
                                    class="mom-card-interactive flex flex-col items-start gap-2 rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-3 text-left shadow-none transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.16)]"
                                >
                                    <span class="flex h-9 w-9 items-center justify-center rounded-mom-chrome border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.08)] text-mom-gold">
                                        <i data-lucide="workflow" class="h-[16px] w-[16px]"></i>
                                    </span>
                                    <span class="text-[13px] font-medium leading-snug text-[var(--text-primary)]">{{ __('Operations') }}</span>
                                    <span class="mom-micro text-[var(--text-muted)]">{{ __('Hiring workspace') }}</span>
                                </a>
                            @endif
                            @if ($w['settings'])
                                <a
                                    href="{{ route('settings.integrations') }}"
                                    class="mom-card-interactive flex flex-col items-start gap-2 rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-3 text-left shadow-none transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.16)]"
                                >
                                    <span class="flex h-9 w-9 items-center justify-center rounded-mom-chrome border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.08)] text-mom-gold">
                                        <i data-lucide="settings" class="h-[16px] w-[16px]"></i>
                                    </span>
                                    <span class="text-[13px] font-medium leading-snug text-[var(--text-primary)]">{{ __('Settings') }}</span>
                                    <span class="mom-micro text-[var(--text-muted)]">{{ __('Workspace') }}</span>
                                </a>
                            @endif
                            @if ($w['security'])
                                <a
                                    href="{{ route('modules.security') }}"
                                    class="mom-card-interactive flex flex-col items-start gap-2 rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-3 text-left shadow-none transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.16)]"
                                >
                                    <span class="flex h-9 w-9 items-center justify-center rounded-mom-chrome border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.08)] text-mom-gold">
                                        <i data-lucide="shield-check" class="h-[16px] w-[16px]"></i>
                                    </span>
                                    <span class="text-[13px] font-medium leading-snug text-[var(--text-primary)]">{{ __('Security') }}</span>
                                    <span class="mom-micro text-[var(--text-muted)]">{{ __('Posture') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </section>
            @php $sep = true; @endphp
        @endif

        @if (! $sep)
            <div class="mom-card p-8">
                <h2 class="mom-section-title">{{ __('No dashboard panels enabled') }}</h2>
                <p class="mom-body-text mt-2 max-w-xl text-[var(--text-secondary)]">
                    {{ __('Enable the Dashboard module under User Management to restore operational summaries.') }}
                </p>
            </div>
        @endif
    </div>
</x-layouts.markonminds>
