@php
    $t = $activeTab ?? (string) request()->query('tab', 'competitors');
    $tabItems = [
        'readiness' => [__('Readiness'), 'growth-center.readiness', []],
        'competitors' => [__('Competitors'), 'growth-center.competitors.index', ['tab' => 'competitors']],
        'war-room' => [__('War Room'), 'growth-center.war-room', []],
        'hijack-opportunities' => [__('Hijack Ops'), 'growth-center.competitors.index', ['tab' => 'hijack-opportunities']],
        'seo' => [__('SEO'), 'growth-center.seo.index', []],
        'aeo' => [__('AEO'), 'growth-center.aeo.index', []],
        'geo' => [__('GEO'), 'growth-center.geo.location', []],
        'ga4' => [__('GA4'), 'growth-center.ga4.index', []],
        'gsc' => [__('GSC'), 'growth-center.gsc.index', []],
        'ai-pulse' => [__('AI Pulse'), 'growth-center.ai-pulse.index', []],
    ];
@endphp

<nav class="space-y-3" aria-label="{{ __('Growth Center workspaces') }}">
    <div class="flex flex-wrap gap-0">
        @foreach ($tabItems as $key => [$label, $route, $params])
            @php
                $isActive = $key === $t
                    || ($key === 'war-room' && request()->routeIs('growth-center.war-room', 'growth-center.war-room.*'))
                    || ($key === 'seo' && request()->routeIs('growth-center.seo.*'))
                    || ($key === 'aeo' && request()->routeIs('growth-center.aeo.*'))
                    || ($key === 'geo' && request()->routeIs('growth-center.geo.*'))
                    || ($key === 'readiness' && request()->routeIs('growth-center.readiness'))
                    || ($key === 'ga4' && request()->routeIs('growth-center.ga4.*'))
                    || ($key === 'gsc' && request()->routeIs('growth-center.gsc.*'))
                    || ($key === 'ai-pulse' && request()->routeIs('growth-center.ai-pulse.*'));
            @endphp
            <a
                href="{{ route($route, $params) }}"
                @class([
                    'inline-flex items-center border-b px-4 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                    'border-mom-gold text-mom-gold' => $isActive,
                    'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $isActive,
                ])
            >{{ $label }}</a>
        @endforeach
    </div>
</nav>
