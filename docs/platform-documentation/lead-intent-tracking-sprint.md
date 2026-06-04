# Lead Intent Tracking & Attribution Sprint

**Date:** 2026-05-30  
**Scope:** Enhance-only layer on existing marketing tracking (no GA4/Meta/UTM script changes)

---

## Executive summary

| Question | Answer |
|----------|--------|
| What is already available? | `marketing_click_events`, GA4/Meta pixels, UTM capture, Lead API, Marketing dashboard tabs, GBP review sync |
| What tracks immediately? | Website WhatsApp/phone/form clicks → marketing DB → **new** `lead_intent_events`; API form leads → lead intent on `Lead` create |
| What needs implementation? | GBP Insights API ingest (calls/directions), Google/Meta Ads call extensions auto-ingest, inbound WhatsApp message confirmation |
| What needs GBP API? | Automated daily GBP call / website click / direction metrics (currently manual GMB snapshots + click proxies) |

**Management view:** Marketing → Dashboard → **Lead Intent** tab (28-day window).

**Backfill:** `php artisan lead-intent:backfill --days=90`

---

## 1. Lead Intent Tracking Report

### Layer architecture

```
Public site (unchanged)
  → gtag / fbq / medcaTrack()
  → POST /marketing/track
  → marketing_click_events (unchanged)
  → LeadIntentRecorder (NEW, additive)
  → lead_intent_events (NEW)

API POST /api/leads (unchanged)
  → leads table
  → LeadObserver → LeadIntentRecorder (NEW)
  → lead_intent_events (form channel)
```

### Stored fields (`lead_intent_events`)

| Field | Description |
|-------|-------------|
| `intent_type` | e.g. `whatsapp_click`, `phone_click`, `form_submit`, `google_ads_form`, `gbp_call` |
| `channel` | `calls`, `whatsapp`, `forms` |
| `attribution_bucket` | `organic`, `google_ads`, `meta_ads`, `gbp`, `direct`, `referral`, `unknown` |
| `source`, `medium`, `campaign` | UTM / last-touch |
| `landing_page` | Page path |
| `service_page` | Detected service URLs |
| `lead_id` | When tied to a Lead |
| `marketing_click_event_id` | 1:1 link to click row |
| `occurred_at` | Event time |

### Mapped marketing events (immediate)

| Marketing `event_type` | Lead intent |
|------------------------|-------------|
| `form_submit` | `form_submit` |
| `whatsapp_click` | `whatsapp_click` |
| `phone_click` | `phone_click` |
| `gbp_call_click` | `gbp_call` |
| `gbp_website_visit` | `gbp_website_click` |
| `gbp_whatsapp_click` | `gbp_whatsapp_click` |

---

## 2. Attribution Mapping Report

| Bucket | Detection rules |
|--------|-----------------|
| **Google Ads** | `utm_source` google* + medium cpc/ppc/paid, or Lead `source=google_ads` |
| **Meta Ads** | facebook/meta/instagram + paid medium, or Lead `source=meta_ads` |
| **GBP** | Intent type `gbp_*`, or Lead `source=gmb` |
| **Organic** | medium/source organic |
| **Direct** | empty UTM / direct |
| **Referral** | referral medium/source |
| **Unknown** | Fallback |

Paid vs organic for **website** intents uses UTM on the click. **API leads** use resolved `Lead.source` first.

---

## 3. Booking Creation Matrix

> Operations **Bookings** = `leads` table (not renamed). No workflow changes in this sprint.

| Action | Creates Booking (Lead)? | Marketing click event? | Lead intent event? |
|--------|-------------------------|------------------------|-------------------|
| Website form → API `/api/leads` | **Yes** | Optional `form_submit` click | **Yes** (`google_ads_form` / `form_submit`) |
| Website WhatsApp click | **No** | **Yes** | **Yes** |
| Website phone (`tel:`) click | **No** | **Yes** | **Yes** |
| Manual Booking create (Ops) | **Yes** | No | **Yes** (form channel) |
| Careers job application | Application row | No | No |
| Competitor growth form | CompetitorLead | No | No |
| GBP review sync | No | No | No |
| Converted lead → webhook `service.booked` | Status change only | No | No |

**Gap:** WhatsApp/phone clicks are **intent signals**, not automatic Booking records until staff converts them manually or via a future automation rule.

---

## 4. WhatsApp Tracking Report

| Item | Status |
|------|--------|
| Up to 5 numbers (click-to-chat) | **Preserved** — unchanged |
| `whatsapp_click` marketing events | **Preserved** |
| GA4 `whatsapp_click` params | **Preserved** |
| Lead intent | `whatsapp_click` channel `whatsapp`; meta includes `phone_number`, `button_name` |
| Actual message received | **Not tracked** without Business API inbound webhook |

---

## 5. Phone Call Tracking Report

| Item | Status |
|------|--------|
| Website `tel:` clicks | `phone_click` → lead intent channel **calls** |
| Stored | `destination_url`, page, UTM, optional phone in meta |
| Actual answered calls | **Not tracked** without call tracking provider or Ads call reporting import |

---

## 6. GBP Tracking Report

### Current implementation

- **Integration:** `google_business_profile` — OAuth, reviews sync (`GoogleBusinessProfileService`)
- **Click proxies:** `gbp_call_click`, `gbp_website_visit`, `gbp_whatsapp_click` in marketing validator (if fired from site)
- **Manual metrics:** Marketing → Communication snapshots (`gmb_calls`, `gmb_directions`, `gmb_views`)
- **Lead attribution:** Leads with `source=gmb` → bucket **GBP**

### GBP Insights API gap

| Metric | Ingest today? | API path (typical) |
|--------|---------------|-------------------|
| Calls | Manual snapshot only | Business Profile Performance API / legacy insights |
| Website clicks | Manual + `gbp_website_visit` clicks | Performance metrics |
| Direction requests | Manual snapshot only | `BUSINESS_DIRECTION_REQUESTS` metric |
| Reviews | **Yes** (sync) | Reviews API |

**Recommendation:** Scheduled job pulling Performance API daily → `lead_intent_events` with types `gbp_call`, `gbp_website_click`, `gbp_direction_request` (future phase; requires GBP API enablement in Google Cloud).

---

## 7. Dashboard structure (implemented)

**Path:** Marketing → Dashboard → **Lead Intent**

1. **Totals** — Calls, WhatsApp, Forms, Total intents (+ leads captured count)
2. **Source breakdown** — matrix by attribution bucket × channel
3. **Channel breakdown** — Calls / WhatsApp / Forms
4. **Campaign breakdown** — top UTM campaigns × channel counts

---

## 8. Source & campaign breakdown

Implemented in `LeadIntentDashboardService` (28-day default, aligned with GA4 window on dashboard).

---

## 9. Risk assessment

| Risk | Level | Mitigation |
|------|-------|------------|
| Double-count form (click + lead) | Medium | Expected: click = intent, lead = conversion; filter by channel in reports |
| Intent ≠ completed contact | Medium | Label dashboard “Lead Intent” not “Leads received” |
| Backfill duplicates on clicks | Low | Unique `marketing_click_event_id` |
| Lead backfill duplicates | Low | Skip if `lead_id` already has intent |

---

## 10. Recommended architecture (next phases)

1. **GBP Performance API** → nightly metrics → `lead_intent_events`
2. **Google Ads offline conversions** / call details import
3. **Meta lead ads** webhook → `meta_form` intents
4. **WhatsApp Business API** inbound → optional “message received” intent (separate from click)
5. **Single executive export** CSV from `LeadIntentDashboardService`

---

## Validation checklist

| Item | Status |
|------|--------|
| No features removed | ✅ |
| No permissions / nav / URLs changed | ✅ |
| Marketing tracking scripts unchanged | ✅ |
| WhatsApp integration unchanged | ✅ |
| GBP integration unchanged | ✅ |
| Operations / Bookings unchanged | ✅ |
