# Deployment Engine Documentation

## Overview

The deployment engine generates CMS pages from industry blueprints, applies style packs to block variants, and packages site configuration for export. It lives primarily under **Site Architect**.

## Entry points

| UI | Route name | Roles |
|----|------------|-------|
| Blueprint Builder | `site-architect.blueprint-builder.index` | manager+ |
| Deployment Packages | `site-architect.deployment-packages.index` | admin+ |
| Block Presets | `site-architect.block-presets.index` | editor+ |
| Section Library | `site-architect.section-library.index` | editor+ |
| Block Studio | `site-architect.block-studio.index` | editor+ |

URL aliases: `/site-architect/sections`, `/site-architect/presets` (301 to canonical paths).

## Core services

| Class | Responsibility |
|-------|----------------|
| `BlueprintRegistry` | Industry blueprint definitions (`config/blueprints.php`) |
| `BlueprintPageGenerator` | Creates `Page` records with `{{block:slug}}` tokens |
| `StylePackRegistry` | Maps packs → block style variants |
| `BlockSettingsResolver` | Resolves render variables per block/pack |
| `ThemeConfigRepository` | Draft/publish theme JSON |
| `ThemeCssVariableBuilder` | Shape/spacing CSS variables |
| `ContentParser` | Public render; block cache; section wrappers |

## Blueprint generation flow

1. User selects blueprint + style pack + layout mode in Blueprint Builder.
2. Optional `activate_generated_pages` publishes pages immediately (Phase 8.5).
3. Generator writes content with block tokens and `block_overrides_json`.
4. Public site resolves blocks via `ContentParser` + `BlockMediaUrl`.

## Theme publish flow

1. Edit draft in **Settings → Appearance** (Livewire).
2. Preview via `settings.appearance.preview.enable` session routes.
3. Publish promotes draft; sets `active_style_pack` on publish (Phase 8.5).

## Configuration

- `config/deployment_engine.php` — generator roles, blueprint metadata
- `config/theme.php` — tokens, presets
- `database/migrations/*deployment_engine*` — package/generation tables

## Testing

- `tests/Feature/DeploymentEngineTest.php`
- `tests/Feature/Phase85RemediationTest.php`
- `tests/Feature/ThemeManagementTest.php`, `ThemePreviewTest.php`

## Operational notes

- Run `ThemePresetSeeder` on fresh environments before blueprint tests.
- Large blueprints: monitor generation time; consider queueing in future.
- Block factory must contain referenced slugs before public render.
