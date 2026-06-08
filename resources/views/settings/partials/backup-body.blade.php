<x-admin.card tag="section">
    <h2 class="mom-section-title">{{ __('Database backup') }}</h2>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Creates a timestamped copy of the SQLite database under storage/app/backups. Other database drivers require a manual dump.') }}</p>
    <form method="post" action="{{ route('settings.system.backup') }}" class="mt-4 flex flex-wrap items-center gap-3">
        @csrf
        <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Run backup now') }}</button>
    </form>

    <div class="mt-8 border-t border-[color:var(--border-tabstrip-divider)] pt-8">
        <h3 class="mom-micro mb-2">{{ __('Export to your computer') }}</h3>
        <p class="mom-body-text text-[var(--text-secondary)]">{{ __('Download includes node_modules and vendor so size should track server usage closely; storage/app/backups is always skipped; .git is excluded by default (override via SETTINGS_SITE_BACKUP_EXCLUDED_PREFIXES). Zip is compressed — smaller than raw du unless you compare unpacked project/. Requires file-based SQLite.') }}</p>
        <div class="mt-4">
            <a href="{{ route('settings.system.backup.download') }}" class="mom-cta-primary inline-flex mom-cta-compact">{{ __('Download full site backup') }}</a>
        </div>
    </div>

    <div class="mt-8 border-t border-[color:var(--border-tabstrip-divider)] pt-8">
        <h3 class="mom-micro mb-2">{{ __('Restore from upload') }}</h3>
        <p class="mom-body-text text-[var(--text-secondary)]">{{ __('Upload a zip from this page. It overwrites storage/public and storage/private, copies project/ files onto the application root, then replaces the SQLite file. A safety zip of the current site is saved first. Files on disk that are not in the archive are left in place.') }}</p>
        @error('backup_file')
            <p class="mom-body-text mt-3 text-[var(--danger)]" role="alert">{{ $message }}</p>
        @enderror
        <form method="post" action="{{ route('settings.system.backup.restore') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[200px] flex-1">
                <label for="backup_file" class="mom-micro mb-1 block text-[var(--text-secondary)]">{{ __('Full site backup (.zip)') }}</label>
                <input id="backup_file" name="backup_file" type="file" accept=".zip,application/zip" required class="mom-subtext mt-2 block w-full max-w-md text-sm" />
            </div>
            <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Restore from backup zip') }}</button>
        </form>
    </div>

    @if ($backupFiles !== [])
        <div class="mt-8">
            <h3 class="mom-micro mb-2">{{ __('Recent backup files') }}</h3>
            <ul class="space-y-1 text-[13px] text-[var(--text-secondary)]">
                @foreach ($backupFiles as $path)
                    <li class="font-mono text-[12px]">{{ basename($path) }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-admin.card>
