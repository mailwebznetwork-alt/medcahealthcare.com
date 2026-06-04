# Page Builder UX Changelog

**Version:** Page Builder Experience Refactor (UX-only)  
**Date:** 2026-06-02  
**Breaking changes:** None (routes, permissions, parsers, DB, rendering unchanged)

---

## Added

| Item | Path |
|------|------|
| Section catalog config (display names, categories, overrides) | `config/page_builder_sections.php` |
| `PageSectionCatalog` service | `app/Services/SiteArchitect/PageSectionCatalog.php` |
| Livewire concern for section picker | `app/Livewire/SiteArchitect/Concerns/InteractsWithPageSectionPicker.php` |
| Visual section picker modal | `resources/views/livewire/site-architect/partials/section-picker-modal.blade.php` |
| Preview tile component (gradient) | `resources/views/site-architect/partials/section-preview-tile.blade.php` |
| Compose journey partial | `resources/views/site-architect/partials/compose-journey.blade.php` |
| UX copy helper | `app/Support/SiteArchitectUxCopy.php` |
| Tests | `tests/Feature/PageSectionCatalogTest.php`, `tests/Feature/PageBuilderUxTest.php` |

---

## Changed (labels & UX only)

### Pages & Blogs

- **Add block** → **Add section**
- Slug `<select>` replaced by **visual picker modal** (search + category filter)
- Section list shows **catalog display name** (e.g. Healthcare Hero, CTA Banner)
- `{{block:slug}}` token shown only when `SiteArchitectNavigation::showsDeveloperBlockTools()` is true
- **Fix:** Blade syntax for token display (was causing HTTP 500 — unclosed `(` in compiled view)

### Block Studio

- Shell title: **Section Content**
- Section selector: optgroups by **picker category**
- Field panels: **Section content**, **Images & media**, **Section layout**, **Style variant**
- Helper paragraph for global phone/WhatsApp via Settings

### Navigation (`SiteArchitectNavigation`)

- Group **Sections**: Section Content, Style Templates; Blocks Factory inserted only for non-editor dev roles
- **Editor:** hides Factory tab; hides Deploy (Blueprint, Packages) and Advanced (Module Builder, Legacy Sections)
- **Admin:** unchanged access via tabs; all routes still registered

### Config overrides (examples)

| Slug (internal) | Display name |
|-----------------|--------------|
| `hero-healthcare` | Healthcare Hero |
| `cta-banner` | CTA Banner |
| `near-you-home` | Services Near You |
| `before-after` | Before & After Gallery |

Full list: `config/page_builder_sections.php` → `overrides`.

---

## Unchanged (explicit)

- All `site-architect.*` routes
- `ContentParser` and `{{block:slug}}` token format in stored content
- Block Factory, Blueprint Builder, deployment packages — behavior and URLs
- Permissions / policies
- Block Blade templates and public rendering
- Database schema

---

## Tests

| Test file | Covers |
|-----------|--------|
| `PageSectionCatalogTest.php` | Display name resolution, picker grouping |
| `PageBuilderUxTest.php` | Picker flow, editor nav without Factory |
| `SiteArchitectSimplificationTest.php` | Role nav, route aliases, studio shell |

**Last run:** 10 tests passed (Page Builder + Site Architect simplification bundle).

---

## Before / after (quick reference)

| UI element | Before | After |
|------------|--------|-------|
| Primary add control | Add block + slug dropdown | Add section + card modal |
| List item label | `hero-healthcare` | Healthcare Hero |
| Studio page title | Block Studio | Section Content |
| Editor side nav | Blocks Factory visible | Factory tab hidden |
| Token in list | Always visible | Admin/manager dev tools only |

---

## Verification commands

```bash
cd /var/www/medcahealthcare
php artisan view:clear
php artisan test tests/Feature/PageSectionCatalogTest.php \
  tests/Feature/PageBuilderUxTest.php \
  tests/Feature/SiteArchitectSimplificationTest.php
```

---

## Rollback

1. Git revert or restore files listed in **Added** and **Changed** sections above.  
2. `php artisan view:clear`  
3. Re-run tests (see Verification commands).

No migrations to reverse.

---

## Remaining work (optional, not in this changelog)

- PNG/SVG preview assets per section in picker cards  
- Hide **Style Templates** for editor role (product decision)  
- Align Blogs compose panel headings 1:1 with Pages  
- Softer label for page **slug** field on create form  

---

## Modified files (complete list for this initiative)

```
config/page_builder_sections.php
app/Services/SiteArchitect/PageSectionCatalog.php
app/Livewire/SiteArchitect/Concerns/InteractsWithPageSectionPicker.php
app/Livewire/SiteArchitect/Pages.php
app/Livewire/SiteArchitect/Blogs.php
app/Livewire/SiteArchitect/BlockStudio.php
app/Support/SiteArchitectNavigation.php
app/Support/SiteArchitectUxCopy.php
resources/views/livewire/site-architect/pages.blade.php
resources/views/livewire/site-architect/blogs.blade.php
resources/views/livewire/site-architect/block-studio.blade.php
resources/views/livewire/site-architect/partials/section-picker-modal.blade.php
resources/views/site-architect/partials/section-preview-tile.blade.php
resources/views/site-architect/partials/compose-journey.blade.php
resources/views/site-architect/block-studio-shell.blade.php
resources/views/site-architect/partials/primary-tabs.blade.php
tests/Feature/PageSectionCatalogTest.php
tests/Feature/PageBuilderUxTest.php
tests/Feature/SiteArchitectSimplificationTest.php
```

Documentation:

```
PAGE-BUILDER-UX-AUTOPSY.md
PAGE-BUILDER-UX-REPORT.md
PAGE-BUILDER-CHANGELOG.md
```
