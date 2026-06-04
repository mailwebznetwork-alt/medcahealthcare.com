# Phase 1 — Route Validation Report

**Status:** PASS  
**Date:** 2026-05-30

## Summary

| Check | Result |
|-------|--------|
| Total application routes | 197 (181 named) |
| Duplicate route names | **0** |
| Nav blade `route()` references | **38/38 resolve** |
| Broken routes | **0** |
| IA redirects registered | **PASS** |

## Sidebar & submenu coverage

All static `route()` names in:

- `resources/views/components/mom-sidebar-nav.blade.php` (dynamic from `ModuleAccess` + `AdminNavigation`)
- `site-architect/partials/primary-tabs.blade.php`
- `marketing/partials/primary-tabs.blade.php`
- `growth-center/partials/primary-tabs.blade.php`
- `operations/partials/primary-tabs.blade.php`
- `system/partials/nav.blade.php`
- `settings/partials/nav.blade.php`
- `security/partials/nav.blade.php`

…exist in `php artisan route:list`.

## Redirect report

| Route / URI | Target | HTTP |
|-------------|--------|------|
| `modules.site-architect` | `site-architect.pages.index` | 302 |
| `site-architect.sections.index` | `/site-architect/section-library` | 301 |
| `site-architect.presets.index` | `/site-architect/block-presets` | 301 |
| `modules.growth-center` | `growth-center.competitors.index` | 302 |
| `growth-center.readiness` | competitors `?tab=readiness` | 302 |
| `/growth-center/war-room` | `/growth-center/war-room/dashboard` | 301 |
| `operations.job-portal.index` | `operations.job-portal.overview` | 302 |
| `operations.pin-codes.index` | `operations.pin-codes.overview` | 302 |
| `system.index` | `system.overview` | 301 |
| `system.integrations` | `settings.integrations` | 301 |
| `settings.index` | `settings.appearance` | 302 |

Legacy URLs (`/site-architect/section-library`, `/block-presets`, `/growth-center/war-room/dashboard`, `/settings/integrations`) remain reachable.

## Broken route report

**None identified.**

## Route collisions

**None** — verified programmatically on `route:list --json`.

## Orphan routes (expected, not defects)

~30 named admin GET routes are not in primary tab nav but reachable via:

- In-module toolbars (Operations CRUD)
- Row actions and previews
- `WorkspaceGlobalSearch`
- Redirect stubs (`operations.job-portal.index`, `modules.site-architect`)

Examples: `operations.services.edit`, `operations.bookings.show`, `admin.settings.integrations.index`.

## Automated verification

- `tests/Feature/NavigationRestructureTest.php` — aliases, System hub, module keys
- Full suite: 350 tests passed
