@php
    $selectedRole = old('role', $user?->role instanceof \BackedEnum ? $user->role->value : (string) ($user?->role ?? 'viewer'));
@endphp
<div class="sm:col-span-2">
    <x-input-label for="role" :value="__('System role')" />
    <select
        id="role"
        name="role"
        class="mt-1 block w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]"
        @disabled($user?->isRootSuperAdmin() ?? false)
        required
    >
        @foreach (\App\ModuleAccess::roleLabels() as $value => $label)
            <option value="{{ $value }}" @selected($selectedRole === $value)>{{ __($label) }}</option>
        @endforeach
    </select>
    <p class="mom-subtext mt-1">{{ __('Controls approval rights and default module access. Module checkboxes can override defaults.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('role')" />
</div>
