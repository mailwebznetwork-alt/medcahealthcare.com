# Platform Completion Changelog

**Latest backup:** `/var/backups/medca-platform-completion-final-20260603-170939/`  
**Gap report:** `PLATFORM-EXECUTION-GAP-REPORT.md`

## 2026-06-03 — Final execution cycle (catalog + parity)

### High (resolved)

| ID | Change |
|----|--------|
| SCHEMA-COVERAGE | `block_content_schemas_catalog.php` + merge in `block_content_schemas.php` → **70** schemas; `BlockContent::hasSchema()` for all template slugs |
| BLOG-PARITY | `Blogs.php` + `blogs.blade.php`: preview iframe, `previewRefreshNonce`, managed-block guard, Studio redirect, stay-in-edit on save, Studio links in composer |
| REVIEWS-GRID | Uses `Review::comment` + `Service::approvedReviews()` when `$service` present |

### Medium (addressed)

| ID | Change |
|----|--------|
| EL-CATALOG-H2 | `scripts/patch-block-headlines.php` + `<x-blocks.marketing-headline />` on 24 catalog blades |
| LOC-PINS | `locations-coverage` reads `$pinCodes` from render context when set |
| MARKETING-HEADLINE | New `resources/views/components/blocks/marketing-headline.blade.php` |

### Files modified (final cycle)

- `config/block_content_schemas.php`, `config/block_content_schemas_catalog.php` (new)
- `app/Support/BlockContent.php`
- `app/Livewire/SiteArchitect/Blogs.php`
- `resources/views/livewire/site-architect/blogs.blade.php`
- `resources/views/components/blocks/marketing-headline.blade.php` (new)
- `resources/views/blocks/locations/locations-coverage.blade.php`
- `resources/views/blocks/shared/reviews-grid.blade.php`
- `scripts/patch-block-headlines.php` (new)
- 24× `resources/views/blocks/**/*.blade.php` (headline component)

### Verification

```bash
php artisan test --filter='BlockContent|PlatformComposition|PlatformCompletion|PublicLead|LeadCapture|OperationsServices|PagePreview'
```

**26 passed** (2026-06-03 final execution).

### Rollback

```bash
BACKUP=/var/backups/medca-platform-completion-final-20260603-170939
cp -a "$BACKUP/block_content_schemas.php" "$BACKUP/block_content_schemas_catalog.php" config/
cp -a "$BACKUP/BlockContent.php" app/Support/
cp -a "$BACKUP/Blogs.php" app/Livewire/SiteArchitect/
php artisan config:clear && php artisan view:clear
```

---

## 2026-06-03 — Completion pass (post Phase B)

### Critical

| ID | Change |
|----|--------|
| GAP-C01 | `Pages::editBlockFromPart` redirects managed/schema blocks to Block Studio; `saveBlockInModal` rejects `is_managed` code saves |
| GAP-C02 | `BlockFactory::saveBlock` updates only `custom_css` + `is_active` for managed blocks; factory form readonly for slug/name/code |

### High

| ID | Change |
|----|--------|
| GAP-H01 | `contact-split`, `cta-sticky` → BlockContent + global phone |
| GAP-H02 | `hero-services`, `hero-locations` content schemas + blades |
| GAP-H03 | `cta-split` content schema + blade |
| GAP-H04 | Pages composer "Studio" + redirect for managed/schema blocks |
| GAP-H05 | `BlockStudio::mount` accepts `?block=` query param |
| GAP-H08 | `ServiceController::syncSeo` skips meta/h1/h2/h3 when page SEO canonical |

### Medium (addressed)

| ID | Change |
|----|--------|
| GAP-M02 | `SectionLibrary::createSection` blocked when deprecated |
| GAP-M03 | Page save keeps edit mode + `previewRefreshNonce` refreshes iframe |

### Files modified

- `config/block_content_schemas.php`
- `resources/views/blocks/services/hero-services.blade.php`
- `resources/views/blocks/locations/hero-locations.blade.php`
- `resources/views/blocks/shared/contact-split.blade.php`
- `resources/views/blocks/shared/cta-sticky.blade.php`
- `resources/views/blocks/shared/cta-split.blade.php`
- `app/Livewire/SiteArchitect/Pages.php`
- `app/Livewire/SiteArchitect/BlockFactory.php`
- `app/Livewire/SiteArchitect/BlockStudio.php`
- `app/Livewire/SiteArchitect/SectionLibrary.php`
- `app/Http/Controllers/Operations/Services/ServiceController.php`
- `resources/views/livewire/site-architect/pages.blade.php`
- `resources/views/livewire/site-architect/block-factory.blade.php`
- `tests/Feature/PlatformCompletionCriticalTest.php`

### Files created

- `PLATFORM-EXECUTION-GAP-REPORT.md`
- `PLATFORM-COMPLETION-CHANGELOG.md`
- `PLATFORM-COMPLETION-STATUS.md`
- `tests/Feature/PlatformCompletionCriticalTest.php`

## 2026-06-03 — Completion cycle 2 (final)

**Backup:** `/var/backups/medca-platform-completion-20260603-170127/`

| ID | Change |
|----|--------|
| GAP-H07 | `LeadIngestionService`, `POST /leads`, `x-public.lead-capture-form`, contact seeder `form-callback` |
| GAP-H06 | All public marketing page blocks: overview, body, coverage, careers hero, services grid |
| GAP-M01 | `hero-careers` inlined |
| GAP-M02 | Section Library create card hidden when deprecated |

**New:** `app/Services/Leads/LeadIngestionService.php`, `LeadCaptureController`, `StorePublicLeadRequest`, `lead-capture-form` component, `PublicLeadCaptureTest.php`, `PLATFORM-COMPLETION-FINAL-REPORT.md`
