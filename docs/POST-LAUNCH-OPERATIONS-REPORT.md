# MEDCA HEALTH CARE — Post-Launch Operations & Growth Report

**Generated:** 2026-06-06T09:30:27+00:00
**Lookback:** 28 days
**Tracking activation run:** YES

> Uses existing systems only. No new engines. GSC index status requires manual Search Console review.

---

## 1. Tracking Activation

| Signal | Status |
|--------|--------|
| GTM configured | NO |
| GA4 configured | NO |
| Search Console verification | NO |
| WhatsApp tracking | YES |
| Conversion events (config) | phone_click, whatsapp_click, form_submit |

**Client events:** `phone_click`, `whatsapp_click`, `form_start`, `form_submit`, `cta_click` via `tracking-events.blade.php` + `POST /marketing/track`.

**Activate from env:** set `MEDCA_GTM_CONTAINER_ID`, `MEDCA_GA4_MEASUREMENT_ID`, `MEDCA_GSC_VERIFICATION`, `MEDCA_WHATSAPP_NUMBER` then:

```bash
php artisan medca:post-launch-ops --activate-tracking
```

---

## 2. Indexing Monitoring (inventory)

| Page type | Active count |
|-----------|-------------:|
| Total active pages | 130 |
| Category pages | 7 |
| Service pages | 116 |
| Sub-service pages | 0 |
| Indexable location pages | 105 |
| Registry entries | 246 |

- Sitemap public: YES — [https://medcahealthcare.in/sitemap.xml](https://medcahealthcare.in/sitemap.xml)
- CMS roots live: about-us, careers, contact, locations, services

Index coverage (Indexed / Excluded / Crawled-not-indexed) is monitored manually in Google Search Console — no API integration in codebase.

---

## 3. SEO Monitoring

Growth readiness score components are in-app (Growth Center → Readiness).

**GA4 Data API (28d):**
- API: **not connected** — Set GA4 Property ID in Settings → Integrations (Google Services) for API reports. View reports under Growth Center → GA4.

**Leads (period):** 0 total · top sources: —

---

## 4. GEO Monitoring

| Metric | Value |
|--------|------:|
| Pin codes | 15 |
| With landmarks | 15 |
| With hospitals | 15 |
| With coverage text | 15 |
| Location pages | 105 |
| Indexable location pages | 105 |
| GEO readiness score | 100% |

CLI: `php artisan medca:geo-entity-report`

---

## 5. AEO Monitoring

| FAQ store | Count |
|-----------|------:|
| Service FAQs | 14 |
| Category FAQs | 14 |
| Sub-service FAQs | 8 |
| Pincode FAQs | 30 |

Services with AI summary: 0 · Avg AEO score: 58.6 · Avg AI discovery score: 59.9

CLI: `php artisan medca:seo-hardening-report`

---

## 6. AI Discoverability

| Signal | Status |
|--------|--------|
| `/llm.txt` | active |
| `/ai-discovery` enabled | YES |
| Custom llm.txt | NO |

Validate entity graphs via go-live cert `schema` + `ai_discoverability` sections.

---

## 7. Lead Monitoring (28d)

| Metric | Value |
|--------|------:|
| Total leads | 0 |
| Qualified | 0 |
| Converted | 0 |
| WhatsApp source leads | 0 |
| Call source leads | 0 |

Dashboard: `/marketing/intelligence` → Lead Intent + Attribution tabs.

---

## 8. Conversion Monitoring (28d)

| Channel | Month clicks |
|---------|-------------:|
| WhatsApp | 0 |
| Phone | 0 |
| Forms (intent) | 0 |

Conversion rate (leads): 0%

---

## 9. Content Expansion (existing XLS workflow)

```bash
php artisan medca:import categories storage/imports/production/categories.csv
php artisan medca:import services storage/imports/production/services.xlsx
php artisan medca:import pincodes storage/imports/production/pincodes.xlsx
php artisan medca:import geo storage/imports/production/geo.xlsx
php artisan medca:populate-production   # full pipeline
```

**Current counts:** categories 7, services 7, sub-services 4, pincodes 15

---

## 10. Monthly Health Audit

| Dimension | Score |
|-----------|------:|
| architecture | 100 |
| data | 100 |
| seo | 100 |
| geo | 100 |
| aeo | 100 |
| performance | 100 |
| tracking | 50 |
| discovery | 100 |
| launch | 94 |

**Decision:** GO

### Warnings
- GTM/GA4/Search Console not fully configured — configure before marketing launch

---

## Manual Operations Checklist

- Submit sitemap.xml in Google Search Console (Property → Sitemaps).
- Mark phone_click, whatsapp_click, form_submit as GA4 conversions (Admin → Events).
- Verify GTM container publishes tags for GA4 + conversion events.
- Review GSC Index Coverage weekly: Indexed, Excluded, Crawled-not-indexed, redirects, canonicals.
- Monitor AI visibility manually: ChatGPT/Gemini/Copilot/Perplexity brand queries for "Medca Health Care Bangalore".
- Content expansion: drop updated XLS/CSV in storage/imports/production → medca:import → post-sync runs automatically.
- Monthly: php artisan medca:post-launch-ops --activate-tracking
- Dashboards: /marketing/intelligence, /growth-center/ga4, Growth Center → SEO/GEO/AEO tabs.

---

## Quick Command Reference

| Task | Command |
|------|---------|
| This report | `medca:post-launch-ops` |
| Go-live cert | `medca:go-live-certification` |
| SEO/GEO/AEO hardening | `medca:seo-hardening-report` |
| GEO entities | `medca:geo-entity-report` |
| Bulk import | `medca:import {entity} {file}` |
| Full populate | `medca:populate-production` |
