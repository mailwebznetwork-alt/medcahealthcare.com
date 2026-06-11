# SEO Ownership Hierarchy

Phase 1B consolidation — single canonical source per URL type.

## Service URLs (`/services/{code}`)

| Priority | Source | Write path | Read path (public) |
|----------|--------|------------|-------------------|
| 1 | Linked `pages` with explicit meta override | Site Architect (manual) | `ServicePageOverrides` |
| 2 | **`service_seo`** | Operations save + `ServiceMasterOrchestrator` | Meta tags, head |
| 3 | **`pages`** (synced) | `ServiceMasterPageSync` | Detail page render |
| 4 | Generated JSON-LD | `UnifiedJsonLdGraphBuilder` | `page-json-ld` (read-only) |
| 5 | `page_seo` / `page_elements` | **Disabled by default** | Growth legacy |

### Drift prevention

- `SeoOwnershipGuard::shouldMirrorServiceToGrowthLayer()` defaults to **false**.
- `ContentSeoAutoFillService::applyAndSyncService()` skips Growth layer writes unless `SERVICES_SEO_MIRROR_GROWTH=true`.
- Canonical URL written by orchestrator to `service_seo.canonical_url`.

## Location URLs (`/services/{code}/{location}`)

| Priority | Source |
|----------|--------|
| 1 | `pages` (provisioned) with pincode enrichment |
| 2 | `pin_codes` coverage/meta/FAQs |
| 3 | `UnifiedJsonLdGraphBuilder::buildLocationGraph()` |

## Sub Service schema

- Embedded in parent service graph via `hasPart` nodes.
- Dedicated rows in `sub_service_seo`, `sub_service_schema` for future standalone URLs.

## Configuration

```env
SERVICES_SEO_OPERATIONS_CANONICAL=true
SERVICES_SEO_MIRROR_GROWTH=false
```

Implemented in `config/services_master.php` → `seo_ownership`.

## Code references

- `App\Services\Seo\SeoOwnershipGuard`
- `App\Services\Operations\ServiceSeoOwnership` (page override detection)
- `App\Services\Growth\ContentSeoAutoFillService` (Growth mirror gate)

## Phase 8–9 flags (`config/seo_ownership.php`, `config/seo_rules.php`)

| Flag | Env | Effect |
|------|-----|--------|
| Skip autofill on generated pages | `SEO_SKIP_AUTOFILL_GENERATED` | Observers skip CMS autofill for `page_source=generated` |
| Skip location meta duplicates | `SEO_SKIP_LOCATION_META_DUPLICATES` | `ServiceMasterPageSync` no longer writes location `meta_*` to `pages` |
| Skip `page_seo` mirror | `SEO_SKIP_PAGE_SEO_GENERATED` | No Growth `page_seo` rows for generated pages |
| Hide SEO editor | `SEO_HIDE_EDITING_GENERATED` | Site Architect read-only notice |
| Data-driven SEO | `SEO_DATA_DRIVEN_ENABLED` | `DataDrivenSeoResolver` at public read time |

Static CMS pages continue to use `pages.meta_*`. Service pages use `service_seo`. Location pages use runtime rules + provisioner (no per-page SEO storage).
