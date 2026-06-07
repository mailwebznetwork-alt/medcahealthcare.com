# Page Ownership Governance

Phase 1C — canonical owners per page type.

## Ownership Matrix

| Page Type | Page Owner | SEO Owner | Schema Owner | Visibility Owner | Site Architect Editable |
|-----------|------------|-----------|--------------|------------------|-------------------------|
| Category | `operations_category` | `service_category_seo` | `service_category_schema` + `CategoryJsonLdBuilder` | `service_categories` + `VisibilityGovernanceService` | Yes |
| Service | `operations_service` | `service_seo` | `UnifiedJsonLdGraphBuilder` | `services` + `VisibilityGovernanceService` | Yes |
| Sub Service | `operations_sub_service` | `sub_service_seo` | `sub_service_schema` + parent graph | `sub_services` + `VisibilityGovernanceService` | Yes (when page exists) |
| Location | `operations_location_matrix` | `pages` + `pin_codes` | `UnifiedJsonLdGraphBuilder` | `service_pincodes` + `service_location_pages` | Yes |
| Web / Landing / Blog | `site_architect` | `pages` | `pages.schema_json` | `pages.visibility_flags` + `is_active` | Yes |

## Override Rules

1. **Generated pages are never locked** — no `locked` or `read_only` in `deployment_meta_json`.
2. **Service page SEO override** — when Site Architect fills meta on a service-owned page, `ServicePageOverrides` takes precedence for that field group.
3. **Operations canonical** — `service_seo` remains master unless explicit page override (see `docs/SEO-OWNERSHIP.md`).

## Registry

All logical pages are indexed in `page_registry` via `medca:sync-page-registry`.

Code: `App\Services\Governance\PageOwnershipResolver`
