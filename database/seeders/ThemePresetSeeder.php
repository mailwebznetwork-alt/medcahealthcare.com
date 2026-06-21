<?php

namespace Database\Seeders;

use App\Models\ThemePreset;
use Illuminate\Database\Seeder;

class ThemePresetSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('theme_presets', []) as $slug => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            ThemePreset::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => (string) ($definition['name'] ?? $slug),
                    'shell' => (string) ($definition['shell'] ?? 'public'),
                    'is_builtin' => true,
                    'tokens' => $definition['tokens'] ?? [],
                    'branding' => $definition['branding'] ?? null,
                    'header_preset' => $definition['header_preset'] ?? 'classic_digital growth platform',
                    'layout_preset' => $definition['layout_preset'] ?? 'contained',
                    'typography' => $definition['typography'] ?? null,
                ]
            );
        }
    }
}
