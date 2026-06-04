# Platform Execution Gap Report

**Project:** Medca Health Care — Platform Completion  
**Date:** 2026-06-03  
**Source of truth:** Phase B report, composition repair plan, forensic autopsy, codebase audit (no new strategy docs).

---

## Summary

| Severity | Count | Phase C action |
|----------|-------|----------------|
| Critical | 2 | Fix in this pass |
| High | 8 | Fix in this pass |
| Medium | 9 | Document; fix where trivial |
| Low | 6 | Document only |

---

## Critical

### GAP-C01 — Pages composer can overwrite Git-managed block code

| Field | Detail |
|-------|--------|
| **Evidence** | `Pages::saveBlockInModal()` updates `blocks.code` with no `is_managed` check; managed templates sync to `@include('blocks.*')` via `BlockTemplateSyncService`. |
| **Files** | `app/Livewire/SiteArchitect/Pages.php`, `resources/views/livewire/site-architect/pages.blade.php` |
| **User impact** | Editor replaces `@include` with raw HTML in modal → public site breaks or diverges from Blade templates; frontend ≠ backend. |
| **Recommended fix** | Redirect block edits for managed/schema blocks to Block Studio; forbid `code` updates on `is_managed` in modal save. |
| **Effort** | S (2–4 h) |

### GAP-C02 — Block Factory allows mutating managed block code in DB

| Field | Detail |
|-------|--------|
| **Evidence** | `BlockFactory::saveBlock()` calls `$block->update($data)` including `code` for all blocks; UI shows full code editor for managed blocks. |
| **Files** | `app/Livewire/SiteArchitect/BlockFactory.php`, `resources/views/livewire/site-architect/block-factory.blade.php` |
| **User impact** | Accidental save desyncs DB from `config/block_templates.php` / Git views; `blocks:sync` may overwrite or leave drift. |
| **Recommended fix** | On save for `is_managed`, only persist `is_active` + `custom_css`; readonly code in form; link to Block Studio. |
| **Effort** | S |

---

## High

### GAP-H01 — Hardcoded phone in contact-split and cta-sticky blocks

| Field | Detail |
|-------|--------|
| **Evidence** | `tel:+918884999002` in `resources/views/blocks/shared/contact-split.blade.php`, `cta-sticky.blade.php` (not migrated in Phase B). |
| **Files** | Above + `config/block_content_schemas.php` |
| **User impact** | Global Content phone changes do not apply on pages using these blocks. |
| **Recommended fix** | Add content schemas + `BlockContent` + global tel/display. |
| **Effort** | S |

### GAP-H02 — Hardcoded hero copy on services and locations heroes

| Field | Detail |
|-------|--------|
| **Evidence** | `hero-services.blade.php`, `hero-locations.blade.php` contain static headlines/subheads. |
| **Files** | Block views, `block_content_schemas.php`, Block Studio |
| **User impact** | Marketing copy not editable in Block Studio; duplicate path via Block Factory code. |
| **Recommended fix** | Extend B1 schemas and wire blades. |
| **Effort** | S |

### GAP-H03 — cta-split hardcoded marketing copy

| Field | Detail |
|-------|--------|
| **Evidence** | `resources/views/blocks/shared/cta-split.blade.php` |
| **Files** | Same as H02 |
| **User impact** | Same as H02 |
| **Recommended fix** | Content schema + BlockContent |
| **Effort** | S |

### GAP-H04 — Duplicate block editing paths (Pages modal vs Block Studio)

| Field | Detail |
|-------|--------|
| **Evidence** | Pages list shows `{{block:slug}}` with "Edit" opening code modal; Block Studio owns `settings_json.content` per B1. |
| **Files** | `pages.blade.php`, `Pages.php`, `block-studio` |
| **User impact** | Editors edit `@include` or tokens expecting WYSIWYG; confusion and breakage. |
| **Recommended fix** | Route "Edit" for managed/schema blocks to Block Studio (`?block=slug`). |
| **Effort** | S |

### GAP-H05 — Block Studio not linked from page composer

| Field | Detail |
|-------|--------|
| **Evidence** | No query-param deep link from Pages to preselect block in `BlockStudio::mount()`. |
| **Files** | `BlockStudio.php`, pages partial |
| **User impact** | Hidden workflow; editors do not discover correct editor. |
| **Recommended fix** | `?block=` mount + Studio button on page parts list. |
| **Effort** | S |

### GAP-H06 — ~60 managed blocks lack `content` schema (partial B1 coverage)

| Field | Detail |
|-------|--------|
| **Evidence** | 10 slugs in `block_content_schemas.php`; 70 templates in `block_templates.php`. |
| **Files** | `config/block_content_schemas.php` |
| **User impact** | Most blocks still code-only; acceptable for data-driven blocks, but marketing CTAs/heroes inconsistent. |
| **Recommended fix** | Incremental schema additions for blocks with static copy (prioritize public pages seeder slugs). |
| **Effort** | M (ongoing) — **not fully closed in this pass** |

### GAP-H07 — form-callback is presentation-only (no embedded submit UI)

| Field | Detail |
|-------|--------|
| **Evidence** | `form-callback.blade.php` links to `/contact`; no Livewire form; leads via `POST /api/leads` per `contact_forms.php`. |
| **Files** | `config/contact_forms.php`, contact page tokens |
| **User impact** | Expectation of inline form in block; actual submission path is API/placement elsewhere. |
| **Recommended fix** | Document + optional `{{module:}}` when form module exists — **no new module in this pass**. |
| **Effort** | M (deferred) |

### GAP-H08 — Service Operations SEO fields still post when page SEO canonical (readonly UX only)

| Field | Detail |
|-------|--------|
| **Evidence** | Phase B added readonly UI; `ServiceController::syncSeo` still accepts posted SEO unless server-side guard added. |
| **Files** | `ServiceController.php`, `_form.blade.php` |
| **User impact** | Low if editors respect UI; power users can override canonical page SEO via POST. |
| **Recommended fix** | Skip `syncSeo` meta fields when `ServiceSeoOwnership::pageSeoOverridesService($linkedPage)`. |
| **Effort** | S |

---

## Medium

### GAP-M01 — hero-careers includes non-block partial

| Field | Detail |
|-------|--------|
| **Evidence** | `hero-careers.blade.php` → `@include('careers.partials.hub-hero')` |
| **Files** | `resources/views/careers/partials/hub-hero.blade.php` |
| **User impact** | Careers hero not editable via Block Studio. |
| **Recommended fix** | Inline BlockContent in hero-careers or schema on wrapper. |
| **Effort** | S |

### GAP-M02 — Section Library UI still allows create (0 DB rows)

| Field | Detail |
|-------|--------|
| **Evidence** | Deprecation banner added Phase B; create UI remains. |
| **Files** | `section-library.blade.php`, `SectionLibrary.php` |
| **User impact** | Editors may create unused sections. |
| **Recommended fix** | Disable create when deprecated flag set. |
| **Effort** | S |

### GAP-M03 — Page preview iframe stale until manual refresh

| Field | Detail |
|-------|--------|
| **Evidence** | iframe `src` set once; no `wire:key` bump on save. |
| **Files** | `pages.blade.php`, `Pages.php` |
| **User impact** | Preview mismatch after save until reload. |
| **Recommended fix** | Bump preview nonce on `savePage`. |
| **Effort** | S |

### GAP-M04 — Blog admin preview minimal

| Field | Detail |
|-------|--------|
| **Evidence** | `blogs.preview` returns `layouts.app` with `$blog` only — uses `ContentParser::parse($blog->content)` in layout (OK). |
| **Files** | `routes/web.php` |
| **User impact** | None if content uses tokens; low risk. |
| **Recommended fix** | Add test parity with page preview. |
| **Effort** | S |

### GAP-M05 — `careers-listing` duplicate module alias

| Field | Detail |
|-------|--------|
| **Evidence** | `config/modules.php` maps two keys to `JobPortal::class` |
| **Files** | `config/modules.php` |
| **User impact** | Confusing module picker labels only. |
| **Recommended fix** | Document alias; remove if unused in pages. |
| **Effort** | S |

### GAP-M06 — home/overview blocks hardcoded service marketing

| Field | Detail |
|-------|--------|
| **Evidence** | `services-overview-home`, `locations-overview-home` not in content schema |
| **Files** | `resources/views/blocks/home/*` |
| **User impact** | Home teasers require code edit. |
| **Recommended fix** | Phase D schema expansion. |
| **Effort** | M |

### GAP-M07 — Orphan active block vs template count (71 vs 70)

| Field | Detail |
|-------|--------|
| **Evidence** | tinker: 71 active blocks, 70 templates |
| **Files** | DB `blocks`, `block_templates.php` |
| **User impact** | One custom block may be intentional. |
| **Recommended fix** | Admin report of non-managed slugs not in templates. |
| **Effort** | S |

### GAP-M08 — Public service fallback template separate from page composer

| Field | Detail |
|-------|--------|
| **Evidence** | `public.services.show` when no detail page |
| **Files** | `ServicePublicController`, `public/services/show.blade.php` |
| **User impact** | Expected when no linked page; SEO from service table. |
| **Recommended fix** | Already documented in composition guidance. |
| **Effort** | — |

### GAP-M09 — ContentSeoAutoFill mutates page SEO on save (observer)

| Field | Detail |
|-------|--------|
| **Evidence** | `PageObserver::saving` → `ContentSeoAutoFillService` |
| **Files** | `PageObserver.php` |
| **User impact** | Empty page fields auto-filled — can surprise editors. |
| **Recommended fix** | Document; no change (existing growth feature). |
| **Effort** | — |

---

## Low

### GAP-L01 — Element library (70 templates) docs-only, no admin route

**Evidence:** PLATFORM-BIBLE; B6 decision. **Impact:** None (intentional). **Effort:** —

### GAP-L02 — Marketing Insights Gemini key

**Evidence:** `MarketingInsightsService` uses `config('gemini.api_key')`. **Impact:** None when configured. **Effort:** —

### GAP-L03 — Placeholder text in whatsapp-configure (918884999002)

**Evidence:** settings partial placeholder only. **Impact:** Cosmetic. **Effort:** —

### GAP-L04 — Stale compiled views in `storage/framework/views`

**Evidence:** old phone in cached views. **Impact:** Cleared on `view:clear`. **Fix:** `php artisan view:clear` in deploy. **Effort:** —

### GAP-L05 — No `{{module:contact-form}}` registered

**Evidence:** `config/modules.php` only job-portal. **Impact:** form-callback directs to /contact. **Effort:** M deferred.

### GAP-L06 — Deployment / blueprint builder low usage

**Evidence:** No pages reference blueprint in seeder. **Impact:** Advanced feature dormant. **Effort:** —

---

## Fix batch (completion pass 2026-06-03)

| ID | Status |
|----|--------|
| GAP-C01 | **Fixed** |
| GAP-C02 | **Fixed** |
| GAP-H01 | **Fixed** |
| GAP-H02 | **Fixed** |
| GAP-H03 | **Fixed** |
| GAP-H04 | **Fixed** |
| GAP-H05 | **Fixed** |
| GAP-H08 | **Fixed** |
| GAP-M02 | **Fixed** |
| GAP-M03 | **Fixed** |
| GAP-H06 | **Fixed** (all MedcaPublicPagesSeeder blocks; 21 schemas total) |
| GAP-H07 | **Fixed** (web lead form + `LeadIngestionService`) |
| GAP-M01 | **Fixed** (hero-careers) |
| GAP-M02 | **Fixed** (create UI hidden) |
| GAP-M03 | **Fixed** (preview refresh on save) |
| Others | See `PLATFORM-COMPLETION-STATUS.md` |
