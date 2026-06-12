<?php

namespace App\Services\Deployment;

use App\Models\GlobalContentVariable;
use App\Models\GlobalContentVariableSnapshot;
use App\Models\User;
use App\Services\Theme\ThemeConfigRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class GlobalContentVariableRepository
{
    private const CACHE_KEY = 'deployment.global_content_variables.resolved';

    private function cacheTtl(): int
    {
        return (int) config('governance.global_content_cache_ttl', 0);
    }

    public function __construct(
        private readonly ThemeConfigRepository $themeRepository,
    ) {}

    /**
     * @return list<string>
     */
    public function allowedKeys(): array
    {
        return array_keys(config('global_content_variables.keys', []));
    }

    /**
     * Resolved key => value for interpolation (published branding + DB overrides).
     *
     * @return array<string, string>
     */
    public function resolved(): array
    {
        $ttl = $this->cacheTtl();

        if ($ttl <= 0) {
            return $this->resolveFromDatabase();
        }

        return Cache::remember(self::CACHE_KEY, $ttl, function (): array {
            return $this->resolveFromDatabase();
        });
    }

    /**
     * @return array<string, string>
     */
    private function resolveFromDatabase(): array
    {
        $definitions = config('global_content_variables.keys', []);
        $branding = $this->themeRepository->publishedBranding();
        $stored = Schema::hasTable('global_content_variables')
            ? GlobalContentVariable::query()->pluck('value', 'key')->all()
            : [];

        $resolved = [];
        foreach ($definitions as $key => $meta) {
            $storedValue = isset($stored[$key]) ? trim((string) $stored[$key]) : '';
            if ($storedValue !== '') {
                $resolved[$key] = $storedValue;

                continue;
            }

            $brandingKey = is_array($meta) ? ($meta['branding_key'] ?? null) : null;
            if (is_string($brandingKey) && isset($branding[$brandingKey]) && (string) $branding[$brandingKey] !== '') {
                $resolved[$key] = (string) $branding[$brandingKey];

                continue;
            }

            $medcaKey = is_array($meta) ? ($meta['medca_key'] ?? null) : null;
            if (is_string($medcaKey) && config('medca.'.$medcaKey) !== null) {
                $resolved[$key] = (string) config('medca.'.$medcaKey);
            }
        }

        if (! isset($resolved['website_url']) || $resolved['website_url'] === '') {
            $resolved['website_url'] = (string) config('app.url');
        }

        return $resolved;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array{label: string, value: string, type: string, group: string, hint: ?string}>
     */
    public function forEditor(): array
    {
        $resolved = $this->resolved();
        $definitions = config('global_content_variables.keys', []);
        $out = [];

        foreach ($definitions as $key => $meta) {
            $out[$key] = $this->editorRow($key, is_array($meta) ? $meta : [], $resolved[$key] ?? '');
        }

        return $out;
    }

    /**
     * @return array<string, array{label: string, description: string, fields: array<string, array{label: string, value: string, type: string, group: string, hint: ?string}>}>
     */
    public function forEditorGrouped(): array
    {
        $editor = $this->forEditor();
        $groups = config('global_content_variables.groups', []);
        $grouped = [];

        foreach ($groups as $groupKey => $groupMeta) {
            $grouped[$groupKey] = [
                'label' => (string) ($groupMeta['label'] ?? $groupKey),
                'description' => (string) ($groupMeta['description'] ?? ''),
                'fields' => [],
            ];
        }

        foreach ($editor as $key => $row) {
            $group = $row['group'];
            if (! isset($grouped[$group])) {
                $grouped[$group] = [
                    'label' => ucfirst(str_replace('_', ' ', $group)),
                    'description' => '',
                    'fields' => [],
                ];
            }

            $grouped[$group]['fields'][$key] = $row;
        }

        return $grouped;
    }

    /**
     * @return list<string>
     */
    public function keysForGroups(array $groupKeys): array
    {
        $definitions = config('global_content_variables.keys', []);
        $keys = [];

        foreach ($definitions as $key => $meta) {
            $group = is_array($meta) ? (string) ($meta['group'] ?? 'identity') : 'identity';
            if (in_array($group, $groupKeys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{label: string, value: string, type: string, group: string, hint: ?string}
     */
    private function editorRow(string $key, array $meta, string $value): array
    {
        return [
            'label' => (string) ($meta['label'] ?? $key),
            'value' => $value,
            'type' => (string) ($meta['type'] ?? 'text'),
            'group' => (string) ($meta['group'] ?? 'identity'),
            'hint' => isset($meta['hint']) ? (string) $meta['hint'] : null,
        ];
    }

    /**
     * @param  array<string, string>  $values
     */
    public function sync(array $values, User $user): void
    {
        foreach ($this->allowedKeys() as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            GlobalContentVariable::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => (string) (config("global_content_variables.keys.{$key}.label") ?? $key),
                    'value' => (string) $values[$key],
                    'updated_by_id' => $user->id,
                ]
            );
        }

        self::forgetCache();
    }

    /**
     * @return array<string, string>
     */
    public function exportPayload(): array
    {
        return $this->resolved();
    }

    /**
     * @param  array<string, string>  $payload
     */
    public function importPayload(array $payload, User $user): void
    {
        $filtered = [];
        foreach ($this->allowedKeys() as $key) {
            if (isset($payload[$key])) {
                $filtered[$key] = (string) $payload[$key];
            }
        }

        $this->sync($filtered, $user);
    }

    public function createSnapshot(User $user): GlobalContentVariableSnapshot
    {
        $nextVersion = (int) GlobalContentVariableSnapshot::query()->max('version') + 1;

        return GlobalContentVariableSnapshot::query()->create([
            'version' => $nextVersion,
            'payload_json' => $this->resolved(),
            'created_by_id' => $user->id,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, GlobalContentVariableSnapshot>
     */
    public function snapshots(int $limit = 10): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('global_content_variable_snapshots')) {
            return collect();
        }

        return GlobalContentVariableSnapshot::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function restoreSnapshot(GlobalContentVariableSnapshot $snapshot, User $user): void
    {
        $payload = is_array($snapshot->payload_json) ? $snapshot->payload_json : [];
        $this->importPayload($payload, $user);
    }

    /**
     * @return array<string, string>
     */
    public function previewSample(): array
    {
        $resolved = $this->resolved();

        return [
            'headline' => __('Welcome to :name', ['name' => $resolved['company_name'] ?? '']),
            'contact' => trim(($resolved['phone_number'] ?? '').' · '.($resolved['email'] ?? '')),
            'cta' => $resolved['primary_cta'] ?? '',
        ];
    }
}
