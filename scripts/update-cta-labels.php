<?php

use App\Models\Block;
use App\Models\GlobalContentVariable;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$replacements = [
    'Speak to a Care Coordinator' => 'Call Us',
    'Request a Free Home Assessment' => 'WhatsApp Us',
];

$blockSkipSlugs = ['hero-contact'];
$gcvSkipKeys = ['contact_hero_headline'];

// Restore contact hero headline if a prior run replaced button copy in the page title field.
$contactHero = Block::query()->where('block_slug', 'hero-contact')->first();
if ($contactHero !== null) {
    $settings = is_array($contactHero->settings_json) ? $contactHero->settings_json : [];
    $content = is_array($settings['content'] ?? null) ? $settings['content'] : [];
    if (($content['headline'] ?? '') === 'Call Us') {
        $content['headline'] = 'Get in Touch';
        $settings['content'] = $content;
        $contactHero->settings_json = $settings;
        $contactHero->save();
    }
}

$report = [
    'blocks' => [],
    'global_content_variables' => [],
    'hero_cta_fields' => [],
];

function replaceInMixed(mixed $data, array $replacements): array
{
    $changed = false;
    if (is_string($data)) {
        $new = $data;
        foreach ($replacements as $from => $to) {
            if (str_contains($new, $from)) {
                $new = str_replace($from, $to, $new);
                $changed = true;
            }
        }
        return [$new, $changed];
    }
    if (is_array($data)) {
        $out = [];
        foreach ($data as $k => $v) {
            [$nv, $c] = replaceInMixed($v, $replacements);
            $out[$k] = $nv;
            $changed = $changed || $c;
        }
        return [$out, $changed];
    }
    return [$data, false];
}

foreach (Block::query()->cursor() as $block) {
    $settings = $block->settings_json;
    if (! is_array($settings)) {
        continue;
    }
    [$newSettings, $changed] = replaceInMixed($settings, $replacements);

    if (in_array($block->block_slug, $blockSkipSlugs, true)) {
        continue;
    }

    if (in_array($block->block_slug, ['hero-home', 'cta-home'], true)) {
        $content = $newSettings['content'] ?? [];
        if (! is_array($content)) {
            $content = [];
        }
        $content['primary_cta_label'] = 'Call Us';
        $content['secondary_cta_label'] = 'WhatsApp Us';
        $content['secondary_cta_url'] = '';
        $newSettings['content'] = $content;
        $changed = true;
        $report['hero_cta_fields'][] = [
            'block_slug' => $block->block_slug,
            'id' => $block->id,
            'primary_cta_label' => 'Call Us',
            'secondary_cta_label' => 'WhatsApp Us',
            'secondary_cta_url' => '',
        ];
    }

    if ($changed) {
        $block->settings_json = $newSettings;
        $block->save();
        $report['blocks'][] = [
            'id' => $block->id,
            'block_slug' => $block->block_slug,
            'settings_json' => $newSettings,
        ];
    }
}

foreach (GlobalContentVariable::query()->cursor() as $gcv) {
    $key = $gcv->key;
    $value = (string) ($gcv->value ?? '');
    $newValue = $value;
    $explicit = false;

    if ($key === 'primary_cta') {
        $newValue = 'Call Us';
        $explicit = true;
    } elseif ($key === 'secondary_cta') {
        $newValue = 'WhatsApp Us';
        $explicit = true;
    } elseif (! in_array($key, $gcvSkipKeys, true)) {
        foreach ($replacements as $from => $to) {
            if (str_contains($newValue, $from)) {
                $newValue = str_replace($from, $to, $newValue);
            }
        }
    }

    if ($explicit || $newValue !== $value) {
        $gcv->value = $newValue;
        $gcv->save();
        $report['global_content_variables'][] = [
            'key' => $key,
            'old' => $value,
            'new' => $newValue,
        ];
    }
}

$whatsappUrl = config('medca.whatsapp_url');
echo json_encode([
    'whatsapp_url_from_config' => $whatsappUrl,
    'updated' => $report,
    'contact_hero_headline_unchanged' => GlobalContentVariable::where('key', 'contact_hero_headline')->value('value'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
