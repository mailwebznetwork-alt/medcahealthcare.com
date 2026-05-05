<x-app-layout
    :page-title="__('Settings')"
    :welcome-line="__('Integrations workspace for platform and channel connections.')"
>
    @if (session('status'))
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->has('integration'))
        <p class="mom-body-text mb-6 text-[var(--danger)]" role="alert">{{ $errors->first('integration') }}</p>
    @endif

    <section class="mom-card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="mom-section-title">{{ __('Integrations') }}</h2>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                    {{ __('Manage providers, secure credentials, and connection state for core services.') }}
                </p>
            </div>
            <span class="mom-micro text-mom-gold">{{ __('Total: :count', ['count' => $integrations->count()]) }}</span>
        </div>
    </section>

    <section class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($integrations as $integration)
            <article class="mom-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="mom-micro">{{ str_replace('_', ' ', $integration->type) }}</p>
                        <h3 class="mt-2 text-base font-semibold text-[var(--text-primary)]">{{ str_replace('_', ' ', $integration->name) }}</h3>
                        @if ($integration->name === 'meta_capi')
                            <p class="mom-subtext mt-2 max-w-sm">{{ __('Meta Conversions API (server). Leave access token blank to retain existing token.') }}</p>
                        @elseif ($integration->name === 'google_business_profile')
                            <p class="mom-subtext mt-2 max-w-sm">{{ __('Use MEDCA_GMB_CLIENT_ID and MEDCA_GMB_CLIENT_SECRET in .env. Sync runs every 4 hours.') }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-1 text-xs {{ $integration->is_enabled ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $integration->is_enabled ? 'bg-[var(--success)]' : 'bg-[var(--text-muted)]' }}"></span>
                        {{ $integration->is_enabled ? __('Enabled') : __('Disabled') }}
                    </span>
                </div>

                <dl class="mom-body-text mt-4 space-y-1 text-[var(--text-secondary)]">
                    <div class="flex justify-between gap-4">
                        <dt>{{ __('Last used') }}</dt>
                        <dd class="text-right text-[var(--text-primary)]">
                            {{ $integration->last_used_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? __('Never') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt>{{ __('Credential keys') }}</dt>
                        <dd class="text-right text-[var(--text-primary)]">{{ count($integration->credentials) }}</dd>
                    </div>
                </dl>

                <form method="post" action="{{ route('admin.settings.integrations.update', $integration->name) }}" class="mt-5 space-y-3">
                    @csrf
                    <input type="hidden" name="is_enabled" value="{{ $integration->is_enabled ? '1' : '0' }}">
                    @foreach (($fieldMap[$integration->name] ?? []) as $field)
                        <label class="block">
                            <span class="mom-micro mb-1 block">{{ str_replace('_', ' ', $field) }}</span>
                            <input
                                type="{{ str_contains($field, 'token') || str_contains($field, 'key') || str_contains($field, 'secret') ? 'password' : 'text' }}"
                                name="credentials[{{ $field }}]"
                                class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]"
                                value="{{ old("credentials.$field") }}"
                                autocomplete="off"
                                placeholder="{{ $integration->name === 'meta_capi' && $field === 'capi_access_token' ? __('Leave blank to keep existing token') : '' }}"
                            >
                        </label>
                    @endforeach

                    <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save') }}</button>
                </form>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a
                        href="{{ route('admin.settings.integrations.show', $integration->name) }}"
                        target="_blank"
                        class="mom-cta-primary !px-3 !py-2 !text-[11px]"
                    >{{ __('View') }}</a>
                    <form method="post" action="{{ route('admin.settings.integrations.toggle', $integration->name) }}" class="inline-flex">
                        @csrf
                        @method('patch')
                        <button type="submit" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">
                            {{ $integration->is_enabled ? __('Disable') : __('Enable') }}
                        </button>
                    </form>
                    <form method="post" action="{{ route('admin.settings.integrations.test', $integration->name) }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">{{ __('Test') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <article class="mom-card p-6 md:col-span-2 xl:col-span-3">
                <p class="mom-body-text text-[var(--text-muted)]">{{ __('No integrations found.') }}</p>
            </article>
        @endforelse
    </section>

    <section class="mom-card mt-8 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="mom-section-title">{{ __('Google Business Review Feed') }}</h3>
                <p class="mom-subtext mt-2">{{ __('Manual sync or wait for scheduled 4-hour sync cycle.') }}</p>
            </div>
            <form method="post" action="{{ route('admin.settings.integrations.google-business-profile.sync-reviews') }}">
                @csrf
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Sync Google reviews') }}</button>
            </form>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Reviewer') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Rating') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Comment') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Reviewed at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @forelse ($googleBusinessReviews as $review)
                        <tr>
                            <td class="px-4 py-3">{{ $review->reviewer_name ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $review->star_rating ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $review->comment ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $review->review_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No Google reviews synced yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
