@foreach (\App\ModuleAccess::labelsForForm() as $key => $meta)
    @php
        $disabled = $user?->isRootSuperAdmin() ?? false;
        $role = old('role', $user?->role instanceof \BackedEnum ? $user->role->value : (string) ($user?->role ?? 'viewer'));
        $defaults = \App\ModuleAccess::grantsForRole((string) $role);
        $checked = $user ? $user->hasModuleAccess($key) : (bool) ($defaults[$key] ?? false);
    @endphp
    <label @class([
        'flex items-start gap-3 rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-4 transition-all duration-320 ease-premium',
        'cursor-pointer hover:border-[rgba(197,160,89,0.16)]' => ! $disabled,
        'cursor-not-allowed opacity-60' => $disabled,
    ])>
        <input
            type="checkbox"
            name="module_access[{{ $key }}]"
            value="1"
            class="mt-1 h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold shadow-inner focus:ring-1 focus:ring-[rgba(197,160,89,0.35)] disabled:opacity-50"
            @checked($checked)
            @disabled($disabled)
        />
        <span class="min-w-0">
            <span class="block text-sm font-semibold text-[var(--text-primary)]">{{ __($meta['label']) }}</span>
            <span class="mom-subtext mt-1 block">{{ __($meta['description']) }}</span>
        </span>
    </label>
@endforeach
