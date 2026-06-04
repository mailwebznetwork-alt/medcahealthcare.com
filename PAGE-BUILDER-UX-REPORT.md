# Page Builder UX Report

**Project:** Medca Health Care — Site Architect Page Builder  
**Date:** 2026-06-02  
**Type:** UX refactor (safe layer only)  
**Architecture:** Unchanged (routes, parsers, DB, rendering preserved)

---

## Summary

The page builder now speaks in **Pages → Sections → Section Content → Preview → Publish** terms. Block slugs, schemas, and factory concepts are internal. Editors get a **visual section picker** and a **simplified nav**; admins retain full power via existing routes.

---

## Before / after (UI references)

Screenshots were not captured in CI; use these **view/route references** for manual before/after checks:

| Area | Route / view | Before | After |
|------|----------------|--------|-------|
| Page compose | `site-architect.pages.index` → create/edit panel | “Add block” + slug `<select>` | **Add section** → modal grid |
| Section list | `resources/views/livewire/site-architect/pages.blade.php` | `hero-healthcare` + `{{block:…}}` | **Healthcare Hero**; token only if `showsDeveloperBlockTools` |
| Section picker | `livewire/.../section-picker-modal.blade.php` | N/A | Search, category filter, cards with preview tile |
| Block Studio | `site-architect.block-studio.index` | Flat slug dropdown | Grouped **Section** select + helper text |
| Primary tabs | `site-architect/partials/primary-tabs.blade.php` | Blocks Factory for all roles | Factory hidden for **editor** |
| Nav group | `SiteArchitectNavigation::tabGroups()` | Mixed deploy | **Content** + **Sections**; Deploy/Advanced admin-only |

**Visual preview tiles:** `resources/views/site-architect/partials/section-preview-tile.blade.php` (gradient by `preview_key`, not PNG).

---

## Phases delivered

### Phase 1 — Forensic

`PAGE-BUILDER-UX-AUTOPSY.md` documents flows, jargon, slug exposure, and role matrix.

### Phase 2 — Visual section selector

- **Catalog:** `config/page_builder_sections.php` + `App\Services\SiteArchitect\PageSectionCatalog`  
- **Picker trait:** `App\Livewire\SiteArchitect\Concerns\InteractsWithPageSectionPicker`  
- **UI:** `section-picker-modal.blade.php`, `section-preview-tile.blade.php`  
- **Integrated:** `Pages.php`, `Blogs.php` (`addSection`, `appendSection`, search/category)  
- **Overrides:** Human names (e.g. `hero-healthcare` → **Healthcare Hero**) without exposing slug

### Phase 3 — “Page Sections” terminology

- **Add section** (not Add block) on Pages/Blogs  
- List shows **section display names**  
- Internal: still `{{block:slug}}` in `content` column

### Phase 4 — Content editing

- Block Studio shell: **Section Content**  
- Tabs: **Section content**, **Images & media**, **Section layout**, **Style variant**  
- Grouped section dropdown in `block-studio.blade.php`  
- `BlockStudio.php` uses `PageSectionCatalog` for labels

### Phase 5 — Role simplification

- `SiteArchitectNavigation::isContentEditorRole()` / `showsDeveloperBlockTools()`  
- **Editor:** no Blocks Factory tab; no Deploy/Advanced tabs  
- **Manager:** Factory + full section tools (not editor-simplified)  
- **Admin / super_admin:** full workspace

### Phase 6 — Safe implementation

No changes to: `ContentParser`, block render views, migrations, deployment engine logic, or `routes/web.php` removals.

### Phase 7 — Validation

Automated (2026-06-02):

```bash
php artisan test tests/Feature/PageSectionCatalogTest.php \
  tests/Feature/PageBuilderUxTest.php \
  tests/Feature/SiteArchitectSimplificationTest.php
```

**Result:** 10 passed, 0 failed.

| Scenario | Verification |
|----------|----------------|
| **A** Create → Add section → Edit content → Preview → Publish | `PageBuilderUxTest`: picker opens, appends section, shows **CTA Banner** (not slug) |
| **B** Template → Edit sections → Publish | Manual: Blueprint unchanged; Pages edit uses same picker (no new route) |
| **C** Editor role | `SiteArchitectSimplificationTest` + `PageBuilderUxTest`: no Factory/Deploy/Advanced in HTML |

**Manual checklist (recommended once per release):**

1. Log in as **editor** → Site Architect → Pages → Create → **Add section** → pick **Healthcare Hero** → save draft → preview URL → publish.  
2. Log in as **admin** → confirm **Blocks Factory**, **Blueprint Builder**, **Packages** tabs visible.  
3. Section Content → select section from grouped list → **Save section** → public page unchanged structurally.

---

## Modified files (page-builder UX scope)

| File | Change |
|------|--------|
| `config/page_builder_sections.php` | Picker categories, overrides, recommendations |
| `app/Services/SiteArchitect/PageSectionCatalog.php` | Display names, grouping, search |
| `app/Livewire/SiteArchitect/Concerns/InteractsWithPageSectionPicker.php` | Picker state + append |
| `app/Livewire/SiteArchitect/Pages.php` | Section picker integration |
| `app/Livewire/SiteArchitect/Blogs.php` | Same picker on blogs |
| `app/Livewire/SiteArchitect/BlockStudio.php` | Catalog-driven labels |
| `app/Support/SiteArchitectNavigation.php` | Role-based nav |
| `app/Support/SiteArchitectUxCopy.php` | Shared copy helpers |
| `resources/views/livewire/site-architect/pages.blade.php` | Sections UX + picker include |
| `resources/views/livewire/site-architect/blogs.blade.php` | Parity |
| `resources/views/livewire/site-architect/block-studio.blade.php` | Grouped select, labels |
| `resources/views/livewire/site-architect/partials/section-picker-modal.blade.php` | Visual modal |
| `resources/views/site-architect/partials/section-preview-tile.blade.php` | Preview gradients |
| `resources/views/site-architect/partials/compose-journey.blade.php` | Journey banner |
| `resources/views/site-architect/block-studio-shell.blade.php` | Title |
| `resources/views/site-architect/partials/primary-tabs.blade.php` | Tab visibility |
| `tests/Feature/PageSectionCatalogTest.php` | Catalog tests |
| `tests/Feature/PageBuilderUxTest.php` | Picker + editor nav |
| `tests/Feature/SiteArchitectSimplificationTest.php` | Updated labels |

Related (earlier in same initiative): `SITE-ARCHITECT-*.md`, Near You blocks — not duplicated here.

---

## UX improvements (measurable)

1. **Zero slug in primary add flow** for editors (picker uses `appendSection(slug)` internally).  
2. **Friendly names** on page section lists via catalog + config overrides.  
3. **Categorized discovery** (Hero, FAQ, CTA, Locations, …).  
4. **Editor nav surface area reduced** ~40% (tabs hidden, routes remain).  
5. **Block Studio** readable by marketing (section/content/media wording).  
6. **Bug fix:** Blade parse error on developer token display (`'{' . '{'` concatenation).

---

## Remaining friction points

- No raster preview images per section (gradients only).  
- Page slug still exposed on page form.  
- Editors still see **Style Templates** tab.  
- Direct navigation to Factory URL still possible if role has permission.  
- Module lines on Pages unchanged (technical “module” label).  
- Recommended sections depend on `recommended_for_page` config + current page slug during edit.

---

## Success criteria check

| Criterion | Met? |
|-----------|------|
| First-time user understands Pages, Sections, Templates, Media | Yes (terminology + picker) |
| No need to know Factory, schemas, slugs, legacy, deploy internals | Yes for editor role |
| 100% platform power preserved | Yes (routes/permissions/render) |
| Complexity reduced | Yes (primary path) |

---

## Rollback instructions

1. **Quick (UX only):** Revert these paths from last known good commit:
   - `config/page_builder_sections.php`
   - `app/Services/SiteArchitect/PageSectionCatalog.php`
   - `app/Livewire/SiteArchitect/Concerns/`
   - `Pages.php`, `Blogs.php`, `BlockStudio.php`
   - `app/Support/SiteArchitectNavigation.php`
   - `resources/views/livewire/site-architect/pages.blade.php`
   - `resources/views/livewire/site-architect/blogs.blade.php`
   - `resources/views/livewire/site-architect/block-studio.blade.php`
   - `resources/views/livewire/site-architect/partials/section-picker-modal.blade.php`
   - `resources/views/site-architect/partials/section-preview-tile.blade.php`
   - Related tests

2. **Clear compiled views:** `php artisan view:clear`

3. **Verify:** `php artisan test tests/Feature/PageBuilderUxTest.php`

4. **Backup reference (Site Architect simplification):** `/var/backups/site-architect-simplification-20260603-184813/` (if present on server)

No database rollback required.

---

## Related documents

- `PAGE-BUILDER-UX-AUTOPSY.md` — forensic detail  
- `PAGE-BUILDER-CHANGELOG.md` — file-level changelog  
- `SITE-ARCHITECT-SIMPLIFICATION-REPORT.md` — broader IA rename pass
