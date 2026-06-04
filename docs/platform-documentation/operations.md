# Operations Documentation

**Module key:** `operations`  
**Sidebar entry:** `modules.operations` (redirects to Job Portal — **unchanged by IA restructure**)

## Primary navigation tabs

| Tab | Route | Feature |
|-----|-------|---------|
| Job Portal | `operations.job-portal.overview` | Vacancies & applications summary |
| PIN Codes | `operations.pin-codes.overview` | Coverage overview |
| Services | `operations.services.index` | Service catalog admin |
| Bookings | `operations.bookings.index` | Lead pipeline |

## Job Portal

| Route pattern | Purpose |
|---------------|---------|
| `operations.job-portal.overview` | Dashboard |
| `operations.job-portal.vacancies.*` | CRUD vacancies |
| `operations.job-portal.applications.*` | Application review, resume |
| `operations.job-portal.index` | Redirect → overview |

## Services

- List, create, edit, preview, duplicate
- Service detail page builder (`detail-page.create/edit`)
- Bound to public `/services/{slug}`

## Bookings / Leads

- Livewire index with filters
- Show view per lead
- Integrates with marketing attribution data

## PIN Codes

- Overview matrix
- Directory, create, edit, bulk import
- Serviceability flags for hyper-local coverage

## Access

- **Module:** `operations`
- **Roles:** manager, admin, super_admin
- **Policies:** `ModulePolicy` for dynamic module schema (legacy operations fields)

## Tests

`JobPortalTest`, `PinCodesTest`, `OperationsServicesStoreTest`, `LeadPipelineTest`

## IA note

Operations was explicitly **not** redesigned in the navigation restructure. Only safe URL redirects (job-portal index → overview) exist.
