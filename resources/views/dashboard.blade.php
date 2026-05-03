@push('scripts')
    @vite(['resources/js/dashboard.js'])
@endpush

<x-layouts.markonminds
    page-title="Dashboard Overview"
    welcome-line="Welcome back — intelligence surfaces update in near real-time."
>
    <div class="mom-reveal w-full max-w-full space-y-0">
        {{-- Mobile heading --}}
        <div class="space-y-1 pb-8 md:hidden md:pb-0">
            <h1 class="mom-title-page">Dashboard Overview</h1>
            <p class="mom-subtext">Welcome back — intelligence surfaces update in near real-time.</p>
        </div>

        {{-- KPI row --}}
        <section class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total revenue', 'value' => '$482.4k', 'delta' => '+12.4%', 'path' => 'M0,28 L16,24 L32,30 L48,16 L64,22 L80,12 L96,18 L112,8 L120,14'],
                ['label' => 'Active users', 'value' => '18,942', 'delta' => '+4.1%', 'path' => 'M0,22 L18,26 L36,18 L54,24 L72,14 L90,20 L108,10 L120,16'],
                ['label' => 'Conversion', 'value' => '3.28%', 'delta' => '+0.6%', 'path' => 'M0,20 L20,16 L40,24 L58,12 L78,18 L96,8 L120,14'],
                ['label' => 'Avg. session', 'value' => '4m 12s', 'delta' => '−2.1%', 'path' => 'M0,14 L22,20 L42,10 L62,18 L82,8 L102,14 L120,6'],
            ] as $kpi)
                <article class="mom-card mom-card-interactive px-5 py-4">
                    <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                        <span class="mom-micro">{{ $kpi['label'] }}</span>
                        <span class="mom-micro normal-case">({{ $kpi['delta'] }})</span>
                    </div>
                    <p class="mom-metric mt-2 leading-none">{{ $kpi['value'] }}</p>
                    <svg viewBox="0 0 120 36" class="mt-3 h-7 w-full" preserveAspectRatio="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="spark-fill-{{ $loop->index }}" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#d4a95f" stop-opacity="0.2" />
                                <stop offset="100%" stop-color="#1c1613" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path
                            d="{{ $kpi['path'] }} L120,36 L0,36 Z"
                            fill="url(#spark-fill-{{ $loop->index }})"
                            opacity="0.85"
                        />
                        <path
                            d="{{ $kpi['path'] }}"
                            fill="none"
                            stroke="#d4a95f"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            vector-effect="non-scaling-stroke"
                            opacity="0.9"
                        />
                    </svg>
                </article>
            @endforeach
        </section>

        <hr class="mom-section-separator" aria-hidden="true" />

        {{-- Analytics + activity --}}
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="mom-card mom-apex p-6 lg:col-span-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="mom-micro">Performance intelligence</p>
                        <h2 class="mom-section-title mt-2">Analytics overview</h2>
                        <p class="mom-subtext mt-2 max-w-xl">Dual-signal trace across acquisition and retention layers — matte baseline vs illuminated primary curve.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="mom-micro text-[var(--text-secondary)]">FY snapshot</span>
                        <i data-lucide="chevron-right" class="h-4 w-4 text-[var(--text-muted)]"></i>
                    </div>
                </div>
                <div id="mom-chart-analytics" class="mt-6 w-full"></div>
            </div>

            <aside class="mom-card flex flex-col p-6 lg:col-span-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="mom-micro">Live stream</p>
                        <h2 class="mom-section-title mt-2">Real-time activity</h2>
                    </div>
                    <span class="mom-live-pulse rounded-mom-pill border border-[rgba(212,169,95,0.22)] bg-[rgba(212,169,95,0.08)] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-mom-gold">Live</span>
                </div>
                <ul class="mt-6 space-y-5">
                    @foreach ([
                        ['t' => 'Now', 's' => 'Insight pipeline refreshed — cohort delta stable.', 'tone' => 'gold'],
                        ['t' => '2m', 's' => 'New enterprise workspace provisioned.', 'tone' => 'muted'],
                        ['t' => '6m', 's' => 'SEO cluster weights recalibrated.', 'tone' => 'muted'],
                        ['t' => '14m', 's' => 'API latency envelope tightened.', 'tone' => 'muted'],
                        ['t' => '22m', 's' => 'Governance policy mirrored to satellite region.', 'tone' => 'muted'],
                    ] as $item)
                        <li class="flex gap-4">
                            <span class="relative mt-1.5 flex h-2 w-2 shrink-0">
                                <span
                                    class="absolute inset-0 rounded-full {{ $item['tone'] === 'gold' ? 'bg-mom-gold shadow-[0_0_12px_rgba(212,169,95,0.45)]' : 'bg-[rgba(255,255,255,0.12)]' }}"
                                ></span>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">{{ $item['t'] }}</p>
                                <p class="mom-body-text mt-1 leading-relaxed text-[var(--text-secondary)]">{{ $item['s'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </section>

        <hr class="mom-section-separator" aria-hidden="true" />

        {{-- Services, traffic, status --}}
        <section class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="mom-card mom-card-interactive p-6 xl:col-span-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="mom-section-title">Top services</h2>
                    <i data-lucide="layers" class="h-[18px] w-[18px] text-[var(--text-muted)]"></i>
                </div>
                <ul class="mt-6 space-y-5">
                    @foreach ([['Enterprise CX', 92], ['Predictive CRM', 84], ['Signal Fabric', 76], ['Ledger Vault', 68]] as [$name, $pct])
                        <li>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-[var(--text-primary)]">{{ $name }}</span>
                                <span class="mom-subtext">{{ $pct }}%</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[var(--bg-card-track)]">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[rgba(212,169,95,0.25)] to-[#d4a95f] shadow-[0_0_18px_rgba(212,169,95,0.25)]"
                                    style="width: {{ $pct }}%"
                                ></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mom-card mom-apex p-6 xl:col-span-4">
                <h2 class="mom-section-title">Traffic sources</h2>
                <p class="mom-subtext mt-2">Attribution donut — matte core, aurum spectrum slices.</p>
                <div id="mom-chart-traffic" class="mx-auto mt-2 max-w-[280px]"></div>
            </div>

            <div class="mom-card p-6 xl:col-span-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="mom-section-title">System status</h2>
                    <i data-lucide="activity" class="h-[18px] w-[18px] text-[var(--text-muted)]"></i>
                </div>
                <div class="mom-table mt-6 overflow-hidden rounded-mom-md border border-[rgba(255,255,255,0.045)]">
                    <table class="w-full text-left text-[13px]">
                        <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                            <tr>
                                <th class="px-4 py-3 font-medium">Node</th>
                                <th class="px-4 py-3 font-medium">State</th>
                                <th class="px-4 py-3 font-medium text-right">Latency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                            @foreach ([['Edge EU', 'Operational', '42ms', true], ['Core US', 'Operational', '38ms', true], ['Analytics GL', 'Degraded', '118ms', false], ['Failover', 'Standby', '—', true]] as [$node, $state, $ms, $ok])
                                <tr>
                                    <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $node }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $ok && $state !== 'Degraded' ? 'bg-[var(--success)]' : 'bg-[var(--warning)]' }}"></span>
                                            {{ $state }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-[var(--text-muted)]">{{ $ms }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <hr class="mom-section-separator" aria-hidden="true" />

        {{-- Bottom editorial grid — 10-column mathematics: 3 + 3 + 2 + 2 --}}
        <section class="grid grid-cols-1 gap-6 xl:grid-cols-10">
            <div class="mom-card p-6 xl:col-span-3">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="mom-micro">Directory</p>
                        <h2 class="mom-section-title mt-2">Recent users</h2>
                    </div>
                    <button
                        type="button"
                        class="rounded-mom-pill border border-[rgba(255,255,255,0.045)] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]"
                    >
                        View all
                    </button>
                </div>
                <div class="mom-table mt-6 overflow-hidden rounded-mom-md border border-[rgba(255,255,255,0.045)]">
                    <table class="w-full text-left text-[13px]">
                        <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                            <tr>
                                <th class="px-4 py-3 font-medium">User</th>
                                <th class="px-4 py-3 font-medium">Role</th>
                                <th class="px-4 py-3 font-medium text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgba(255,255,255,0.045)]">
                            @foreach ([['Amelia Voss', 'Director', true], ['Jonah Pike', 'Analyst', true], ['Meera Shah', 'Operator', false], ['Leo Marin', 'Architect', true], ['Noor Khalid', 'Reviewer', true]] as [$name, $role, $active])
                                <tr class="text-[var(--text-secondary)]">
                                    <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $name }}</td>
                                    <td class="px-4 py-3">{{ $role }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex items-center gap-2 justify-end">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $active ? 'bg-[var(--success)]' : 'bg-[var(--danger)]' }}"></span>
                                            <span>{{ $active ? 'Active' : 'Suspended' }}</span>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mom-card p-6 xl:col-span-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="orbit" class="h-[18px] w-[18px] text-mom-gold"></i>
                    <h2 class="mom-section-title">AI insights</h2>
                </div>
                <p class="mom-subtext mt-2">Weighted rings — calm intelligence, not spectacle.</p>
                <div class="mt-8 grid grid-cols-2 gap-6">
                    @foreach ([['SEO', 82], ['AIO', 64], ['KWD', 91], ['SPD', 74]] as [$label, $pct])
                        <div class="flex flex-col items-center gap-3">
                            <div
                                class="relative grid h-[92px] w-[92px] place-items-center rounded-full p-[3px]"
                                style="background: conic-gradient(#d4a95f {{ $pct }}%, rgba(255,255,255,0.06) 0);"
                            >
                                <div
                                    class="flex h-full w-full flex-col items-center justify-center rounded-full border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-matte-deep)] shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]"
                                >
                                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[var(--text-muted)]">{{ $label }}</span>
                                    <span class="mom-metric mt-1 text-[22px]">{{ $pct }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mom-card flex flex-col p-6 xl:col-span-2">
                <h2 class="mom-section-title">KPI scorecard</h2>
                <dl class="mt-6 flex flex-1 flex-col justify-between space-y-5">
                    @foreach ([['North star', '0.74', 'QoQ momentum'], ['Retention', '94.2%', 'Rolling 90d'], ['Risk index', 'Low', 'Governance']] as [$k, $v, $sub])
                        <div class="flex items-start justify-between gap-3 border-b border-[rgba(255,255,255,0.045)] pb-5 last:border-0 last:pb-0">
                            <div class="min-w-0">
                                <dt class="text-[13px] font-medium text-[var(--text-primary)]">{{ $k }}</dt>
                                <dd class="mom-subtext mt-1">{{ $sub }}</dd>
                            </div>
                            <dd class="shrink-0 text-base font-semibold tabular-nums text-mom-gold">{{ $v }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="mom-card flex flex-col p-6 xl:col-span-2">
                <h2 class="mom-section-title">Quick actions</h2>
                <div class="mt-6 grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ([['sparkles', 'Compose insight'], ['shield-check', 'Policies'], ['database', 'Connectors'], ['gauge', 'Latency']] as [$icon, $lbl])
                        <button
                            type="button"
                            class="mom-card-interactive flex flex-col items-start gap-2 rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-3 text-left shadow-none transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)]"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-mom-sm border border-[rgba(212,169,95,0.22)] bg-[rgba(212,169,95,0.08)] text-mom-gold">
                                <i data-lucide="{{ $icon }}" class="h-[16px] w-[16px]"></i>
                            </span>
                            <span class="text-[13px] font-medium leading-snug text-[var(--text-primary)]">{{ $lbl }}</span>
                            <span class="mom-micro text-[var(--text-muted)]">Execute</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-layouts.markonminds>
