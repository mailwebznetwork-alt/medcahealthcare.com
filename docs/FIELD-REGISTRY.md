# Medca Field Registry

Database-first field ownership for Phase 1B foundation.

## Master workbook import

Operations primary files: **`services.xlsx`** and **`pincodes.xlsx`**. Column → DB mapping is documented in `docs/MASTER-XLS-GUIDE.md`. Importers write to the tables below; overflow fields use `custom_fields` on `services` and `pin_codes`.

## Ownership Legend

| Owner | Meaning |
|-------|---------|
| **Operations** | `services`, `sub_services`, `pin_codes`, `service_pincodes` — canonical catalog |
| **Operations SEO** | `service_seo`, `sub_service_seo` — meta/scores for services |
| **Operations Schema** | `service_schema`, `sub_service_schema` + runtime `UnifiedJsonLdGraphBuilder` |
| **Site Architect** | `pages`, `blocks` — layout override when explicitly filled |
| **Growth (legacy)** | `page_seo`, `page_elements` — mirror only when enabled |

## Categories (`service_categories`)

| Field | Code | Required | Owner |
|-------|------|----------|-------|
| Name | `name` | Yes | Operations |
| Code | `code` | Yes | Operations |
| Slug | `slug` | No (defaults to code) | Operations |
| Description | `description` | No | Operations |
| Parent | `parent_id` | No | Operations |
| Sort order | `sort_order` | No | Operations |
| Active | `is_active` | Yes | Operations |
| Featured | `is_featured` | No | Operations |
| Visibility | `visibility` | Yes | Operations |
| Homepage | `show_on_homepage` | No | VisibilityGovernance |
| About | `show_on_about` | No | VisibilityGovernance |
| Contact | `show_on_contact` | No | VisibilityGovernance |
| SEO meta | `service_category_seo.*` | No | CategoryDiscoveryEngine |
| Schema | `service_category_schema.*` | No | CategoryJsonLdBuilder |
| FAQs | `service_category_faqs` | No | Operations AEO |
| Linked page | `page_id` | No | UniversalPageRegistry |

## Services (`services`)

| Field | Code | Required | Owner |
|-------|------|----------|-------|
| Title | `title` | Yes | Operations |
| Service code | `service_code` | Yes | Operations |
| Description | `description` | No | Operations |
| Benefits | `key_benefits` | No | Operations |
| Eligibility | `eligibility` | No | Operations |
| Process | `process_steps` | No | Operations |
| Featured | `is_featured` | No | Operations |
| Top rated | `is_top_rated` | No | Derived (reviews) — column planned Phase 2 |
| Publish status | `publish_status` | Yes | Operations |
| Visibility | `visibility` | Yes | Operations |
| SEO meta | `service_seo.*` | No | Operations SEO |
| Schema | `service_schema.schema_json` | No | Operations Schema |
| CTA | — | No | Blocks `settings_json` (Phase 2 relational) |

## Sub Services (`sub_services`)

| Field | Code | Required | Owner |
|-------|------|----------|-------|
| Parent service | `service_id` | Yes | Operations |
| Sub service code | `sub_service_code` | Yes | Operations |
| Title | `title` | Yes | Operations |
| Standalone promotion | `standalone_service_id` | No | Operations |
| Sort order | `sort_order` | No | Operations |
| Featured | `is_featured` | No | Operations |
| Top rated cache | `avg_rating_cache` / `is_top_rated` | No | Operations |
| Visibility | `visibility` | Yes | Operations |
| SEO | `sub_service_seo.*` | No | Operations SEO |
| Schema | `sub_service_schema.*` | No | Operations Schema |
| FAQs | `sub_service_faqs` | No | Operations AEO |

## Pincodes / Locations (`pin_codes` + enrichment)

| Field | Code | Required | Owner |
|-------|------|----------|-------|
| Pincode | `pincode` | Yes | Operations |
| Area | `area_name` | Yes | Operations GEO |
| City / State | `city`, `state` | Yes | Operations GEO |
| Coverage | `coverage_text` | No | Operations GEO |
| Emergency coverage | `emergency_coverage_text` | No | Operations GEO |
| Landmarks | `pin_code_landmarks` | No | Operations GEO |
| Hospitals | `pin_code_hospitals` | No | Operations GEO |
| Nearby areas | `pin_code_nearby_areas` | No | Operations GEO |
| Location FAQs | `pin_code_location_faqs` | No | Operations AEO |
| Geo anchor | `geo_location_id` | No | Operations GEO |

## Mappings (`service_pincodes`)

| Field | Code | Required | Owner |
|-------|------|----------|-------|
| Service | `service_id` | Yes | Operations |
| Pincode | `pincode_id` | Yes | Operations |
| Priority | `priority` | No | Operations Matrix |
| Visible | `is_visible` | Yes (default true) | Operations Matrix |
| Featured | `is_featured` | No | Operations Matrix |
| Coverage notes | `coverage_notes` | No | Operations Matrix |
| Category filter | `category_filter_ids` | No | Operations Matrix |
| Effective from/until | `effective_from`, `effective_until` | No | Operations Matrix |

## Pages — Homepage / About / Contact

| Page | Slug | Visibility control |
|------|------|-------------------|
| Homepage | `home` | `pages.is_active` + `site_navigation_items` |
| About | `about-us` | `pages.is_active` + nav |
| Contact | `contact` | `pages.is_active` + nav |

## JSON Extension

| Entity | Field | Governance |
|--------|-------|------------|
| Services | `custom_fields` | Document keys in import registry |
| Pincodes | `custom_fields` | Document keys in import registry |
| Blocks | `settings_json` | `config/block_content_schemas.php` |
