<div class="space-y-8">
    @if ($flash)
        <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-primary)]" role="status">
            {{ $flash }}
        </div>
    @endif

    <div class="flex flex-wrap gap-2 border-b border-[var(--border-panel-soft)] pb-4">
        @foreach ([
            'overview' => __('Overview'),
            'google-ads' => __('Google Ads'),
            'meta' => __('Meta Ads'),
            'communication' => __('Communication'),
            'campaigns' => __('Campaigns'),
            'insights' => __('Insights'),
        ] as $key => $label)
            <button
                type="button"
                wire:click="$set('tab', '{{ $key }}')"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition duration-320 ease-premium {{ $tab === $key ? 'bg-[rgba(197,160,89,0.15)] text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]' }}"
            >
                {{ $label }}
            </button>
        @endforeach
        <button
            type="button"
            wire:click="refreshData"
            class="ml-auto rounded-lg border border-[var(--border-panel-soft)] px-3 py-1.5 text-sm text-[var(--text-muted)] hover:border-[rgba(197,160,89,0.25)] hover:text-[var(--text-primary)]"
        >
            {{ __('Refresh data') }}
        </button>
    </div>

    @if ($tab === 'overview')
        @php
            $sum = $ga4Bundle['summary'] ?? [];
            $ga4Err = $ga4Bundle['error'] ?? null;
        @endphp
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Users (28d)') }}</p>
                <p class="mom-metric mt-2">{{ number_format((int) ($sum['users'] ?? 0)) }}</p>
                <p class="mom-subtext mt-1">{{ __('GA4 active users') }}</p>
            </article>
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Sessions') }}</p>
                <p class="mom-metric mt-2">{{ number_format((int) ($sum['sessions'] ?? 0)) }}</p>
                <p class="mom-subtext mt-1">{{ __('Traffic depth') }}</p>
            </article>
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Conversions') }}</p>
                <p class="mom-metric mt-2">{{ number_format((int) ($sum['conversions'] ?? 0)) }}</p>
                <p class="mom-subtext mt-1">{{ __('Attributed in GA4') }}</p>
            </article>
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ __('Conversion rate') }}</p>
                <p class="mom-metric mt-2">{{ isset($sum['conversion_rate']) ? number_format((float) $sum['conversion_rate'], 2).'%' : '—' }}</p>
                <p class="mom-subtext mt-1">{{ __('Conversions / sessions') }}</p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="mom-card p-5">
                <h3 class="mom-section-title text-base">{{ __('GA4') }}</h3>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                    {{ $ga4Err ? __('API: :msg', ['msg' => $ga4Err]) : __('Measurement + Data API reports linked.') }}
                </p>
            </article>
            <article class="mom-card p-5">
                <h3 class="mom-section-title text-base">{{ __('Google Ads') }}</h3>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                    {{ ($googleAds['configured'] ?? false) ? __('Optional API seam configured — layer campaign spend here.') : __('Use GA4 for click/conversion signals until Ads API is wired.') }}
                </p>
            </article>
            <article class="mom-card p-5">
                <h3 class="mom-section-title text-base">{{ __('Meta Ads') }}</h3>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                    {{ ($metaAds['configured'] ?? false) ? __('Marketing API reachable.') : __('Add token + ad account for reach/clicks/leads.') }}
                </p>
            </article>
            <article class="mom-card p-5">
                <h3 class="mom-section-title text-base">{{ __('WhatsApp') }}</h3>
                <p class="mom-micro mt-2">{{ __('Clicks (events)') }}</p>
                <p class="mom-metric mt-1">{{ number_format($whatsappClicks) }}</p>
            </article>
            <article class="mom-card p-5">
                <h3 class="mom-section-title text-base">{{ __('GMB (manual)') }}</h3>
                @php $gmb = $snapshots->firstWhere('channel', 'gmb'); @endphp
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                    @if ($gmb)
                        {{ __('Latest snapshot: views :v · calls :c', ['v' => number_format((int) data_get($gmb->metrics, 'views', 0)), 'c' => number_format((int) data_get($gmb->metrics, 'calls', 0))]) }}
                    @else
                        {{ __('Add a GMB snapshot under Communication.') }}
                    @endif
                </p>
            </article>
            <article class="mom-card p-5">
                <h3 class="mom-section-title text-base">{{ __('Email / SMS') }}</h3>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                    {{ __('Manual snapshots + open pixels — see Communication tab.') }}
                </p>
            </article>
        </section>

    @endif

    @if ($tab === 'google-ads')
        <section class="space-y-4">
            @if ($googleAds['note'] ?? null)
                <p class="mom-body-text text-[var(--text-secondary)]">{{ $googleAds['note'] }}</p>
            @endif
            <div class="mom-card overflow-hidden p-0">
                <table class="min-w-full text-sm">
                    <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                        <tr>
                            <th class="px-4 py-2">{{ __('Campaign') }}</th>
                            <th class="px-4 py-2">{{ __('Clicks') }}</th>
                            <th class="px-4 py-2">{{ __('Cost') }}</th>
                            <th class="px-4 py-2">{{ __('Conversions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($googleAds['campaigns'] ?? [] as $c)
                            <tr class="border-t border-[var(--border-panel-soft)]">
                                <td class="px-4 py-2">{{ $c['name'] ?? '—' }}</td>
                                <td class="px-4 py-2">{{ number_format((int) ($c['clicks'] ?? 0)) }}</td>
                                <td class="px-4 py-2">{{ isset($c['cost']) ? number_format((float) $c['cost'], 2) : '—' }}</td>
                                <td class="px-4 py-2">{{ isset($c['conversions']) ? number_format((float) $c['conversions'], 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No campaign rows — configure API or rely on GA4 conversion events.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'meta')
        <section class="space-y-4">
            @if ($metaAds['note'] ?? null)
                <p class="mom-body-text text-[var(--text-secondary)]">{{ $metaAds['note'] }}</p>
            @endif
            <div class="mom-card overflow-hidden p-0">
                <table class="min-w-full text-sm">
                    <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                        <tr>
                            <th class="px-4 py-2">{{ __('Campaign') }}</th>
                            <th class="px-4 py-2">{{ __('Reach') }}</th>
                            <th class="px-4 py-2">{{ __('Clicks') }}</th>
                            <th class="px-4 py-2">{{ __('Leads') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($metaAds['campaigns'] ?? [] as $c)
                            <tr class="border-t border-[var(--border-panel-soft)]">
                                <td class="px-4 py-2">{{ $c['name'] ?? '—' }}</td>
                                <td class="px-4 py-2">{{ number_format((int) ($c['reach'] ?? 0)) }}</td>
                                <td class="px-4 py-2">{{ number_format((int) ($c['clicks'] ?? 0)) }}</td>
                                <td class="px-4 py-2">{{ number_format((int) ($c['leads'] ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No insights rows.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'communication')
        <section class="grid gap-8 lg:grid-cols-2">
            <div class="mom-card p-6">
                <h3 class="mom-section-title">{{ __('WhatsApp (GA4)') }}</h3>
                <p class="mom-metric mt-4">{{ number_format($whatsappClicks) }}</p>
                <p class="mom-subtext mt-1">{{ __('`whatsapp_click` events (28d)') }}</p>
            </div>
            <div class="mom-card p-6">
                <h3 class="mom-section-title">{{ __('Manual snapshot') }}</h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mom-micro">{{ __('Channel') }}</label>
                        <select wire:model.live="snapshot_channel" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                            <option value="sms">SMS</option>
                            <option value="email">Email</option>
                            <option value="gmb">GMB</option>
                            <option value="whatsapp">{{ __('WhatsApp (ops)') }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mom-micro">{{ __('Period start') }}</label>
                            <input type="date" wire:model="snapshot_period_start" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="mom-micro">{{ __('Period end') }}</label>
                            <input type="date" wire:model="snapshot_period_end" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        </div>
                    </div>
                    @if ($snapshot_channel === 'sms')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mom-micro">{{ __('Sent') }}</label>
                                <input type="number" wire:model="sms_sent" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="mom-micro">{{ __('Delivered') }}</label>
                                <input type="number" wire:model="sms_delivered" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                            </div>
                        </div>
                    @elseif ($snapshot_channel === 'email')
                        <div class="space-y-3">
                            <div>
                                <label class="mom-micro">{{ __('Sent') }}</label>
                                <input type="number" wire:model="email_sent" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mom-micro">{{ __('Open rate %') }}</label>
                                    <input type="text" wire:model="email_open_rate" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="mom-micro">{{ __('CTR %') }}</label>
                                    <input type="text" wire:model="email_ctr" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                                </div>
                            </div>
                        </div>
                    @elseif ($snapshot_channel === 'gmb')
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="mom-micro">{{ __('Views') }}</label><input type="number" wire:model="gmb_views" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" /></div>
                            <div><label class="mom-micro">{{ __('Calls') }}</label><input type="number" wire:model="gmb_calls" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" /></div>
                            <div><label class="mom-micro">{{ __('Directions') }}</label><input type="number" wire:model="gmb_directions" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" /></div>
                            <div><label class="mom-micro">{{ __('Reviews') }}</label><input type="number" wire:model="gmb_reviews" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" /></div>
                        </div>
                    @else
                        <div>
                            <label class="mom-micro">{{ __('Conversations (manual)') }}</label>
                            <input type="number" wire:model="whatsapp_conversations" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        </div>
                    @endif
                    <button type="button" wire:click="saveCommunicationSnapshot" class="rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">
                        {{ __('Save snapshot') }}
                    </button>
                </div>
            </div>
            <div class="mom-card p-6">
                <h3 class="mom-section-title">{{ __('Email open pixel') }}</h3>
                <p class="mom-subtext mt-2">{{ __('1×1 GIF tracker — embed in HTML campaigns.') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <input type="text" wire:model="email_tracker_label" placeholder="{{ __('Label (optional)') }}" class="min-w-[12rem] flex-1 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    <button type="button" wire:click="createEmailTracker" class="rounded-lg border border-[var(--border-panel-soft)] px-4 py-2 text-sm hover:border-[rgba(197,160,89,0.25)]">{{ __('Create pixel') }}</button>
                </div>
                <ul class="mom-body-text mt-4 space-y-2 text-[var(--text-secondary)]">
                    @foreach ($emailTrackers as $tr)
                        <li class="break-all text-xs font-mono">{{ route('marketing.email-open-pixel', $tr->token) }} @if($tr->label) — {{ $tr->label }} @endif ({{ __('opens') }}: {{ $tr->open_count }})</li>
                    @endforeach
                </ul>
            </div>
        </section>
        <div class="mom-card overflow-hidden p-0">
            <h3 class="border-b border-[var(--border-panel-soft)] px-4 py-3 text-sm font-semibold">{{ __('Recent snapshots') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Channel') }}</th><th class="px-4 py-2">{{ __('Period') }}</th><th class="px-4 py-2">{{ __('Metrics') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($snapshots as $s)
                        <tr class="border-t border-[var(--border-panel-soft)]">
                            <td class="px-4 py-2">{{ strtoupper($s->channel) }}</td>
                            <td class="px-4 py-2">{{ $s->period_start?->format('Y-m-d') }} — {{ $s->period_end?->format('Y-m-d') }}</td>
                            <td class="max-w-xl px-4 py-2 font-mono text-xs">{{ json_encode($s->metrics) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($tab === 'campaigns')
        <section class="mom-card p-6">
            <h3 class="mom-section-title">{{ __('Campaign tracker') }}</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <input type="text" wire:model="campaign_name" placeholder="{{ __('Name') }}" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                <select wire:model="campaign_platform" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                    <option value="google_ads">Google Ads</option>
                    <option value="meta">Meta</option>
                    <option value="ga4">GA4</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="email">Email</option>
                    <option value="other">{{ __('Other') }}</option>
                </select>
                <input type="number" step="0.01" wire:model="campaign_budget" placeholder="{{ __('Budget') }}" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                <select wire:model="campaign_status" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="paused">{{ __('Paused') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                </select>
            </div>
            <button type="button" wire:click="saveCampaign" class="mt-4 rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Add campaign') }}</button>
        </section>
        <div class="mom-card overflow-hidden p-0">
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr>
                        <th class="px-4 py-2">{{ __('Name') }}</th>
                        <th class="px-4 py-2">{{ __('Platform') }}</th>
                        <th class="px-4 py-2">{{ __('Budget') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaigns as $c)
                        <tr class="border-t border-[var(--border-panel-soft)]">
                            <td class="px-4 py-2">{{ $c->name }}</td>
                            <td class="px-4 py-2">{{ $c->platform }}</td>
                            <td class="px-4 py-2">{{ $c->budget !== null ? number_format((float) $c->budget, 2) : '—' }}</td>
                            <td class="px-4 py-2">{{ $c->status }}</td>
                            <td class="px-4 py-2 text-right">
                                <button type="button" wire:click="deleteCampaign({{ $c->id }})" wire:confirm="{{ __('Remove this campaign?') }}" class="text-[var(--danger)] hover:underline">{{ __('Remove') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($tab === 'insights')
        <section class="space-y-6">
            <div class="mom-card p-6">
                <h3 class="mom-section-title">{{ __('Rule-based insights') }}</h3>
                <ul class="mom-body-text mt-4 list-disc space-y-2 pl-5 text-[var(--text-secondary)]">
                    @forelse ($insights as $i)
                        <li>{{ $i['message'] }}</li>
                    @empty
                        <li>{{ __('Not enough signals yet — connect GA4 Data API or add manual snapshots.') }}</li>
                    @endforelse
                </ul>
            </div>
            @if ($geminiNarrative)
                <div class="mom-card p-6">
                    <h3 class="mom-section-title">{{ __('Gemini summary') }}</h3>
                    <p class="mom-body-text mt-4 whitespace-pre-wrap text-[var(--text-secondary)]">{{ $geminiNarrative }}</p>
                    <p class="mom-micro mt-4">{{ __('Uses config(gemini.api_key); cached 1 hour.') }}</p>
                </div>
            @endif
        </section>
    @endif
</div>
