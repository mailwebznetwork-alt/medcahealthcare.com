<x-app-layout
    :page-title="$title"
    :welcome-line="__('Operational workspace for this module.')"
>
    @if ($moduleKey === \App\ModuleAccess::SECURITY)
        <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Failed Logins') }}</p>
                <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($securityMetrics['failed_login_attempts'] ?? 0)) }}</p>
                <p class="mom-subtext mt-2">{{ __('Total failed login attempts recorded.') }}</p>
            </article>
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Role Denials') }}</p>
                <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($securityMetrics['role_denials'] ?? 0)) }}</p>
                <p class="mom-subtext mt-2">{{ __('Role-based access violations blocked.') }}</p>
            </article>
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Session Timeouts') }}</p>
                <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($securityMetrics['session_timeouts'] ?? 0)) }}</p>
                <p class="mom-subtext mt-2">{{ __('Auto-logout timeout events triggered.') }}</p>
            </article>
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Upload Rejections') }}</p>
                <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($securityMetrics['upload_validation_failures'] ?? 0)) }}</p>
                <p class="mom-subtext mt-2">{{ __('Invalid uploads blocked by validation.') }}</p>
            </article>
        </section>

        <section class="mom-card mt-8 p-6">
            <h2 class="mom-section-title">{{ __('Failed Login Attempts by IP') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[24rem] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('IP Address') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Failures') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                        @forelse ($failedLoginByIp as $row)
                            <tr>
                                <td class="px-4 py-3 font-mono">{{ $row->ip_address }}</td>
                                <td class="px-4 py-3 text-right text-[var(--text-primary)]">{{ number_format((int) $row->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No failed login attempts recorded yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mom-card mt-8 p-6">
            <h2 class="mom-section-title">{{ __('Recent Security Events') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[42rem] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Action') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Module') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('IP') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Description') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Timestamp') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                        @forelse ($recentSecurityEvents as $event)
                            <tr>
                                <td class="px-4 py-3 font-mono text-[var(--text-primary)]">{{ $event->action }}</td>
                                <td class="px-4 py-3">{{ $event->module }}</td>
                                <td class="px-4 py-3 font-mono">{{ $event->ip_address ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $event->description ?? '—' }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($event->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No security events available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="mom-card p-8">
            <h1 class="mom-title-page">{{ $title }}</h1>
            <p class="mom-body-text mt-3 max-w-2xl text-[var(--text-secondary)]">
                {{ __('This area is provisioned for your account. Connect data sources and automation from your administration tools when you are ready.') }}
            </p>
        </div>
    @endif
</x-app-layout>
