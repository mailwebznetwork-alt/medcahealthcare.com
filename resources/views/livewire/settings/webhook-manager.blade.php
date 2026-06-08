<div class="space-y-8">
    <section class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Webhook Manager') }}</h2>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
            {{ __('Real-time outbound HTTP notifications: system events → signed payloads → your endpoints, with retries and delivery logs (architecture: listener → trigger → payload → transport → logs).') }}
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="startCreate" class="mom-cta-primary mom-cta-compact">
                {{ __('Add endpoint') }}
            </button>
        </div>
    </section>

    @if ($showEndpointForm)
        <section class="mom-card p-6" wire:key="webhook-form">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <h3 class="mom-section-title">{{ $editingId ? __('Edit endpoint') : __('New endpoint') }}</h3>
                <button type="button" wire:click="cancelEdit" class="mom-cta-ghost mom-cta-compact">{{ __('Cancel') }}</button>
            </div>
            <form wire:submit.prevent="save" class="mt-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Webhook name') }}</span>
                        <input type="text" wire:model.blur="name" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" autocomplete="off">
                        @error('name') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Sort order') }}</span>
                        <input type="number" wire:model.blur="sort_order" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" min="0">
                        @error('sort_order') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>
                </div>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Target URL') }}</span>
                    <input type="url" wire:model.blur="target_url" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" autocomplete="off">
                    @error('target_url') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('HTTP method') }}</span>
                        <select wire:model.live="http_method" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            @foreach (['POST', 'GET', 'PUT', 'PATCH'] as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                        @error('http_method') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Timeout (seconds)') }}</span>
                        <input type="number" wire:model.blur="timeout_seconds" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" min="1" max="120">
                        @error('timeout_seconds') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Max retries') }}</span>
                        <input type="number" wire:model.blur="max_retries" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" min="1" max="10">
                        @error('max_retries') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>
                </div>
                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-[13px] text-[var(--text-secondary)]">
                        <input type="checkbox" wire:model.live="is_enabled" class="rounded border border-[rgba(255,255,255,0.2)]">
                        {{ __('Enabled') }}
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-[13px] text-[var(--text-secondary)]">
                        <input type="checkbox" wire:model.live="enforce_https" class="rounded border border-[rgba(255,255,255,0.2)]">
                        {{ __('Require HTTPS URL') }}
                    </label>
                </div>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Secret (HMAC)') }} — {{ $editingId ? __('leave blank to keep current') : __('optional') }}</span>
                    <input type="password" wire:model.blur="secret_input" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" autocomplete="new-password">
                    @error('secret_input') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Bearer token header') }} — {{ __('optional') }}</span>
                    <input type="password" wire:model.blur="auth_bearer_input" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" autocomplete="new-password">
                    @error('auth_bearer_input') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>
                <div>
                    <span class="mom-micro mb-2 block">{{ __('Subscribe to events') }}</span>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($catalogEvents as $row)
                            <label class="inline-flex cursor-pointer items-center gap-2 text-[13px] text-[var(--text-secondary)]">
                                <input type="checkbox" wire:model="selected_events" value="{{ $row['key'] }}" class="rounded border border-[rgba(255,255,255,0.2)]">
                                <span class="font-mono text-[12px]">{{ $row['key'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selected_events') <p class="mt-1 text-[12px] text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Custom JSON payload template') }} — {{ __('optional; blank = default envelope') }}</span>
                    <textarea wire:model.blur="payload_template" rows="6" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-[12px] text-[var(--text-primary)]" placeholder="{{ __('Valid JSON; use double-brace placeholders like event and payload_json.') }}"></textarea>
                    @error('payload_template') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Conditional mapping rules (JSON)') }} — {{ __('optional; default/include_only/exclude/rename per event') }}</span>
                    <textarea wire:model.blur="mapping_rules_json" rows="5" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-[12px] text-[var(--text-primary)]"></textarea>
                    @error('mapping_rules_json') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Destination IP allowlist (CIDR per line)') }} — {{ __('optional; TLS host resolved IPs must match') }}</span>
                    <textarea wire:model.blur="allowed_cidrs_text" rows="3" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-[12px] text-[var(--text-primary)]" placeholder="203.0.113.0/24"></textarea>
                    @error('allowed_cidrs_text') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2 text-[13px] text-[var(--text-secondary)]">
                    <input type="checkbox" wire:model.live="verify_ssl" class="rounded border border-[rgba(255,255,255,0.2)]">
                    {{ __('Verify TLS certificates when connecting') }}
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Custom headers (JSON object)') }}</span>
                    <textarea wire:model.blur="custom_headers_json" rows="4" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-[12px] text-[var(--text-primary)]"></textarea>
                    @error('custom_headers_json') <span class="text-[12px] text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Save endpoint') }}</button>
                </div>
            </form>
        </section>
    @endif

    <section class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Endpoints') }}</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[56rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('URL') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Method') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Events') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                    @forelse ($hooks as $hook)
                        <tr wire:key="hook-{{ $hook->id }}">
                            <td class="px-4 py-3">{{ $hook->name }}</td>
                            <td class="px-4 py-3 font-mono text-[11px]">{{ \Illuminate\Support\Str::limit($hook->target_url, 48) }}</td>
                            <td class="px-4 py-3">{{ $hook->http_method }}</td>
                            <td class="px-4 py-3 font-mono text-[11px]">{{ implode(', ', $hook->events ?? []) }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $hook->is_enabled ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}">
                                    {{ $hook->is_enabled ? __('On') : __('Off') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="startEdit({{ $hook->id }})" class="mom-cta-ghost mom-cta-compact">{{ __('Edit') }}</button>
                                    <button type="button" wire:click="toggleEnabled({{ $hook->id }})" class="mom-cta-ghost mom-cta-compact">{{ __('Toggle') }}</button>
                                    <button type="button" wire:click="sendTest({{ $hook->id }})" class="mom-cta-ghost mom-cta-compact">{{ __('Test') }}</button>
                                    <button type="button" wire:click="deleteEndpoint({{ $hook->id }})" wire:confirm="{{ __('Delete this endpoint?') }}" class="mom-cta-ghost mom-cta-compact">{{ __('Delete') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No outbound endpoints yet. Add one or use Integrations → Webhook for a legacy single URL.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Event catalog') }}</h3>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Subscribe endpoints above; when the event fires, subscribed URLs are notified in sort order.') }}</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Event key') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('When it fires') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                    @foreach ($catalogEvents as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-[12px]">{{ $row['key'] }}</td>
                            <td class="px-4 py-3">{{ __($row['description']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Payload & headers') }}</h3>
        <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 text-[var(--text-secondary)]">
            <li>{{ __('Template placeholders use double braces: event, sent_at, payload_json, app_name, environment.') }}</li>
            <li>{{ __('Blank template uses the default JSON envelope (event, payload, sent_at, app, environment).') }}</li>
            <li>{{ __('Custom headers must be a flat JSON object of string values; Authorization Bearer can also be set separately.') }}</li>
        </ul>
    </section>

    <section class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Security') }}</h3>
        <ul class="mom-body-text mt-2 list-inside list-disc space-y-1 text-[var(--text-secondary)]">
            <li>{{ __('Optional HMAC SHA-256 signature in X-Webhook-Signature when a secret is set (body for POST/PUT/PATCH; query signature for GET).') }}</li>
            <li>{{ __('HTTPS requirement toggle rejects non-https targets before send.') }}</li>
            <li>{{ __('Bearer tokens and custom headers for vendor-specific verification.') }}</li>
        </ul>
    </section>

    <section class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Retries & delivery logs') }}</h3>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Failures retry up to your max attempts with backoff; each attempt is logged below.') }}</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[56rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Time') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Endpoint') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Event') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Attempt') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('HTTP') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Result') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('ms') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                    @forelse ($deliveries as $delivery)
                        <tr wire:key="delivery-{{ $delivery->id }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $delivery->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3">{{ $delivery->outboundWebhook?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-[11px]">{{ $delivery->event_key }}</td>
                            <td class="px-4 py-3">{{ $delivery->attempt_number }}</td>
                            <td class="px-4 py-3">{{ $delivery->response_status ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $delivery->success ? 'text-[var(--success)]' : 'text-[var(--danger)]' }}">
                                    {{ $delivery->success ? __('OK') : __('Fail') }}
                                </span>
                                @if ($delivery->error_message)
                                    <span class="block text-[11px] text-[var(--text-muted)]">{{ \Illuminate\Support\Str::limit($delivery->error_message, 120) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $delivery->duration_ms ?? '—' }}
                                <button type="button" wire:click="inspectDelivery({{ $delivery->id }})" class="mom-cta-ghost ml-2 !px-2 !py-1 !text-[10px]">{{ __('Inspect') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No deliveries logged yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $deliveries->links() }}
        </div>
        @if ($inspectedDelivery)
            <div class="mom-card mt-6 border border-[rgba(197,160,89,0.25)] p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="mom-micro">{{ __('Delivery detail (debug)') }}</h4>
                    <button type="button" wire:click="$set('inspectDeliveryId', null)" class="mom-cta-ghost mom-cta-compact">{{ __('Close') }}</button>
                </div>
                <p class="mom-subtext mt-2">{{ __('Request payload') }}</p>
                <pre class="mt-1 max-h-48 overflow-auto whitespace-pre-wrap rounded-mom-chrome bg-[rgba(10,15,28,0.85)] p-3 font-mono text-[11px] text-[var(--text-secondary)]">{{ $inspectedDelivery->request_payload ?? '—' }}</pre>
                <p class="mom-subtext mt-4">{{ __('Response payload') }}</p>
                <pre class="mt-1 max-h-48 overflow-auto whitespace-pre-wrap rounded-mom-chrome bg-[rgba(10,15,28,0.85)] p-3 font-mono text-[11px] text-[var(--text-secondary)]">{{ $inspectedDelivery->response_payload ?? $inspectedDelivery->response_body ?? '—' }}</pre>
            </div>
        @endif
    </section>
</div>
