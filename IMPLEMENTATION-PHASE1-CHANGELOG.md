# Implementation Phase 1 — Changelog

**Date:** 2026-06-03  
**Backup location:** `/var/backups/medca-phase1-services-20260603-155028`  
**Contents:** `database.sqlite`, `project-phase1-snapshot.tar.gz` (pre-change PHP/Blade snapshot)

**Scope:** P0 recovery only — content, procedures, featured image, gallery, `syncMedia` wiring.  
**Not in scope:** syncSeo, syncFaqs, syncSchema, tabs redesign, categories, packages, migrations.

---

## Files modified

| File | Change summary |
|------|----------------|
| `app/Http/Controllers/Operations/Services/ServiceController.php` | Persist content fields on create/update; call `syncMedia()`; add `contentAttributesFromValidated()`; gallery remove via `remove_gallery[]` in `syncMedia()` |
| `app/Http/Requests/Operations/Services/StoreServiceRequest.php` | Use `NormalizesServiceListingLines`; validation for content + media |
| `app/Http/Requests/Operations/Services/UpdateServiceRequest.php` | Same as store request |
| `app/Http/Requests/Operations/Services/Concerns/NormalizesServiceListingLines.php` | Phase 1: normalize `procedures` only (avoid wiping other JSON lists) |
| `resources/views/operations/services/_form.blade.php` | New **Content** and **Media** sections (no tab redesign) |
| `tests/Feature/OperationsServicesStoreTest.php` | Phase 1 tests: content create, media update, public render |

## Files not modified (per rules)

- `routes/web.php`
- `app/Services/ContentParser.php`
- `app/Http/Controllers/Public/ServicePublicController.php` (behavior unchanged; reads existing columns)
- Migrations / database schema
- `deployment_packages` / Site Architect modules
- syncSeo / syncFaqs / syncSchema methods (left unwired)

## New files

| File | Purpose |
|------|---------|
| `IMPLEMENTATION-PHASE1-CHANGELOG.md` | This log |
| `IMPLEMENTATION-PHASE1-REPORT.md` | Verification and rollback guide |

## Database

No migrations. Uses existing columns:

- `services.short_summary`, `description`, `procedures`, `featured_image`, `gallery`

---

## Hotfix (2026-06-03) — Create service button

| File | Change |
|------|--------|
| `resources/views/components/dynamic-fields/unified-table.blade.php` | Move schema `<form>` to `@push('schema-forms')` — fixes invalid nested form breaking main submit |
| `resources/views/components/layouts/markonminds.blade.php` | `@stack('schema-forms')` before scripts |
| `resources/views/operations/services/index.blade.php` | Empty-state **Create service** link |

## Rollback (quick)

```bash
BACKUP=/var/backups/medca-phase1-services-20260603-155028
cd /var/www/medcahealthcare
tar -xzf "$BACKUP/project-phase1-snapshot.tar.gz" -C /
cp "$BACKUP/database.sqlite" database/database.sqlite
php artisan view:clear
```

Restore ownership on `database.sqlite` if needed: `chown www-data:www-data database/database.sqlite`
