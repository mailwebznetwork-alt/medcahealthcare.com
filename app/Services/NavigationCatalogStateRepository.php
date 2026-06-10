<?php

namespace App\Services;

use App\Models\NavigationCatalogState;
use Illuminate\Support\Str;

class NavigationCatalogStateRepository
{
    public function forZone(string $zone): NavigationCatalogState
    {
        return NavigationCatalogState::query()->firstOrCreate(
            ['zone' => $zone],
            [
                'exclusions' => [],
                'manual_children' => [],
                'sibling_orders' => [],
            ],
        );
    }

    /**
     * @return list<string>
     */
    public function exclusions(string $zone): array
    {
        return array_values($this->forZone($zone)->exclusions ?? []);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function manualChildren(string $zone): array
    {
        return $this->forZone($zone)->manual_children ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function siblingOrders(string $zone): array
    {
        return $this->forZone($zone)->sibling_orders ?? [];
    }

    public function exclude(string $zone, string $catalogKey): void
    {
        $state = $this->forZone($zone);
        $exclusions = array_values(array_unique(array_merge($state->exclusions ?? [], [$catalogKey])));
        $state->update(['exclusions' => $exclusions]);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public function addManualChild(string $zone, string $parentCatalogKey, array $node): void
    {
        $state = $this->forZone($zone);
        $manualChildren = $state->manual_children ?? [];
        $node['_attachment_id'] = (string) ($node['_attachment_id'] ?? Str::uuid());

        $siblings = is_array($manualChildren[$parentCatalogKey] ?? null) ? $manualChildren[$parentCatalogKey] : [];
        $siblings[] = $node;
        $manualChildren[$parentCatalogKey] = array_values($siblings);

        $state->update(['manual_children' => $manualChildren]);
    }

    public function removeManualChild(string $zone, string $parentCatalogKey, string $attachmentId): void
    {
        $state = $this->forZone($zone);
        $manualChildren = $state->manual_children ?? [];

        if (! isset($manualChildren[$parentCatalogKey])) {
            return;
        }

        $manualChildren[$parentCatalogKey] = array_values(array_filter(
            $manualChildren[$parentCatalogKey],
            static fn (array $child): bool => (string) ($child['_attachment_id'] ?? '') !== $attachmentId,
        ));

        if ($manualChildren[$parentCatalogKey] === []) {
            unset($manualChildren[$parentCatalogKey]);
        }

        $state->update(['manual_children' => $manualChildren]);
    }

    /**
     * @param  list<string>  $orderedKeys
     */
    public function setSiblingOrder(string $zone, string $contextKey, array $orderedKeys): void
    {
        $state = $this->forZone($zone);
        $siblingOrders = $state->sibling_orders ?? [];
        $siblingOrders[$contextKey] = array_values($orderedKeys);
        $state->update(['sibling_orders' => $siblingOrders]);
    }
}
