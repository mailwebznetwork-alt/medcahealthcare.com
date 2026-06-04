# Site Architect Simplification — Implementation Report

**Date:** 2026-06-03  
**References:** `SITE-ARCHITECT-UX-FORENSIC-AUTOPSY.md`, `PLATFORM-COMPLETION-FINAL-REPORT.md`, `PLATFORM-COMPLETION-STATUS.md`  
**Backup:** `/var/backups/site-architect-simplification-20260603-184813/`

---

## 1. Objective

Simplify Site Architect **information architecture and labeling** without removing features, routes, parsers, or data structures. No Operations/Services module changes except two **label-only** cross-links in service composition guidance.

---

## 2. Navigation — before vs after

### Before

| Group | Items |
|-------|--------|
| Content | Pages, Blogs, Navigation, Media |
| Blocks | Sections (legacy), Presets, Block Studio, Block Factory |
| Deploy | Blueprint Builder, Packages |
| Advanced | Module Builder |

**Issues:** Legacy sections mixed with daily block tools; “Presets” vs “Studio” unclear; Deploy duplicated by MarkOnMinds Deployment Engine hub on five screens.

### After

| Group | Items | Default editor/manager | Admin / super_admin |
|-------|--------|------------------------|---------------------|
| **Content** | Pages, Blogs, Navigation, Media | ✓ | ✓ |
| **Blocks** | Blocks Studio, Blocks Factory, Templates | ✓ | ✓ |
| **Deploy** | Blueprint Builder, Packages | Hidden | ✓ |
| **Advanced** | Module Builder, Legacy Sections | Hidden | ✓ |

Implementation: `App\Support\SiteArchitectNavigation` + `primary-tabs.blade.php`.

---

## 3. Renamed items (UI only)

| Old label | New label | Routes (unchanged) |
|-----------|-----------|-------------------|
| Block Studio | Blocks Studio | `site-architect.block-studio.index` |
| Block Factory | Blocks Factory | `site-architect.block-factory.index` |
| Presets / Block Presets | Templates | `site-architect.presets.index` → redirect; `block-presets.index` |
| Sections (legacy) / Section Library | Legacy Sections | `site-architect.sections.index` → redirect; `section-library.index` |

---

## 4. Visibility changes

| Workspace | editor | manager | admin / super_admin |
|-----------|--------|---------|---------------------|
| Pages, Blogs, Navigation, Media | Tab visible | Tab visible | Tab visible |
| Blocks Studio, Blocks Factory, Templates | Tab visible | Tab visible | Tab visible |
| Blueprint Builder, Packages | Tab hidden | Tab hidden | Tab visible |
| Module Builder, Legacy Sections | Tab hidden | Tab hidden | Tab visible |

**Note:** Hidden tabs do **not** revoke access. Managers retain `DeploymentEnginePolicy` for blueprint generation via direct URL; editors retain preset/studio policies. Only **discoverability** changed per audit §8.3.

---

## 5. Legacy Sections

- Moved under **Advanced → Legacy Sections**
- Badge: **Legacy** on tab
- Shell banner: **Legacy / Backward Compatibility**
- `{{section:slug}}` parsing, DB, export, and routes **unchanged**
- Deployment hub **removed** from this shell (no duplicate nav)

---

## 6. Templates (formerly Presets)

- Shell title: **Templates**
- In-app copy: “Create template”, “Saved templates”, etc.
- Backend: `BlockPresetRepository`, `block_presets` table, `site-architect.block-presets.*` routes **unchanged**

---

## 7. Deployment duplication — consolidated

| Surface | Before | After |
|---------|--------|-------|
| Primary tabs | Deploy group | Unchanged for admin |
| Deployment hub | On 5 shells; title “MarkOnMinds Deployment Engine”; duplicated Blueprint/Sections/Presets/Studio/Packages | **Deploy shortcuts** on Blueprint + Packages only; links Theme, Global content, Templates, Blocks Studio, Blueprint, Packages |
| Blocks Studio / Templates / Legacy | Included hub | Hub **removed** |

---

## 8. Routes preserved

All existing route names verified in tests:

| Route | Status |
|-------|--------|
| `site-architect.pages.index` | ✓ |
| `site-architect.blogs.index` | ✓ |
| `site-architect.navigation.index` | ✓ |
| `site-architect.media.index` | ✓ |
| `site-architect.block-studio.index` | ✓ |
| `site-architect.block-factory.index` | ✓ |
| `site-architect.presets.index` (301 → block-presets) | ✓ |
| `site-architect.block-presets.index` | ✓ |
| `site-architect.blueprint-builder.index` | ✓ |
| `site-architect.deployment-packages.index` | ✓ |
| `site-architect.modules.index` | ✓ |
| `site-architect.sections.index` (301 → section-library) | ✓ |
| `site-architect.section-library.index` | ✓ |

---

## 9. Permissions preserved

| Policy / gate | Unchanged |
|---------------|-----------|
| `module:site_architect` middleware | ✓ |
| `DeploymentEnginePolicy::manageBlockPresets` | Block Studio, Templates, Legacy Sections |
| `DeploymentEnginePolicy::useBlueprintBuilder` | Blueprint Builder |
| `DeploymentEnginePolicy::managePackages` | Packages |
| `PagePolicy` / block CRUD | ✓ |

---

## 10. Modified files (complete list)

**New**

- `app/Support/SiteArchitectNavigation.php`
- `tests/Feature/SiteArchitectSimplificationTest.php`
- `SITE-ARCHITECT-SIMPLIFICATION-CHANGELOG.md`
- `SITE-ARCHITECT-SIMPLIFICATION-REPORT.md`

**Updated**

- `resources/views/site-architect/partials/primary-tabs.blade.php`
- `resources/views/site-architect/partials/deployment-hub.blade.php`
- `resources/views/site-architect/block-studio-shell.blade.php`
- `resources/views/site-architect/block-presets-shell.blade.php`
- `resources/views/site-architect/block-factory-shell.blade.php`
- `resources/views/site-architect/section-library-shell.blade.php`
- `resources/views/livewire/site-architect/block-factory.blade.php`
- `resources/views/livewire/site-architect/block-studio.blade.php`
- `resources/views/livewire/site-architect/block-presets.blade.php`
- `resources/views/livewire/site-architect/section-library.blade.php`
- `app/Livewire/SiteArchitect/Pages.php`
- `app/Livewire/SiteArchitect/Blogs.php`
- `app/Services/WorkspaceGlobalSearch.php`
- `resources/views/operations/services/_composition-guidance.blade.php` (labels only)
- `resources/views/operations/services/_detail-page-panel.blade.php` (label only)
- `tests/Feature/Phase85CompletionPatchTest.php`

---

## 11. Verification results

```bash
php artisan test --filter=SiteArchitectSimplification   # 5 passed
php artisan test --filter=Phase85CompletionPatchTest    # presets → Templates assertion
```

| Check | Result |
|-------|--------|
| Editor sees Content + Blocks only | Pass |
| Admin sees all four groups | Pass |
| Legacy route alias + banner | Pass |
| Templates route alias | Pass |
| No deploy hub on Blocks Studio | Pass |
| Deploy shortcuts on Blueprint Builder | Pass |

**Manual QA recommended:** Log in as editor → confirm Deploy/Advanced absent; admin → confirm Legacy Sections banner; manager → open `/site-architect/blueprint-builder` directly (should still 403 or allow per policy).

---

## 12. Rollback instructions

1. Restore from backup:
   ```bash
   BACKUP=/var/backups/site-architect-simplification-20260603-184813
   cp "$BACKUP/navigation/primary-tabs.blade.php" resources/views/site-architect/partials/
   cp "$BACKUP/navigation/deployment-hub.blade.php" resources/views/site-architect/partials/
   cp -r "$BACKUP/ui/site-architect-views/"* resources/views/site-architect/
   cp -r "$BACKUP/ui/livewire-site-architect/"* resources/views/livewire/site-architect/
   ```
2. Remove `app/Support/SiteArchitectNavigation.php` and `tests/Feature/SiteArchitectSimplificationTest.php`.
3. Revert Livewire/WorkspaceGlobalSearch/Operations label commits if needed.
4. `php artisan view:clear && php artisan test --filter=SiteArchitect`

---

## 13. Goal alignment

| Goal | Status |
|------|--------|
| Reduce backend complexity | ✓ Fewer visible tabs for daily editors |
| Increase discoverability | ✓ Clear Blocks Studio vs Factory vs Templates |
| Preserve all features | ✓ No routes/APIs removed |
| No platform redesign | ✓ Navigation/labels only |
| No Operations/Services changes | ✓ Label-only cross-links |

---

*End of report.*
