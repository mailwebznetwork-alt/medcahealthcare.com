@php
    $active = $activeSection ?? 'integrations';
    $isSuperAdmin = auth()->check() && strtolower((string) auth()->user()?->role) === 'super_admin';
    $tabBase = 'rounded-mom-chrome px-3 py-2 transition duration-320 ease-premium';
    $tabInactive = 'text-[var(--text-secondary)] hover:text-[var(--accent-gold)]';
    $tabActive = 'border border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.08)] text-mom-gold';
@endphp

<nav class="mom-card mb-8 flex flex-wrap gap-2 p-3 text-[13px]" aria-label="{{ __('Settings sections') }}">
    <a href="{{ route('settings.integrations') }}" class="{{ $tabBase }} {{ $active === 'integrations' ? $tabActive : $tabInactive }}">{{ __('Integrations') }}</a>
    <a href="{{ route('settings.webhooks') }}" class="{{ $tabBase }} {{ $active === 'webhooks' ? $tabActive : $tabInactive }}">{{ __('Webhooks') }}</a>
    @if ($isSuperAdmin)
        <a href="{{ route('settings.backup') }}" class="{{ $tabBase }} {{ $active === 'backup' ? $tabActive : $tabInactive }}">{{ __('Backup') }}</a>
        <a href="{{ route('settings.maintenance') }}" class="{{ $tabBase }} {{ $active === 'maintenance' ? $tabActive : $tabInactive }}">{{ __('Maintenance') }}</a>
    @endif
</nav>
