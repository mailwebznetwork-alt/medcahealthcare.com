<div class="space-y-8">
    <div>
        <a href="{{ route('operations.bookings.index') }}" class="mom-subtext inline-flex items-center gap-1 text-mom-gold hover:underline">
            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
            {{ __('Back to list') }}
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="mom-card p-6">
            <h2 class="mom-section-title text-base">{{ __('Contact') }}</h2>
            <dl class="mom-body-text mt-4 space-y-2 text-[var(--text-secondary)]">
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Name') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Phone') }}</dt><dd class="text-right font-mono text-[var(--text-primary)]"><a href="tel:{{ preg_replace('/\s+/', '', $lead->phone) }}" class="text-mom-gold hover:underline">{{ $lead->phone }}</a></dd></div>
                @if ($lead->email)
                    <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Email') }}</dt><dd class="text-right text-[var(--text-primary)]"><a href="mailto:{{ $lead->email }}" class="text-mom-gold hover:underline">{{ $lead->email }}</a></dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Service') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->service }}</dd></div>
            </dl>
            @if ($lead->message)
                <p class="mom-subtext mt-4">{{ __('Message') }}</p>
                <p class="mom-body-text mt-1 whitespace-pre-wrap text-[var(--text-secondary)]">{{ $lead->message }}</p>
            @endif
        </div>

        <div class="mom-card p-6">
            <h2 class="mom-section-title text-base">{{ __('Attribution & routing') }}</h2>
            <dl class="mom-body-text mt-4 space-y-2 text-[var(--text-secondary)]">
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Source') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->source->label() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Campaign') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->campaign ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('PIN') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->pinCode?->pincode ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Status') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->status->label() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Assigned') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->assignedUser?->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Follow-up') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->follow_up_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--text-muted)]">{{ __('Created') }}</dt><dd class="text-right text-[var(--text-primary)]">{{ $lead->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="mom-card p-6">
        <h2 class="mom-section-title text-base">{{ __('Notes') }}</h2>
        <form wire:submit="addNote" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label class="mom-micro">{{ __('Add note') }}</label>
                <textarea wire:model="noteBody" rows="2" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" placeholder="{{ __('Internal note…') }}"></textarea>
                @error('noteBody') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="shrink-0 rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Save note') }}</button>
        </form>

        <ul class="mt-8 space-y-4 border-t border-[var(--border-panel-soft)] pt-6">
            @forelse ($lead->notes as $n)
                <li class="rounded-lg border border-[var(--border-panel-soft)] bg-[rgba(255,255,255,0.02)] px-4 py-3">
                    <p class="mom-micro">{{ $n->author?->name ?? __('User') }} · {{ $n->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                    <p class="mom-body-text mt-2 whitespace-pre-wrap text-[var(--text-secondary)]">{{ $n->note }}</p>
                </li>
            @empty
                <li class="mom-body-text text-[var(--text-muted)]">{{ __('No notes yet.') }}</li>
            @endforelse
        </ul>
    </div>
</div>
