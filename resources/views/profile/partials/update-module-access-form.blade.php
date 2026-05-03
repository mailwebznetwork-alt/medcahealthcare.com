<section>
    <header>
        <h2 class="mom-section-title">
            {{ __('Module access') }}
        </h2>

        <p class="mom-body-text mt-2">
            {{ __('Checked modules are visible in the sidebar and on your dashboard. Unchecked modules stay hidden until access is granted again.') }}
        </p>
    </header>

    @if (session('status') === 'module-access-updated')
        <p class="mom-body-text mt-4 text-[var(--success)]" role="status">
            {{ __('Module access saved.') }}
        </p>
    @endif

    <form method="post" action="{{ route('profile.module-access.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        @foreach (\App\ModuleAccess::labelsForForm() as $key => $meta)
            <label class="flex cursor-pointer items-start gap-3 rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-4 transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)]">
                <input
                    type="checkbox"
                    name="module_access[{{ $key }}]"
                    value="1"
                    class="mt-1 h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold shadow-inner focus:ring-1 focus:ring-[rgba(212,169,95,0.35)]"
                    @checked($user->hasModuleAccess($key))
                />
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-[var(--text-primary)]">{{ __($meta['label']) }}</span>
                    <span class="mom-subtext mt-1 block">{{ __($meta['description']) }}</span>
                </span>
            </label>
        @endforeach

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button variant="mom">{{ __('Save access') }}</x-primary-button>
        </div>
    </form>
</section>
