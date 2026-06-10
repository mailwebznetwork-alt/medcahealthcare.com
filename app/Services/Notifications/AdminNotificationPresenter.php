<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Str;

class AdminNotificationPresenter
{
    public function shouldNotify(string $action, string $module): bool
    {
        if (in_array($action, config('notifications.muted_actions', []), true)) {
            return false;
        }

        return $module !== '';
    }

    /**
     * @return array{
     *     module: string,
     *     action: string,
     *     entity_type: string|null,
     *     title: string,
     *     body: string|null,
     *     url: string|null
     * }
     */
    public function present(string $action, string $module, ?string $description, ?int $actorUserId): array
    {
        $normalizedModule = (string) config("notifications.module_map.{$module}", $module);
        $entityKey = $this->resolveEntityKey($action);
        $entityLabel = (string) config("notifications.entity_labels.{$entityKey}", Str::headline($entityKey ?: 'Record'));
        $verb = $this->resolveVerb($action);
        $moduleLabel = (string) config("notifications.module_labels.{$normalizedModule}", Str::headline($normalizedModule));
        $actorName = $this->resolveActorName($actorUserId);

        $title = "{$entityLabel} {$verb}";
        $body = trim(collect([
            $description,
            $actorName !== null ? "By {$actorName}" : null,
            $moduleLabel,
        ])->filter()->implode(' · '));

        return [
            'module' => $normalizedModule,
            'action' => $action,
            'entity_type' => $entityKey ?: null,
            'title' => $title,
            'body' => $body !== '' ? $body : null,
            'url' => $this->resolveUrl($normalizedModule, $action, $description),
        ];
    }

    private function resolveEntityKey(string $action): string
    {
        $action = strtolower($action);

        foreach (array_keys(config('notifications.entity_labels', [])) as $key) {
            if (Str::startsWith($action, $key)) {
                return $key;
            }
        }

        if (preg_match('/^([a-z_]+?)(?:_(?:create|update|delete|created|updated|deleted|removed|duplicate|toggle|sync))$/', $action, $matches) === 1) {
            return $matches[1];
        }

        return Str::before($action, '_') ?: $action;
    }

    private function resolveVerb(string $action): string
    {
        $action = strtolower($action);

        if (preg_match('/(?:^|_)(delete|deleted|removed|destroy)(?:_|$)/', $action) === 1) {
            return 'removed';
        }

        if (preg_match('/(?:^|_)(create|created|duplicate|registered)(?:_|$)/', $action) === 1) {
            return 'added';
        }

        if (preg_match('/(?:^|_)(update|updated|toggle|sync|reorder|save)(?:_|$)/', $action) === 1) {
            return 'updated';
        }

        if (str_contains($action, 'failure') || str_contains($action, 'blocked')) {
            return 'alert';
        }

        return 'changed';
    }

    private function resolveActorName(?int $actorUserId): ?string
    {
        if ($actorUserId === null) {
            return null;
        }

        return User::query()->whereKey($actorUserId)->value('name');
    }

    private function resolveUrl(string $module, string $action, ?string $description): ?string
    {
        if ($description !== null) {
            if (preg_match('/\buser ID (\d+)\b/i', $description, $matches) === 1) {
                return route('user-management.edit', ['user' => (int) $matches[1]], false);
            }

            if (preg_match('/\bpage ID (\d+)\b/i', $description, $matches) === 1) {
                return route('site-architect.pages.index', absolute: false).'?highlight='.$matches[1];
            }
        }

        $path = config("notifications.module_urls.{$module}");

        return is_string($path) && $path !== '' ? $path : null;
    }
}
