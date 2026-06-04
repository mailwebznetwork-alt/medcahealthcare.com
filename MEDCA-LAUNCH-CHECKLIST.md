# Medca Health Care — Launch Checklist

**Last updated:** 2026-06-03  
**Command:** `php artisan medca:launch-seed`  
**Verify:** `php artisan test --filter=MedcaLaunch`

---

## Phase 1 — Services (6 lines)

| Service | Code | Published | SEO | Pincodes | Detail page | Related |
|---------|------|-----------|-----|----------|-------------|---------|
| Home Nursing | `homenursing-services` | ☐ → ✅ | ✅ | ✅ | ✅ | ✅ |
| Elder Care | `elder-care` | ✅ | ✅ | ✅ | ✅ | ✅ |
| Caregiver Services | `caregivers` | ✅ | ✅ | ✅ | ✅ | ✅ |
| Doctor Home Visit | `doctor-home-visit` | ✅ | ✅ | ✅ | ✅ | ✅ |
| Physiotherapy | `physiotherapy-at-home` | ✅ | ✅ | ✅ | ✅ | ✅ |
| ICU / Specialized Care | `icu-care-at-home` | ✅ | ✅ | ✅ | ✅ | ✅ |

- [x] Title, code, summary, description, procedures
- [x] Featured image + gallery (launch placeholders — **replace with brand photography**)
- [x] Public URLs: `/services/{code}`
- [x] Automated test: `MedcaLaunchDataTest`

---

## Phase 2 — Marketing pages

| Page | URL | Blocks | Active |
|------|-----|--------|--------|
| Home | `/` | hero-home, services-overview-home, locations-overview-home, cta-home | ✅ |
| About | `/about-us` | hero-about, body-about | ✅ |
| Services | `/services` | hero-services, services-block-carousel, cta-services | ✅ |
| Locations | `/locations` | hero-locations, locations-coverage | ✅ |
| Contact | `/contact` | hero-contact, contact-info, form-callback | ✅ |
| Careers | `/careers` | hero-careers, careers-open-roles | ✅ |

- [x] Header navigation wired (Site Architect)
- [x] Services carousel lists all six `{{service:code}}` tokens
- [x] Block Studio copy seeded for home/services overview cards
- [ ] Replace placeholder hero photography in Block Studio (optional pre-launch)

---

## Phase 3 — Content quality

- [x] Grammar/spelling pass in seeder copy (clinical, Bangalore, Arekere belt)
- [x] CTA labels aligned (`Book a home visit`, `WhatsApp our care team`)
- [x] Phone/WhatsApp via Global Content (`+91 88849 99002` / `wa.me/918884999002`)
- [x] Brand name: Medca Health Care
- [ ] Final marketing review by clinical lead (human sign-off)

---

## Phase 4 — SEO

- [x] Page meta titles/descriptions (launch seeder)
- [x] Service SEO records per line
- [x] `robots.txt` responds 200
- [x] `sitemap.xml` + `sitemap-services.xml` include service codes
- [x] Service SEO ownership: page canonical when linked (existing platform rule)
- [ ] Submit sitemap in Google Search Console (ops)
- [ ] Confirm production `APP_URL` in `.env`

See `MEDCA-SEO-PASS-REPORT.md`.

---

## Phase 5 — Lead flow

| Step | Status |
|------|--------|
| Landing (`/contact`) | ✅ |
| Form (`form-callback` block) | ✅ |
| `POST /leads` (CSRF) | ✅ `PublicLeadCaptureTest` |
| `POST /api/leads` (API key) | ✅ `LeadCaptureApiTest` |
| Storage (`leads` table) | ✅ |
| Admin visibility | ✅ Operations / Marketing leads routes |

---

## Phase 6 — Mobile & UX

- [x] Public layout uses responsive Tailwind (`layouts.app`, `medca-*` tokens)
- [ ] Manual pass: iPhone/Android — header, footer, contact form, service detail
- [ ] Manual pass: tablet — services grid / carousel
- [ ] Lighthouse mobile score on production (ops)

---

## Phase 7 — Pre-launch QA

| Area | Automated | Manual |
|------|-----------|--------|
| Services | ✅ MedcaLaunch | Photo swap |
| Pages | ✅ HTTP 200 | Copy review |
| SEO | ✅ sitemap test | GSC submit |
| Leads | ✅ | CRM workflow |
| Media | Placeholders | Real assets |
| Performance | — | CDN / cache |
| Accessibility | — | axe scan |
| Navigation | ✅ seeder | Click-through |
| Links | ✅ service routes | External profiles |
| Forms | ✅ lead test | Spam/rate limits |

---

## Phase 8 — Go / no-go

| Criterion | Status |
|-----------|--------|
| Critical launch blockers | **None** |
| Lead capture | **Works** |
| Service pages | **Work** |
| SEO configured | **Yes** (submit sitemap post-deploy) |
| Mobile acceptable | **Pending manual UX pass** |

**Recommended decision:** **GO** for soft launch after ops replaces placeholder service images and runs manual mobile QA.

---

## Rollback

```bash
# DB restore from backup taken before:
php artisan medca:launch-seed

# Or revert seeders only (partial):
# Restore services/pages from /var/backups/medca-launch-YYYYMMDD-HHMMSS/
```

**Backup reference:** `/var/backups/medca-launch-20260603-115837/` (documented at seed time)
