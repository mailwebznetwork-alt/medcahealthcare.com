# MEDCA HEALTH CARE — Go-Live Certification (Phase 4)

**Generated:** 2026-06-08T16:04:04+00:00
**Decision:** **NO-GO**
**Certified:** NO

## Scores

| Dimension | Score |
|-----------|------:|
| Architecture | 79 |
| Data | 85 |
| SEO | 100 |
| GEO | 67 |
| AEO | 100 |
| Performance | 100 |
| Tracking | 50 |
| Discovery | 92 |
| **Launch** | **84** |

## Go-Live Checklist

| Area | Status |
|------|--------|
| Content | FAIL |
| SEO | PASS |
| GEO | FAIL |
| AEO | PASS |
| Schema | FAIL |
| Discovery | PASS |
| Tracking | FAIL |
| Performance | PASS |
| Lead Flow | PASS |

## Critical Issues

- Section [page_generation] failed certification
- Section [matrix] failed certification

## Warnings

- GTM/GA4/Search Console not fully configured — configure before marketing launch

## Recommendations

- Set MEDCA_GTM_CONTAINER_ID, MEDCA_GA4_MEASUREMENT_ID, MEDCA_GSC_VERIFICATION in .env
- Submit sitemap.xml in Google Search Console after DNS cutover
- Mark phone_click, whatsapp_click, form_submit as GA4 conversions

## Section Details

### Import system — FAIL (67%)
- FAIL: production_csv_files — /var/www/medca_healthcare/storage/imports/production
- FAIL: preview_pipeline — categories.csv preview

### Categories — PASS (97%)
- FAIL: active_count — 6 active categories

### Services — PASS (93%)
- FAIL: detach-pin-svc_faq — 0 FAQs
- FAIL: detach-pin-svc_locations — pincode mappings
- FAIL: dolore-cum-33626_faq — 0 FAQs
- FAIL: dolore-cum-33626_locations — pincode mappings

### Sub services — FAIL (81%)
- FAIL: blood-test_page — CMS page
- FAIL: ecg-at-home_page — CMS page
- FAIL: thyroid-test_page — CMS page
- FAIL: xray-at-home_page — CMS page

### Locations — FAIL (73%)
- FAIL: 560997_coverage — coverage text
- FAIL: 560997_landmarks — 0
- FAIL: 560997_hospitals — 0
- FAIL: 560997_faqs — location FAQs
- FAIL: 752761_coverage — coverage text
- FAIL: 752761_landmarks — 0
- FAIL: 752761_hospitals — 0
- FAIL: 752761_faqs — location FAQs
- FAIL: 560101_coverage — coverage text
- FAIL: 560101_landmarks — 0
- FAIL: 560101_hospitals — 0
- FAIL: 560101_faqs — location FAQs
- FAIL: 061285_coverage — coverage text
- FAIL: 061285_landmarks — 0
- FAIL: 061285_hospitals — 0
- FAIL: 061285_faqs — location FAQs
- FAIL: 290107_coverage — coverage text
- FAIL: 290107_landmarks — 0
- FAIL: 290107_hospitals — 0
- FAIL: 290107_faqs — location FAQs
- FAIL: geo_readiness — geo enrichment score

### Matrix — FAIL (80%)
- FAIL: services_have_pins — 2 services without pins

### Page generation — FAIL (71%)
- FAIL: category_pages — 6 category pages
- FAIL: sub_service_pages — 0 sub-service pages

### Discovery — PASS (100%)

### Change pincode — PASS (100%)

### Internal linking — FAIL (75%)
- FAIL: service_to_location — related locations

### Seo — PASS (100%)

### Geo — FAIL (60%)
- FAIL: coverage_text — 14 with coverage
- FAIL: full_geo_coverage — 14/19 enriched

### Aeo — PASS (100%)

### Schema — FAIL (83%)
- FAIL: service_has_FAQPage — FAQPage

### Ai discoverability — PASS (100%)

### Performance — PASS (100%)

### Tracking — FAIL (50%)
- FAIL: gtm — GTM container
- FAIL: ga4 — GA4 measurement ID
- FAIL: search_console — GSC verification token

### Security integrity — PASS (100%)

