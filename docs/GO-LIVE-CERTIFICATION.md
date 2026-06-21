# MEDCA HEALTH CARE — Go-Live Certification (Phase 4)

**Generated:** 2026-06-13T22:51:57+05:30
**Decision:** **NO-GO**
**Certified:** NO

## Scores

| Dimension | Score |
|-----------|------:|
| Architecture | 46 |
| Data | 12 |
| SEO | 40 |
| GEO | 10 |
| AEO | 17 |
| Performance | 100 |
| Tracking | 50 |
| Discovery | 40 |
| **Launch** | **39** |

## Go-Live Checklist

| Area | Status |
|------|--------|
| Content | FAIL |
| SEO | FAIL |
| GEO | FAIL |
| AEO | FAIL |
| Schema | FAIL |
| Discovery | FAIL |
| Tracking | FAIL |
| Performance | PASS |
| Lead Flow | FAIL |

## Critical Issues

- Section [page_generation] failed certification
- Section [discovery] failed certification
- Section [change_pincode] failed certification
- Section [matrix] failed certification
- Security: import_audit_trail — 0 committed batches
- Section [services] — insufficient published services
- Database-first compliance violation in app/

## Warnings

- GTM/GA4/Search Console not fully configured — configure before marketing launch
- Enable ai_discovery_enabled in SeoTechnical for full AI endpoint readiness

## Recommendations

- Set MEDCA_GTM_CONTAINER_ID, MEDCA_GA4_MEASUREMENT_ID, MEDCA_GSC_VERIFICATION in .env
- Submit sitemap.xml in Google Search Console after DNS cutover
- Mark phone_click, whatsapp_click, form_submit as GA4 conversions

## Section Details

### Import system — FAIL (50%)
- FAIL: production_csv_files — /var/www/medca_healthcare/storage/imports/production
- FAIL: preview_pipeline — categories.csv preview
- FAIL: audit_trail — 0 import batches

### Categories — FAIL (0%)
- FAIL: active_count — 0 active categories

### Services — FAIL (0%)
- FAIL: published_count — 0 published services

### Sub services — FAIL (0%)
- FAIL: sub_service_count — 0 sub-services

### Locations — FAIL (0%)
- FAIL: location_pages — 0 indexable location pages
- FAIL: geo_readiness — geo enrichment score

### Matrix — FAIL (60%)
- FAIL: matrix_count — 0 mappings
- FAIL: visible_mappings — visible pivots exist

### Page generation — FAIL (29%)
- FAIL: category_pages — 0 category pages
- FAIL: service_pages — 0 service pages
- FAIL: sub_service_pages — 0 sub-service pages
- FAIL: location_pages — 0 location pages
- FAIL: generated_pages — 0 generated

### Discovery — FAIL (0%)
- FAIL: category_discovery — 0 categories
- FAIL: service_discovery — 0 services
- FAIL: sub_service_discovery — 0 sub-services
- FAIL: location_discovery — 0 locations
- FAIL: pincode_discovery — missing

### Change pincode — FAIL (20%)
- FAIL: pincode_switch — We do not service that pincode yet.
- FAIL: discovery_refresh — discovery payload
- FAIL: pincode_search — 0 results
- FAIL: session_persistence — current pincode

### Internal linking — PASS (100%)

### Seo — FAIL (40%)
- FAIL: database_first — 1 violations
- FAIL: services_have_meta — service meta titles
- FAIL: canonical_paths — meta descriptions

### Geo — FAIL (20%)
- FAIL: landmarks_populated — 0 pincodes
- FAIL: hospitals_populated — 0 pincodes
- FAIL: full_geo_coverage — 0/0 enriched
- FAIL: location_pages_indexable — 0 pages

### Aeo — FAIL (17%)
- FAIL: category_faqs — 0 FAQs
- FAIL: service_faqs — 0 FAQs
- FAIL: sub_service_faqs — 0 FAQs
- FAIL: location_faqs — 0 FAQs
- FAIL: faq_total — 0 total FAQs

### Schema — FAIL (50%)
- FAIL: pages_with_schema — 7 pages

### Ai discoverability — FAIL (60%)
- FAIL: entity_relationships — category-service links
- FAIL: faq_readiness — service FAQs for AI overviews

### Performance — PASS (100%)

### Tracking — FAIL (50%)
- FAIL: gtm — GTM container
- FAIL: ga4 — GA4 measurement ID
- FAIL: search_console — GSC verification token

### Security integrity — FAIL (60%)
- FAIL: import_audit_trail — 0 committed batches
- FAIL: db_first_compliant — no hardcoded localities in app/

