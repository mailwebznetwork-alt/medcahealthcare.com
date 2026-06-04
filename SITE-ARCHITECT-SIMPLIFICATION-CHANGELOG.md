# Site Architect Simplification — Changelog

**Date:** 2026-06-03  
**Scope:** Site Architect backend IA only (navigation, labels, visibility, deploy hub consolidation)  
**Out of scope:** Operations, Services, Leads, SEO, public rendering, routes removal

## Pre-change backups

| Backup | Path |
|--------|------|
| Full bundle | `/var/backups/site-architect-simplification-20260603-184813/` |
| Navigation | `navigation/primary-tabs.blade.php`, `navigation/deployment-hub.blade.php`, `AdminNavigation.php`, `ModuleAccess.php` |
| UI views | `ui/site-architect-views/`, `ui/livewire-site-architect/` |
| Config | `config/deployment_engine.php` |

## Added

| File | Purpose |
|------|---------|
| `app/Support/SiteArchitectNavigation.php` | Central tab IA, role visibility, deploy shortcut steps |
| `tests/Feature/SiteArchitectSimplificationTest.php` | Navigation + route preservation tests |
| `SITE-ARCHITECT-SIMPLIFICATION-CHANGELOG.md` | This file |
| `SITE-ARCHITECT-SIMPLIFICATION-REPORT.md` | Implementation report |

## Modified

| File | Change |
|------|--------|
| `resources/views/site-architect/partials/primary-tabs.blade.php` | New IA groups; role-filtered items; renames |
| `resources/views/site-architect/partials/deployment-hub.blade.php` | Renamed to “Deploy shortcuts”; only on Blueprint/Packages; no duplicate primary tabs |
| `resources/views/site-architect/*-shell.blade.php` | Page titles / welcome lines; hub removed from Studio/Presets/Legacy |
| `resources/views/livewire/site-architect/block-*.blade.php` | UI copy (Blocks Studio/Factory, Templates, Legacy Sections) |
| `resources/views/livewire/site-architect/section-library.blade.php` | Legacy Sections labels |
| `app/Livewire/SiteArchitect/Pages.php` | Managed-block error → Blocks Studio |
| `app/Livewire/SiteArchitect/Blogs.php` | Managed-block error → Blocks Studio |
| `app/Services/WorkspaceGlobalSearch.php` | Search labels for new names |
| `resources/views/operations/services/_composition-guidance.blade.php` | Cross-link labels only |
| `resources/views/operations/services/_detail-page-panel.blade.php` | Cross-link label only |
| `tests/Feature/Phase85CompletionPatchTest.php` | Assert `Templates` page title |

## Renames (UI only)

| Before | After |
|--------|-------|
| Block Studio | **Blocks Studio** |
| Block Factory | **Blocks Factory** |
| Presets / Block Presets | **Templates** |
| Sections (legacy) / Section Library | **Legacy Sections** |

## Navigation structure (after)

```
CONTENT
├ Pages
├ Blogs
├ Navigation
└ Media

BLOCKS
├ Blocks Studio
├ Blocks Factory
└ Templates

DEPLOY (admin, super_admin only)
├ Blueprint Builder
└ Packages

ADVANCED (admin, super_admin only)
├ Module Builder
└ Legacy Sections
```

## Visibility

| Role | Deploy + Advanced tabs |
|------|-------------------------|
| editor, manager | Hidden (routes still work if bookmarked) |
| admin, super_admin | Shown |

## Preserved (unchanged)

- All `site-architect.*` route names and URL paths
- `site-architect.sections.index` → `section-library` redirect
- `site-architect.presets.index` → `block-presets` redirect
- `ContentParser` `{{section:}}` support
- `section_library_items` table and APIs
- `DeploymentEnginePolicy` and Livewire authorization
- Block preset repository / database schema

## Deployment hub

- Removed from: Blocks Studio, Templates, Legacy Sections shells
- Retained (simplified) on: Blueprint Builder, Packages only
- Title changed from “MarkOnMinds Deployment Engine” to “Deploy shortcuts”
