@php
    use App\ModuleAccess;

    $user = auth()->user();
    $cards = [
        [
            'label' => __('Site Architect'),
            'body' => __('Structure pages, navigation, media, and reusable website blocks.'),
            'route' => 'site-architect.pages.index',
            'icon' => 'drafting-compass',
            'enabled' => $user?->hasModuleAccess(ModuleAccess::SITE_ARCHITECT),
        ],
        [
            'label' => __('Operations'),
            'body' => __('Open hiring, coverage, services, bookings, and operational workspaces.'),
            'route' => 'modules.operations',
            'icon' => 'workflow',
            'enabled' => $user?->hasModuleAccess(ModuleAccess::OPERATIONS),
        ],
        [
            'label' => __('Marketing'),
            'body' => __('Review campaigns, attribution, insights, and lead intent signals.'),
            'route' => 'marketing.dashboard',
            'icon' => 'megaphone',
            'enabled' => $user?->hasModuleAccess(ModuleAccess::MARKETING),
        ],
        [
            'label' => __('Growth Center'),
            'body' => __('Use SEO, GEO, AEO, competitors, analytics, and war-room modules.'),
            'route' => 'modules.growth-center',
            'icon' => 'trending-up',
            'enabled' => $user?->hasModuleAccess(ModuleAccess::GROWTH_CENTER),
        ],
        [
            'label' => __('User Management'),
            'body' => __('Manage team accounts, roles, and module access.'),
            'route' => 'user-management.index',
            'icon' => 'users-round',
            'enabled' => $user?->hasModuleAccess(ModuleAccess::USER_MANAGEMENT),
        ],
        [
            'label' => __('System'),
            'body' => __('Check platform health, source of truth, integrations, and queues.'),
            'route' => 'system.overview',
            'icon' => 'server',
            'enabled' => $user?->hasModuleAccess(ModuleAccess::SYSTEM),
        ],
    ];
@endphp

<x-layouts.markonminds
    page-title="Console"
    welcome-line="A stable command surface for Medca Healthcare modules."
>
    <div class="mom-reveal w-full max-w-full">
        <section class="mom-card mom-apex overflow-hidden p-6 md:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="mom-micro">{{ config('medca.brand_name', 'Medca Healthcare') }}</p>
                    <h1 class="mom-title-page mt-3">{{ __('Console') }}</h1>
                    <p class="mom-body-text mt-4 text-[var(--text-secondary)]">
                        {{ __('Use this page as the lightweight entry point for the workspace. It opens only the modules assigned to your account.') }}
                    </p>
                </div>
                <a
                    href="{{ route('profile.edit') }}"
                    class="inline-flex items-center justify-center rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.08)] px-4 py-2 text-sm font-semibold text-mom-gold transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.42)]"
                >
                    {{ __('Account Settings') }}
                </a>
            </div>
        </section>

        <hr class="mom-section-separator" aria-hidden="true" />

        <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $card)
                @if ($card['enabled'] && Route::has($card['route']))
                    <a href="{{ route($card['route']) }}" class="mom-card mom-card-interactive block p-6 no-underline">
                        <div class="flex items-center justify-between gap-4">
                            <span class="flex h-11 w-11 items-center justify-center rounded-mom-chrome border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.08)] text-mom-gold">
                                <i data-lucide="{{ $card['icon'] }}" class="h-[18px] w-[18px]"></i>
                            </span>
                            <i data-lucide="arrow-up-right" class="h-[17px] w-[17px] text-[var(--text-muted)]"></i>
                        </div>
                        <h2 class="mom-section-title mt-5">{{ $card['label'] }}</h2>
                        <p class="mom-body-text mt-3 text-[var(--text-secondary)]">{{ $card['body'] }}</p>
                    </a>
                @endif
            @endforeach
        </section>

        <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="mom-card p-6">
                <p class="mom-micro">{{ __('Session') }}</p>
                <p class="mom-metric mt-3 text-2xl">{{ now()->timezone('Asia/Kolkata')->format('M j, H:i') }}</p>
                <p class="mom-subtext mt-2">{{ __('India Standard Time') }}</p>
            </div>

            <div class="mom-card p-6">
                <p class="mom-micro">{{ __('Workspace') }}</p>
                <p class="mom-body-text mt-3 text-[var(--text-secondary)]">
                    {{ __('Powered by MarkOnMinds. Module navigation remains available from the sidebar.') }}
                </p>
            </div>
        </section>
    </div>
</x-layouts.markonminds>
