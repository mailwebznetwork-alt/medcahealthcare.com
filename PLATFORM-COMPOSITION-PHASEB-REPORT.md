# Platform Composition — Phase B Report

**Project:** Medca Health Care  
**Date:** 2026-06-03  
**Scope:** Recovery and consolidation per approved decisions B1–B6 (no new tables, no new service systems, no visual builder).

**Source of truth:** `PLATFORM-COMPOSITION-REPAIR-PLAN.md`, `SITE-COMPOSITION-OWNERSHIP-MAP.md`, `PLATFORM-FORENSIC-AUTOPSY.md`, `SERVICES-ARCHITECTURE-DECISION-REPORT.md`, Phase A backup manifest.

---

## 1. Executive summary

Phase B establishes **one owner per content type**, **one preview/render path** for pages, and **block-first** marketing copy editing without redesigning the platform.

| Goal | Status |
|------|--------|
| Block-owned hero/CTA copy (B1) | Done — `settings_json.content` + Block Studio |
| Preview-first page editor (B2) | Done — production iframe on edit |
| Contact form ownership clarity (B3) | Done — `config/contact_forms.php` + block copy |
| Page SEO canonical (B4) | Done — admin UX + existing `layouts.app` precedence |
| Section Library deprecation (B5) | Done — UI deprecated; parser retained |
| Elements not in admin UX (B6) | Done — documented in Block Studio |

---

## 2. Ownership changes applied

| Content type | Owner (canonical) | Editor path |
|--------------|-------------------|-------------|
| Hero / CTA marketing copy | `blocks.settings_json.content` | Site Architect → Block Studio → Content panel |
| Phone, WhatsApp, address, brand | Global Content (`global_content_variables`) | Settings → Global Content |
| Page layout order | `pages.content` tokens | Site Architect → Pages |
| Page SEO (when linked) | `pages.meta_*`, `h1`, headings | Site Architect → Pages |
| Service SEO | `service_seo` table | Operations → Service → SEO tab (fallback; readonly when page SEO wins) |
| Lead submission | `POST /api/leads` | API / integrations (not Global Content) |
| Form placement | `pages.content` | Pages composer |
| Form presentation | Block views (`form-callback`, `contact-info`, …) | Block Studio + Blade |
| Section groups (legacy) | `section_library_items` (deprecated UI) | Still parsed via `{{section:slug}}` |

---

## 3. Modified files list

See `PHASEB-MODIFICATION-LOG.md` (29 entries).

**New:**

- `config/block_content_schemas.php`
- `config/contact_forms.php`
- `app/Support/BlockContent.php`
- `app/Services/Operations/ServiceSeoOwnership.php`
- `resources/views/operations/services/partials/_seo-canonical-banner.blade.php`
- `tests/Unit/BlockContentTest.php`
- `tests/Feature/PlatformCompositionPhaseBTest.php`

**Key updates:**

- Block Studio + `BlockSettingsEditor` persist `content`
- 10 block Blade templates read `BlockContent` + global phone/tel
- Pages editor: production preview iframe
- Service form: canonical SEO banner + readonly fields
- Section Library shell + nav: deprecated labels

---

## 4. Deprecated items

| Item | Action | Backward compatibility |
|------|--------|------------------------|
| Section Library (admin) | Marked deprecated in `config/platform_composition.php`, shell banner, tab label "Sections (legacy)" | Routes unchanged; `ContentParser` + `SectionLibraryRepository` unchanged |
| Hardcoded hero/CTA strings in Blade | Replaced with schema defaults (same text until edited in Block Studio) | Public output unchanged if `settings_json.content` empty |
| Service SEO as primary (when page linked + filled) | Readonly + banner in Operations | Service SEO still stored; used for `/services/CODE` fallback |

**Not removed:** Section Library database table, routes, or `{{section:}}` token support.

---

## 5. Remaining blockers

None for Phase B scope. See `PLATFORM-COMPOSITION-BLOCKERS.md` for **optional future** work (embedded contact module UI, bulk content seed for all blocks, route removal).

| Item | Requires redesign? |
|------|-------------------|
| On-page embedded lead form (Livewire) | New module UI — out of Phase B |
| Bulk migrate all 71 blocks to `content` JSON | Optional artisan command — not required |
| Delete Section Library routes | Product decision — not required for consolidation |

---

## 6. Verification results

```text
php artisan test --filter='BlockContentTest|PlatformCompositionPhaseBTest|OperationsServices'
```

| Suite | Result |
|-------|--------|
| `BlockContentTest` | 3 passed |
| `PlatformCompositionPhaseBTest` | 5 passed |
| `OperationsServices` (regression) | 9 passed |
| **Total** | **17 passed** |

**Manual checks recommended:**

1. Block Studio → `hero-home` → edit Content → Save → Preview shows updated headline.
2. Pages → Edit `home` (or any saved page) → iframe preview loads `site-architect.pages.preview`.
3. Operations → Service with linked page + page meta filled → SEO tab shows canonical banner and readonly fields.
4. `/site-architect/section-library` → deprecation notice visible.
5. Public home/contact — phone links use Global Content when set.

---

## 7. Rollback instructions

### 7.1 Full rollback (code + DB)

```bash
BACKUP=/var/backups/medca-phaseb-composition-20260603-164920
cd /var/www/medcahealthcare

# Restore project files from tarball (review diff first)
tar -xzf "$BACKUP/project-snapshot.tar.gz" -C /var/www/medcahealthcare

# Restore database (adjust credentials to your environment)
mysql -u USER -p DATABASE < "$BACKUP/database/"*.sql

# Restore config/routes if needed
cp -a "$BACKUP/config/"* config/
cp -a "$BACKUP/routes/"* routes/

php artisan config:clear
php artisan view:clear
php artisan test --filter=OperationsServices
```

### 7.2 Partial rollback (Phase B files only)

```bash
cd /var/www/medcahealthcare
git checkout -- config/block_content_schemas.php config/contact_forms.php config/platform_composition.php
git checkout -- app/Support/BlockContent.php app/Services/Operations/ServiceSeoOwnership.php
git checkout -- app/Services/Deployment/BlockSettingsEditor.php
git checkout -- app/Livewire/SiteArchitect/BlockStudio.php app/Livewire/SiteArchitect/Pages.php
git checkout -- resources/views/blocks/ resources/views/livewire/site-architect/
git checkout -- resources/views/operations/services/_form.blade.php
git checkout -- resources/views/site-architect/
```

Remove new files if not in git: `BlockContent.php`, `ServiceSeoOwnership.php`, `_seo-canonical-banner.blade.php`, Phase B tests, this report.

### 7.3 Data note

Block `settings_json.content` written via Block Studio after Phase B is **forward-only** in DB; rollback tarball restores pre-Phase-B DB state.

---

## 8. Decision traceability

| ID | Decision | Evidence in codebase |
|----|----------|---------------------|
| B1 | Block content owner | `block_content_schemas.php`, `BlockContent::get()`, hero/CTA blades |
| B2 | Preview-first | `pages.blade.php` iframe → `PagePublicPreviewService` route |
| B3 | Form ownership | `contact_forms.php`, `form-callback.blade.php` |
| B4 | Page SEO wins | `ServiceSeoOwnership`, `layouts/app.blade.php` (pre-existing), service form UX |
| B5 | Deprecate section UI | `platform_composition.section_library_deprecated` |
| B6 | Blocks first-class | Block Studio messaging; no Element Library admin route |

---

## 9. Related backups

| Backup | Path |
|--------|------|
| Phase B | `/var/backups/medca-phaseb-composition-20260603-164920/` |
| Phase A repair | `/var/backups/medca-composition-repair-20260603-164521/` |

---

*Report generated as part of Platform Composition Phase B execution.*
