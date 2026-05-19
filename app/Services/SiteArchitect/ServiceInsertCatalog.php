<?php

namespace App\Services\SiteArchitect;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

/**
 * Site Architect block editors: every service row for the insert dropdown,
 * regardless of publish/visibility/active — public rendering still uses
 * {@see Service::findPublishedByCode()}.
 */
class ServiceInsertCatalog
{
    public const string SERVICE_TOKEN_PATTERN = '/\{\{\s*service\s*:\s*([^}]+?)\s*\}\}/';

    /**
     * @return Collection<int, Service>
     */
    public function forDropdown(): Collection
    {
        return Service::query()
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'service_code',
                'publish_status',
                'visibility',
                'is_active',
            ]);
    }

    public function existsForToken(string $serviceCode): bool
    {
        $code = trim($serviceCode);

        if ($code === '') {
            return false;
        }

        return Service::query()
            ->where('service_code', $code)
            ->exists();
    }

    /**
     * Default grid used when a block only contains {{service:CODE}} tokens (no @foreach).
     */
    public function defaultServicesGridBlade(): string
    {
        return <<<'BLADE'
<section class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
@foreach ($services as $service)
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">{{ $service->title }}</h3>
        @if (filled($service->short_summary))
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 200) }}</p>
        @endif
        <a href="{{ route('public.services.show', $service->service_code) }}" class="mt-4 inline-flex text-sm font-semibold text-[#0046ad] hover:text-[#001e5c]">{{ __('Learn more') }} →</a>
    </article>
@endforeach
</section>
BLADE;
    }

    /**
     * Prepends the default services grid when block code has tokens but no layout.
     */
    public function ensureLayoutInBlockCode(string $code): string
    {
        if (preg_match('/@foreach\s*\(\s*\$services\b/', $code) === 1) {
            return $code;
        }

        if (preg_match(self::SERVICE_TOKEN_PATTERN, $code) !== 1) {
            return $code;
        }

        $trimmed = trim($code);
        $layout = $this->defaultServicesGridBlade();

        return $trimmed === '' ? $layout : $layout."\n\n".$trimmed;
    }
}
