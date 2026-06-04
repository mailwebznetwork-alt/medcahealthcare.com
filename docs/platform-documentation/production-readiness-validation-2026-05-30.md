# Live Workflow Validation & Production Readiness

**Date:** 2026-05-30  
**Type:** Verification only (no code changes in this sprint)  
**Test suite:** 371 tests passed

---

## Executive answer

**Can Medca go live today with confidence?**

**Partially — with conditions.**

| Area | Verdict |
|------|---------|
| Website lead capture → Operations Bookings | **READY** |
| WhatsApp click tracking (website) | **READY** (enable WhatsApp integration in admin) |
| Lead Intent dashboard | **READY** (run backfill after deploy) |
| Phone click tracking (`tel:` links) | **CRITICAL** — see Phase 3 |
| Outbound webhooks | **NEEDS ATTENTION** — configure & test in production |
| GBP automated call/direction metrics | **NEEDS ATTENTION** — manual snapshots only |
| GA4/Meta live tag firing | **NEEDS ATTENTION** — browser QA + measurement IDs |

---

## Phase 1 — Lead Flow Validation Report

**Flow tested:** `POST /api/leads` → `leads` row → `LeadObserver` → `lead_intent_events` → `LeadIntentDashboardService`

| Check | Result |
|-------|--------|
| Lead created | ✅ HTTP 201 (automated + `LeadCaptureApiTest`) |
| “Booking” in Operations | ✅ Same `leads` table (`Bookings` Livewire) — no separate booking entity |
| UTM / attribution on lead | ✅ `utm_source`, `utm_medium`, `utm_campaign`, `gclid` persisted (`MarketingAttributionTest`) |
| Pipeline stage | ✅ `new_lead` initialized |
| Lead intent event | ✅ `google_ads_form` / `form_submit` on create |
| Marketing dashboard | ✅ Lead Intent tab loads aggregates (`LeadIntentTrackingTest`) |
| Webhook `lead.created` | ✅ When outbound webhook configured (`OutboundWebhookManagerDispatchTest`) |

**Note:** Website form must call `/api/leads` with valid `X-API-KEY` (`security.lead_api_key`).

---

## Phase 2 — WhatsApp Validation Report

**Components verified in codebase:** `x-whatsapp.link`, `x-whatsapp.floating-button`, hero/contact blocks, `tracking-events.blade.php`

| Check | Result |
|-------|--------|
| `marketing_click_events` | ✅ `whatsapp_click` recorded |
| `lead_intent_events` | ✅ Created via `LeadIntentRecorder` |
| UTM on click | ✅ source/medium/campaign stored |
| GA4 `whatsapp_click` | ✅ Emitted in JS when `gtag` loaded |
| Meta `Contact` | ✅ Emitted when `fbq` loaded |
| Browser proof | ⚠️ **Manual** — DevTools → Network/GA4 DebugView |

**Environment note:** Local DB had `whatsapp` integration **disabled**; enable before go-live.

---

## Phase 3 — Call Tracking Validation Report

| Check | Result |
|-------|--------|
| `phone_click` without `destination_url` | ✅ HTTP 200, recorded |
| `phone_click` with `tel:+91…` (as sent by live JS) | ❌ **HTTP 422** — `Invalid destination URL` |
| Lead intent from phone | ✅ Works when marketing event is stored |
| Lead Intent dashboard | ✅ Counts `phone_click` channel |

**CRITICAL:** Public script sends `destination_url: "tel:…"`. Validator uses `FILTER_VALIDATE_URL`, which rejects `tel:` schemes. **Live phone buttons likely do not record clicks today.**

**Recommended action (post-validation fix):** Allow `tel:` / `mailto:` in `MarketingTrackingValidator` (one-line validation change).

---

## Phase 4 — Operations Validation Report

| Check | Result |
|-------|--------|
| API lead → visible in Bookings | ✅ Same model |
| Manual lead create | ✅ `Bookings::saveLead` unchanged |
| Lead intent on manual create | ✅ `LeadObserver` records form intent |
| Job portal / careers | ✅ Separate `applications` — not Bookings |
| Regression tests | ✅ `LeadPipelineTest`, `JobPortalTest` pass |
| Operations routes/workflows | ✅ Not modified in validation |

---

## Phase 5 — Webhook Validation Report

**Code paths audited:** `OutboundWebhookDispatcher`, `OutboundWebhookSender` (retries, delivery logs), legacy `integrations.name=webhook`

| Webhook | Automated test | Production config (this env) |
|---------|----------------|------------------------------|
| Managed `outbound_webhooks` | ✅ `OutboundWebhookManagerDispatchTest` | **None configured** |
| Legacy integration webhook | Code present | Not enabled |
| `lead.created` | ✅ Test with Http::fake | Untested against real URL |
| `contact.form.submitted` | Dispatched from API | Untested |
| `service.booked` | On lead converted | Untested |
| Async queue | `SendOutboundWebhookJob` | Requires **queue worker** |

**Status list**

| Status | Items |
|--------|--------|
| **Working** | Dispatcher, signing, delivery logs, retry loop (in code + tests) |
| **Untested** | All production endpoint URLs (none configured in validation DB) |
| **Failed** | N/A in test environment |

---

## Phase 6 — Attribution Validation Report

**Classifier tested:** Organic, Google Ads, Meta, GBP, Direct, Referral — all match expected buckets.

| Field | Lead API | Click events | Lead intent |
|-------|----------|--------------|-------------|
| source | ✅ | ✅ | ✅ |
| medium | ✅ | ✅ | ✅ |
| campaign | ✅ | ✅ | ✅ |
| landing_page | ✅ (when sent) | ✅ `page_path` | ✅ |

**Paid vs organic:** Derived from UTM + `Lead.source` on form leads; from UTM on clicks.

---

## Phase 7 — Dashboard Validation Report

| Surface | Load / auth | Data |
|---------|-------------|------|
| Marketing Dashboard | ✅ `module:marketing` | GA4/Ads/Meta depend on API credentials |
| Lead Intent tab | ✅ Implemented | ✅ DB-driven |
| Growth Center | ✅ 15+ feature tests | Tab redirects standardized |
| Operations Bookings | ✅ Module tests | Lead list |
| Security | ✅ Hardening tests | Integrations require `module:settings` |
| System / Settings | ✅ `SettingsPageTest` | Integrations, webhooks UI |
| Lead export CSV | ✅ Route exists | Manager+ role (`MarketingReportController`) |

**Filters / exports:** Lead CSV export by date; Lead Intent tab has no CSV export yet (view-only).

---

## Phase 8 — Production Readiness Score

| Component | Score | Notes |
|-----------|-------|-------|
| Routes & permissions | **READY** | 371 tests; security hardening tests pass |
| Lead capture API | **READY** | Set `LEAD_API_KEY` in production |
| Marketing click tracking | **NEEDS ATTENTION** | Phone `tel:` blocker |
| Lead intent layer | **READY** | Migrate + `lead-intent:backfill` |
| Bookings (Operations) | **READY** | Unchanged |
| Webhooks | **NEEDS ATTENTION** | Configure + queue worker |
| Integrations | **NEEDS ATTENTION** | Enable WhatsApp click-to-chat; GA4/Meta IDs |
| Documentation | **READY** | Platform docs present |

**Overall:** **NEEDS ATTENTION** (not CRITICAL platform-wide; one CRITICAL tracking defect for phone)

---

## Critical Issues List

1. **Phone clicks with `tel:` URLs return 422** — marketing + lead intent not recorded for typical call buttons.
2. **No outbound webhooks configured** in validation environment — production must add and test endpoints.
3. **WhatsApp integration disabled** in validation DB — enable + configure numbers before launch.
4. **Queue worker** required if `settings.webhooks.async_dispatch` is true (default).
5. **GA4/Meta** — confirm measurement IDs in Marketing settings / integrations (browser QA).

---

## Recommended Final Actions (pre-go-live)

1. Fix `tel:` / `mailto:` validation for `phone_click` (small patch).
2. Production: `php artisan migrate --force` && `php artisan lead-intent:backfill --days=90`.
3. Enable **WhatsApp** integration + numbers; test floating button on staging.
4. Configure outbound webhooks; run test delivery from Settings → Webhooks.
5. Start **queue worker** (`php artisan queue:work`).
6. Set `APP_KEY`, `LEAD_API_KEY`, `APP_URL`, Redis, DB backups.
7. Browser QA: one form submit, one WhatsApp click, one phone click — verify GA4 DebugView + Lead Intent tab.
8. Document SOP: WhatsApp/phone intents ≠ Bookings until staff converts.

---

## Test evidence

```
php artisan test  → 371 passed
Filtered: LeadIntent, LeadCapture, MarketingAttribution, OutboundWebhook,
          SecurityHardening, SettingsPage, ModuleAccess, GrowthCenter → pass
Live HTTP: whatsapp_click 200 | phone_click+tel 422 | phone_click no URL 200
```
