<?php

namespace App\Services\Integrations;

class IntegrationRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return config('integrations.definitions', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $name): ?array
    {
        $definition = config("integrations.definitions.{$name}");

        return is_array($definition) ? $definition : null;
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->all());
    }
}
