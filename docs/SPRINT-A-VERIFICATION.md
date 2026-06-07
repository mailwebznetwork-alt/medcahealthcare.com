# Sprint A Verification Checklist

**Sprint:** Growth & Conversion Foundation  
**Date:** 2026-06-02  
**Scope:** Revenue, leads, attribution, SEO visibility, conversion — no platform/CMS architecture changes.

---

## 1. Analytics & Attribution

| Check | Status | Notes |
|-------|--------|-------|
| GA4 `gtag.js` on all public pages via `tracking-head` | ✅ | Loads when `MarketingSetting.ga4_measurement_id` or `google_services` integration is set |
| GTM container in `<head>` + `<noscript>` body | ✅ | When `google_tag_manager` integration has `container_id` / `gtm_id` |
| `phone_click` event (gtag + `medcaTrack`) | ✅ | Fires on `tel:` link clicks |
| `whatsapp_click` event | ✅ | Fires on `wa.me` / `data-whatsapp-track` links |
| `form_start` event | ✅ | First focus in any public form |
| `form_submit` event | ✅ | Form submit + `generate_lead` in tracking-body |
| `cta_click` event | ✅ | `.btn-premium`, `.medca-cta-solid`, `.medca-cta-on-hero`, `[data-medca-cta]` |
| GA4 conversions registered (config) | ✅ | `config/marketing_automation.php` → `phone_click`, `whatsapp_click`, `form_submit` |
| Mark conversions in GA4 Admin | ⚠️ Manual | GA4 → Admin → Events → Mark as conversion (one-time per property) |
| Debug mode in local/staging | ✅ | `debug_mode: true` when `APP_DEBUG=true` |
| UTM persistence (cookie + session + localStorage) | ✅ | Middleware + `tracking-events` client backup |

### GA4 conversion verification steps

1. Set measurement ID in **Settings → Marketing** or `google_services` integration.
2. Open site with `?utm_source=test&utm_medium=sprint_a`.
3. Chrome: install [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger) or use GA4 **Admin → DebugView**.
4. Click phone link → confirm `phone_click` + `generate_lead`.
5. Click WhatsApp CTA → confirm `whatsapp_click` + `generate_lead`.
6. Focus form field → `form_start`; submit → `form_submit`.
7. In GA4 Admin, mark `phone_click`, `whatsapp_click`, `form_submit` as conversions.

---

## 2. Public Service & Location Pages

| Check | Status | Notes |
|-------|--------|-------|
| Breadcrumbs on service detail (CMS + fallback view) | ✅ | `ServicePublicController` + `growth-chrome` / `show.blade.php` |
| Breadcrumbs on location pages | ✅ | `locationBreadcrumbs()` via `PageRenderContextRegistrar` |
| Internal links from `internal_links_snapshot` | ✅ | `service-internal-links` component |
| Related services section | ✅ | `related_services` in snapshot |
| Nearby locations section | ✅ | `related_locations` in snapshot |

### Breadcrumb verification

- Visit `/services/{code}` → Home → Services → {Service title}
- Visit `/services/{code}/{location-slug}` → adds location crumb

### Internal linking verification

- After `php artisan services:sync-master`, pages show **Related → Services** and **Locations** link lists when snapshot populated.

---

## 3. Homepage Conversion Optimization

| Check | Status | Notes |
|-------|--------|-------|
| Hero phone CTA visible | ✅ | `hero-home` block — `medca-cta-on-hero` + `tel:` |
| Hero WhatsApp CTA visible | ✅ | `x-whatsapp.link` in hero |
| Trust / reviews fallback | ✅ | `home-trust-reviews` when no `reviews` block token |
| Top revenue / featured services | ✅ | Featured services grid in trust partial |
| Near You / location coverage | ✅ | `near-you-home` fallback when token missing |
| Mobile call FAB | ✅ | `global/floating.blade.php` — `md:hidden`, left side |
| Mobile WhatsApp FAB | ✅ | Existing floating WhatsApp button |
| Reviews block on homepage | ✅ | `reviews-grid` falls back to site-wide approved reviews |

---

## 4. Lead Attribution

| Check | Status | Notes |
|-------|--------|-------|
| UTM capture middleware on public routes | ✅ | `CaptureMarketingAttributionMiddleware` |
| Hidden UTM fields on lead form | ✅ | `utm-hidden-fields` in `lead-capture-form` |
| Attribution on lead save | ✅ | `LeadAttributionService` + `UtmCaptureService` |
| `gclid` → Google Ads source | ✅ | `LeadSourceResolver` |
| `fbclid` → Meta Ads source | ✅ | `LeadSourceResolver` |
| Organic / Direct / Referral / GMB UTM mapping | ✅ | `LeadSourceResolver` match rules |
| Survives session navigation | ✅ | 90-day first-touch cookie + session last-touch |

### Lead attribution verification

```bash
# 1. Land with UTMs
curl -c /tmp/cookies.txt "https://medcahealthcare.in/?utm_source=google&utm_medium=cpc&gclid=test123"

# 2. Submit lead form (or API)
# 3. Confirm lead row: utm_source, gclid, lead_source, first_touch_*, last_touch_*
```

---

## 5. Reviews & Trust

| Check | Status | Notes |
|-------|--------|-------|
| Approved reviews on homepage | ✅ | `home-trust-reviews` + `reviews-grid` |
| Approved reviews on service pages | ✅ | `show.blade.php` + service-scoped `reviews-grid` |
| Average rating on service header | ✅ | `averageRating` / `reviewsCount` in fallback view |
| Site-wide avg rating on home | ✅ | Trust band in `home-trust-reviews` |

---

## 6. Automated tests

```bash
php artisan test --filter="SprintAGrowthFoundation|PhoneClickTracking|MarketingAttribution|LeadIntentTracking"
```

Expected: all pass (1 skip if no public services seeded).

---

## Files changed (Sprint A)

| Area | Files |
|------|-------|
| Tracking | `resources/views/components/marketing/tracking-head.blade.php`, `tracking-body.blade.php`, `tracking-events.blade.php`, `config/marketing_automation.php` |
| Growth chrome | `resources/views/public/partials/growth-chrome.blade.php`, `layouts/app.blade.php` |
| Homepage trust | `resources/views/public/partials/home-trust-reviews.blade.php`, `blocks/shared/reviews-grid.blade.php` |
| Service fallback | `resources/views/public/services/show.blade.php` |
| Lead attribution | `resources/views/public/partials/utm-hidden-fields.blade.php`, `lead-capture-form.blade.php`, `LeadSourceResolver.php`, `LeadIngestionService.php` |
| Mobile CTA | `resources/views/global/floating.blade.php` |
| Tests | `tests/Feature/SprintAGrowthFoundationTest.php` |
| Docs | `docs/KNOWN-TEST-GAPS.md`, `docs/SPRINT-A-VERIFICATION.md` |
