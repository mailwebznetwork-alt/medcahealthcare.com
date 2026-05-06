<?php

namespace App\Http\Controllers;

use App\Models\GoogleBusinessReview;
use App\Models\Integration;
use App\Services\Integrations\CredentialVault;
use App\Services\Integrations\IntegrationRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
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
            $hasIntegrationAccountsTable = Schema::hasTable('integration_accounts');
            $query = Integration::query()
                ->orderBy('type')
                ->orderBy('name');

            if ($hasIntegrationAccountsTable) {
                $query->with('accounts');
            }

            $integrations = $query->get()
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
                ->map(fn (array $definition, string $name): array => [
                    'name' => $name,
                    'label' => (string) ($definition['label'] ?? $name),
                    'type' => (string) ($definition['type'] ?? 'misc'),
                    'is_added' => in_array($name, $addedNames, true),
                ])
                ->values()
                ->all();
        }

        $googleBusinessReviews = collect();
        if (Schema::hasTable('google_business_reviews')) {
            $googleBusinessReviews = GoogleBusinessReview::query()->latest('review_time')->limit(20)->get();
        }

        $backupFiles = [];
        $backupDir = storage_path('app/backups');
        if (is_dir($backupDir)) {
            $paths = glob($backupDir.'/*') ?: [];
            rsort($paths, SORT_STRING);
            $backupFiles = array_slice($paths, 0, 12);
        }

        return view('settings.integrations', [
            'integrations' => $integrations,
            'matrixSummary' => $matrixSummary,
            'definitions' => $definitions,
            'availableIntegrations' => $availableIntegrations,
            'hasIntegrationAccountsTable' => Schema::hasTable('integration_accounts'),
            'credentialVault' => $this->credentialVault,
            'googleBusinessReviews' => $googleBusinessReviews,
            'webhookEvents' => config('settings.webhook_events', []),
            'maintenanceActive' => File::exists(storage_path('framework/maintenance.php')),
            'backupFiles' => $backupFiles,
            'operationsConfigured' => is_string(config('settings.operations_token')) && config('settings.operations_token') !== '',
            'maintenanceSecretConfigured' => is_string(config('settings.maintenance_bypass_secret')) && config('settings.maintenance_bypass_secret') !== '',
        ]);
    }
}
