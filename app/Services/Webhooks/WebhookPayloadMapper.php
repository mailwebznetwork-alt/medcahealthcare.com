<?php

namespace App\Services\Webhooks;

/**
 * Conditional / structural payload mapping for outbound webhooks.
 *
 * Rules shape (JSON column mapping_rules):
 * {
 *   "default": { "include_only": ["a","b"], "exclude": ["x"], "rename": {"old": "new"} },
 *   "events": {
 *     "lead.created": { "include_only": ["lead_id", "uuid"] }
 *   }
 * }
 */
class WebhookPayloadMapper
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $rules
     * @return array<string, mixed>
     */
    public function apply(array $payload, ?array $rules, string $eventKey): array
    {
        if ($rules === null || $rules === []) {
            return $payload;
        }

        $eventSpecific = $rules['events'][$eventKey] ?? null;
        $branch = is_array($eventSpecific) ? $eventSpecific : ($rules['default'] ?? $rules);

        if (! is_array($branch)) {
            return $payload;
        }

        $out = $payload;

        if (! empty($branch['include_only']) && is_array($branch['include_only'])) {
            $keys = array_map('strval', $branch['include_only']);
            $out = [];
            foreach ($keys as $key) {
                if (array_key_exists($key, $payload)) {
                    $out[$key] = $payload[$key];
                }
            }
        }

        if (! empty($branch['exclude']) && is_array($branch['exclude'])) {
            foreach ($branch['exclude'] as $ex) {
                $k = (string) $ex;
                unset($out[$k]);
            }
        }

        if (! empty($branch['rename']) && is_array($branch['rename'])) {
            $renamed = [];
            foreach ($out as $k => $v) {
                $newKey = isset($branch['rename'][$k]) ? (string) $branch['rename'][$k] : $k;
                $renamed[$newKey] = $v;
            }
            $out = $renamed;
        }

        return $out;
    }
}
