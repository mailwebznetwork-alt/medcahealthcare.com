# MEDCA HEALTH CARE — Post-Launch Operations & Growth

Operate, monitor, optimize, and scale **using existing systems only**. No new tables, engines, or architecture changes.

**Primary command:**

```bash
php artisan medca:post-launch-ops --activate-tracking
```

Report output: `docs/POST-LAUNCH-OPERATIONS-REPORT.md` (also scheduled **1st of each month at 06:00 IST**).

---

## 1. Tracking Activation

| System | Where it lives | Activation |
|--------|----------------|------------|
| GTM | `tracking-head.blade.php` + `Integration:google_tag_manager` | `MEDCA_GTM_CONTAINER_ID` in `.env` → `--activate-tracking` |
| GA4 | gtag + `MarketingSetting.ga4_measurement_id` | `MEDCA_GA4_MEASUREMENT_ID` |
| Search Console | `seo_technical.google_site_verification` meta | `MEDCA_GSC_VERIFICATION` |
| WhatsApp | `tracking-events.blade.php` + `Integration:whatsapp` | `MEDCA_WHATSAPP_NUMBER` or public `MEDCA_WHATSAPP_URL` |
| Calls | `phone_click` events | Automatic on `tel:` links |
| Conversions | `marketing_automation.ga4_conversion_events` | Mark events in GA4 Admin |
| Lead attribution | `CaptureMarketingAttributionMiddleware` + `LeadAttributionService` | UTM/gclid on forms + `/marketing/track` |

**Verify events:** Browser DevTools → Network → `marketing/track` + GA4 DebugView. Dashboard: `/marketing/intelligence`.

---

## 2. Indexing Monitoring

**In-app inventory:** `medca:post-launch-ops` section 2 (page counts, sitemap, registry).

**Manual (Google Search Console):** Indexed, Not indexed, Excluded, Crawled-not-indexed, redirects, canonicals.

| Page type | Slug pattern / source |
|-----------|------------------------|
| Category | `category-{code}` |
| Service | `service-{code}` |
| Sub-service | `sub-service-{code}` |
| Location | `ServiceLocationPage` + `/services/{code}/{location}` |
| CMS roots | `about-us`, `services`, `locations`, `careers`, `contact` |
| Registry | `PageRegistry` via `medca:sync-page-registry` |

**Sitemap:** `https://medcahealthcare.in/sitemap.xml` — submit once in GSC.

---

## 3. SEO Monitoring

| Signal | Tool |
|--------|------|
| Impressions, clicks, CTR, position | GSC Performance (manual) or GA4 Data API (`/growth-center/ga4`) |
| Top landing pages | GA4 dashboard + `MarketingAnalyticsAggregator` |
| Technical SEO | `medca:seo-hardening-report` |
| Readiness score | Growth Center → Readiness tab |

---

## 4. GEO Monitoring

| Signal | Tool |
|--------|------|
| Location page visibility | `ServiceLocationPage` indexable count |
| Pincode enrichment | `medca:geo-entity-report` |
| Hospital/landmark relevance | `GeoEnrichmentReadinessService` |
| Local SEO scores | Operations → Service edit → GEO tab |

---

## 5. AEO Monitoring

| Signal | Tool |
|--------|------|
| FAQ coverage | Entity FAQ tables + import `faq_pairs` column |
| Answer readiness | `aeo_score` on `service_seo` |
| AI discovery score | `ai_discovery_score` on `service_seo` |
| Hardening audit | `medca:seo-hardening-report` → `aeo_readiness` |

---

## 6. AI Discoverability

| Endpoint | Purpose |
|----------|---------|
| `/llm.txt` | Bot-oriented site summary |
| `/ai-discovery` | JSON discovery payload (requires `ai_discovery_enabled`) |

**Toggle:** Growth Center → SEO → AI discovery enabled (or `--activate-tracking` sets flags).

**Manual checks:** Brand queries in ChatGPT, Gemini, Copilot, Perplexity; validate JSON-LD via go-live cert.

---

## 7. Lead Monitoring

**Dashboard:** `/marketing/intelligence` → Lead Intent, Attribution, Executive.

**Dimensions:** service, pincode, category (via lead relations), source, landing page, UTM campaign.

**CLI snapshot:** `medca:post-launch-ops` section 7.

---

## 8. Conversion Monitoring

| Event | Capture |
|-------|---------|
| WhatsApp clicks | `MarketingClickEvent` + GA4 `whatsapp_click` |
| Calls | `phone_click` |
| Forms | `form_submit` + `Lead` records |
| Pipeline stages | `MarketingConversionEvent` |

**Daily aggregation:** `AggregateMarketingAnalyticsJob` (01:15).

---

## 9. Content Expansion

Use the **two-workbook operations model** (same Phase 1–4 importers, no new engines):

| Workbook | Sheets |
|----------|--------|
| `services.xlsx` | Categories, Services, SubServices, ServiceDefaults (optional) |
| `pincodes.xlsx` | Pincodes, GeoEnrichment, Mappings (optional) |

```bash
# Generate blank templates
php artisan medca:export-import-templates

# Preview master workbook
php artisan medca:import services storage/imports/templates/services.xlsx --preview

# Commit workbook (all sheets, post-sync per touched entity)
php artisan medca:import services path/to/services.xlsx
php artisan medca:import pincodes path/to/pincodes.xlsx

# Legacy single-entity file still supported
php artisan medca:import categories storage/imports/production/categories.csv

# Full production pipeline (CSV paths in config/medca_launch.php)
php artisan medca:populate-production
```

**UI:** `/operations/bulk-import` — choose **Master workbook** or single entity.

**Docs:** `docs/MASTER-XLS-GUIDE.md`, `docs/IMPORT-ARCHITECTURE.md`

---

## 10. Monthly Health Audit

```bash
php artisan medca:post-launch-ops --activate-tracking
php artisan medca:go-live-certification
php artisan medca:seo-hardening-report
php artisan medca:geo-entity-report
```

| Dimension | Source |
|-----------|--------|
| Architecture / data | `GoLiveCertificationService` |
| SEO / GEO / AEO | Hardening + go-live sections |
| Tracking | `TrackingValidationService` |
| Leads / conversions | Marketing intelligence dashboards |
| Performance | AI Pulse + optional PageSpeed API key |

---

## Success Criteria (unchanged)

- Database first
- Category → Service → Sub Service → Location → Pincode
- SEO / GEO / AEO / AI discoverability / lead generation ready
- Scalable via import + orchestrators only
- **No further architectural development** unless a real business requirement emerges
