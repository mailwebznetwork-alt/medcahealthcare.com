<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('Database backup') }}</h2>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Creates a timestamped copy of the SQLite database under storage/app/backups. Other database drivers require a manual dump.') }}</p>
    <form method="post" action="{{ route('settings.system.backup') }}" class="mt-4 flex flex-wrap items-center gap-3">
        @csrf
        <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Run backup now') }}</button>
    </form>
    @if ($backupFiles !== [])
        <div class="mt-6">
            <h3 class="mom-micro mb-2">{{ __('Recent backup files') }}</h3>
            <ul class="space-y-1 text-[13px] text-[var(--text-secondary)]">
                @foreach ($backupFiles as $path)
                    <li class="font-mono text-[12px]">{{ basename($path) }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
