<?php

namespace App\Http\Controllers;

use App\Models\GoogleBusinessReview;
use App\Models\Integration;
use App\Services\Integrations\CredentialVault;
use App\Services\Integrations\IntegrationRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
        private readonly CredentialVault $credentialVault
    ) {}

    public function __invoke(): View
    {
        /** @var Collection<int, Integration> $integrations */
        $integrations = collect();
        $matrixSummary = collect();
        $availableIntegrations = [];
        $definitions = $this->registry->all();

        if (Schema::hasTable('integrations')) {
            $integrations = Integration::query()
                ->orderBy('type')
                ->orderBy('name')
                ->with('accounts')
                ->get()
                ->filter(fn (Integration $integration): bool => is_array($this->registry->get($integration->name)))
                ->values();

            $matrixSummary = $integrations
                ->groupBy('type')
                ->map(function (Collection $rows): array {
                    return [
                        'total' => $rows->count(),
                        'active' => $rows->where('is_enabled', true)->count(),
                        'inactive' => $rows->where('is_enabled', false)->count(),
                    ];
                })
                ->sortKeys();

            $addedNames = $integrations->pluck('name')->all();
            $availableIntegrations = collect($definitions)
                ->filter(fn (array $definition, string $name): bool => ! in_array($name, $addedNames, true))
                ->map(fn (array $definition, string $name): array => [
                    'name' => $name,
                    'label' => (string) ($definition['label'] ?? $name),
                    'type' => (string) ($definition['type'] ?? 'misc'),
                ])
                ->values()
                ->all();
        }

        $googleBusinessReviews = collect();
        if (Schema::hasTable('google_business_reviews')) {
            $googleBusinessReviews = GoogleBusinessReview::query()->latest('review_time')->limit(20)->get();
        }

        return view('settings.integrations', [
            'integrations' => $integrations,
            'matrixSummary' => $matrixSummary,
            'definitions' => $definitions,
            'availableIntegrations' => $availableIntegrations,
            'credentialVault' => $this->credentialVault,
            'googleBusinessReviews' => $googleBusinessReviews,
        ]);
    }
}
