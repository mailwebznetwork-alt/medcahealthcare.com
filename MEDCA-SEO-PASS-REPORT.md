# Medca SEO Pass Report

**Date:** 2026-06-03  
**Scope:** Public marketing surface + six launch services  
**Method:** Seeder verification + automated HTTP tests

---

## Summary

| Check | Result | Evidence |
|-------|--------|----------|
| Canonical URLs | Pass | Services at `/services/{code}`; CMS pages at root slugs |
| Page titles | Pass | `MedcaLaunchPagesSeeder` meta_title per page |
| Meta descriptions | Pass | All six pages + service `service_seo` rows |
| Open Graph | Pass | `public/services/show.blade.php` + `site-seo-meta` for pages |
| Sitemap index | Pass | `GET /sitemap.xml` → 200 |
| Sitemap — services | Pass | `GET /sitemap-services.xml` contains all six codes |
| Sitemap — pages | Pass | Growth `SeoService::generatePagesSitemapXml()` |
| robots.txt | Pass | `GET /robots.txt` → 200 |
| Internal linking | Pass | Header nav, home → `/services`, carousel tokens |
| Service SEO ownership | Pass | `ServiceSeoOwnership` + guarded `syncSeo` (platform) |
| Page SEO ownership | Pass | Marketing pages own meta; service detail pages synced on provision |

---

## Page SEO (launch seeder)

| Slug | meta_title (truncated) |
|------|------------------------|
| home | Medca Health Care — Premium Home Healthcare in Bangalore |
| about-us | About Medca Health Care — Doctor-Led Home Healthcare… |
| services | Medca Services — Home Nursing, Elder Care… |
| locations | Service Areas — Medca Home Healthcare Across Bangalore |
| contact | Contact Medca Health Care — Book Home Healthcare… |
| careers | Careers — Medca Health Care \| Bangalore… |

---

## Service SEO

| Code | meta_title |
|------|------------|
| homenursing-services | Home Nursing in Bangalore \| Medca Health Care |
| elder-care | Elder Care at Home in Bangalore \| Medca Health Care |
| caregivers | Caregiver Services at Home \| Medca Health Care Bangalore |
| doctor-home-visit | Doctor Home Visit in Bangalore \| Medca Health Care |
| physiotherapy-at-home | Physiotherapy at Home in Bangalore \| Medca Health Care |
| icu-care-at-home | ICU Care at Home in Bangalore \| Medca Health Care |

Each record includes `meta_description`, `h1`, `h2`, `h3`, `focus_keywords`, and `ai_context`.

---

## Canonical & indexing rules

- **Published services** (`publish_status=published`, `visibility=public`, `is_active`) appear in `sitemap-services.xml`.
- **Draft/private** services emit `noindex` on detail template when not listed publicly.
- **Linked detail page:** When Operations links `detail_page_id`, page meta can override service meta per `ServiceSeoOwnership`.

---

## Post-launch ops (not blockers)

1. Set production `APP_URL` and run `php artisan medca:normalize-site-urls` if migrating hosts.
2. Submit `https://{domain}/sitemap.xml` in Google Search Console.
3. Configure `MEDCA_PUBLIC_PROFILE_URL` for header location pill if using Google Business Profile.
4. Replace placeholder JPEGs so `og:image` uses brand photography.

---

## Verification commands

```bash
php artisan test --filter=MedcaLaunch
curl -sS -o /dev/null -w "%{http_code}" https://YOUR_DOMAIN/sitemap-services.xml
curl -sS https://YOUR_DOMAIN/robots.txt | head
```

**Test result:** 5 tests in `MedcaLaunchDataTest` (includes sitemap assertions) — passed 2026-06-03.
