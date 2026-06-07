# Known Test Gaps (Non-Production-Blocking)

Last verified: 2026-06-02 (Sprint A — Growth & Conversion Foundation)

These failures are **documented and accepted** for Sprint A. They relate to platform/CMS hardening, typography admin validation, or stale test assertions — not public conversion, attribution, or revenue paths.

| # | Test | Failure summary | Why non-blocking |
|---|------|-----------------|------------------|
| 1 | `TypographyTypeScaleTest` → `it rejects invalid rem size` | `ValidationException` not thrown for oversized `h1` rem value | Admin typography guardrail only; public pages use theme defaults |
| 2 | `ElementLibraryCompletionTest` → `it syncs and renders a shared element with style pack variant` | FAQ block render missing expected marketing headline copy | Block Studio / element library QA; public FAQ blocks still render |
| 3 | `MedcaPublicPagesSeederTest` → `it seeds page content composed entirely of editable block tokens` | Seeded home/content missing expected block token pattern | Seeder drift vs. block token naming; CMS content editable in admin |
| 4 | `OperationsServicesStoreTest` → `it persists content and media when updating a service` | Featured image path stored under `media/{uuid}/` not legacy `services/{id}/` | Media library migration path; public URLs resolve correctly |
| 5 | `Phase85CompletionPatchTest` → `it renders section library shell without blade parse errors` | Legacy admin section shell HTML assertion mismatch | Admin-only legacy sections UI; public layouts unaffected |
| 6 | `PublicHeaderConsistencyTest` → `it renders the same primary header navigation on home, cms, and catalog routes` | Regex expects `flex min-w-0 flex-1` nav wrapper (header refactored for single-line desktop nav) | Visual/nav CSS change intentional; links and routes unchanged |
| 7 | `PublicMarketingShellTest` → `it shows compact centered footer line` | Footer markup/class assertion drift | Cosmetic footer layout; contact CTAs and legal links present |
| 8 | `ServicesContentBindingTest` → `it pages block modal lists a service created after the modal was opened once refresh runs` | Livewire block modal service list refresh timing | Admin page builder UX; public service pages unaffected |
| 9 | `ServicesContentBindingTest` → `it block modal service select includes newly created service after sync` | Same modal refresh race on service select | Admin-only; service public URLs and SEO unaffected |

## Suite snapshot

- **Total:** 481 tests
- **Passing:** 470
- **Failing:** 10 assertions across 9 test classes (see table)
- **Skipped:** 1 (environment-dependent)

## Sprint A coverage (passing)

- `SprintAGrowthFoundationTest` — GA4/gtag, conversion events, mobile call FAB, growth chrome
- `PhoneClickTrackingTest` — `phone_click` server-side recording
- `MarketingAttributionTest` — UTM capture, lead attribution fields, click dedupe
- `LeadIntentTrackingTest` — intent buckets and classification

## Remediation priority (post–Sprint A)

1. Update `PublicHeaderConsistencyTest` nav selector to match current header markup
2. Align `MedcaPublicPagesSeeder` block tokens with seeder test expectations
3. Refresh `ElementLibraryCompletionTest` FAQ headline assertion for current block partials
4. Decide typography max rem policy and align `TypographyTypeScale::assertValid`
