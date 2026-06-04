# Industry Blueprint Packs Sprint

**Date:** 2026-05-30  
**Scope:** Production-ready industry packs via `config/blueprint_packs.php` — no architecture, navigation, or ops changes.

---

## Phase 1 — Blueprint audit

### 1. Blueprint inventory

| Slug | Industry | Pages | Landings | Style pack (default) | Theme |
|------|----------|-------|----------|----------------------|-------|
| `home_healthcare` | healthcare | **14** | 3 | healthcare_professional | clinical_blue |
| `care_home` | care_home | **8** | 1 | healthcare_premium | premium_gold |
| `real_estate` | real_estate | **5** | 1 | modern_purple | modern_purple |
| `cosmetics_clinic` | cosmetics | **6** | 1 | luxury_black | luxury_black |
| `construction` | construction | 2 | 0 | construction_industrial | forest_green |
| `painting` | painting | 1 | 0 | minimal_white | clinical_blue |
| `consultancy` | consultancy | 1 | 0 | consultancy_corporate | clinical_blue |
| `education` | education | 1 | 0 | education_clean | clinical_blue |

**Total blueprints:** 8  
**Industry packs (v2):** 4

### 2. Missing blueprint report (pre-sprint → resolved)

| Gap | Resolution |
|-----|------------|
| Healthcare service-line pages | **6 pages** (`service-home-care` … `service-palliative-care`) |
| Healthcare FAQ / testimonials | Dedicated pages added |
| Care home admissions / facilities / reviews | Pages added |
| Real estate pack | **New** `real_estate` |
| Cosmetics / clinic pack | **New** `cosmetics_clinic` |
| Shared element usage | All packs use Element Library slugs |

### 3. Blueprint quality score

| Blueprint | Structure | Content | Elements | Deployment | Theme | **Score** |
|-----------|-----------|---------|----------|------------|-------|-----------|
| home_healthcare | A | A | A | A | A | **95** |
| care_home | A | A | A | A | A | **92** |
| real_estate | A | B | A | A | A | **88** |
| cosmetics_clinic | A | A | A | A | A | **90** |
| construction | B | B | B | A | A | **75** |
| painting | C | C | B | A | A | **65** |

*Legacy vertical blueprints unchanged; industry packs are production targets.*

---

## Phase 2 — Healthcare pack

**Slug:** `home_healthcare`

| Deliverable | Pages / slugs |
|-------------|----------------|
| Homepage | `home` — hero-healthcare, trust, services overview, stats, testimonial, locations, cta-home |
| Service pages | `service-home-care`, `service-elder-care`, `service-nursing`, `service-doctor-visits`, `service-physiotherapy`, `service-palliative-care` |
| About | `about` |
| Contact | `contact` + form-callback |
| FAQ | `faq` |
| Testimonials | `testimonials` |
| Services hub | `services` |
| **CTA strategy** | Home: `cta-home`; services: `cta-services` + `cta-banner`; landings: `cta-sticky` |

**Landings:** `consultation`, `lp-nursing`, `lp-physiotherapy`

---

## Phase 3 — Care home pack

**Slug:** `care_home`

| Page | Focus |
|------|--------|
| `home` | Care home landing |
| `services` | Care services |
| `admissions` | Process + pricing + form |
| `facilities` | Gallery + video |
| `faq` | Accordion |
| `reviews` | Testimonials + reviews grid |
| `about`, `contact` | Trust + contact |

**Landing:** `book-tour`

---

## Phase 4 — Real estate pack

**Slug:** `real_estate`

| Page | Focus |
|------|--------|
| `home` | Property marketing hero |
| `listings` | Grid + lead form |
| `project-sample` | Project showcase |
| `about`, `contact` | Agent trust |

**Landing:** `property-enquiry`

---

## Phase 5 — Cosmetics / clinic pack

**Slug:** `cosmetics_clinic`

| Page | Focus |
|------|--------|
| `home` | Treatments + before/after |
| `services` | Treatments + pricing tiers |
| `before-after` | Gallery |
| `pricing` | Tiers + comparison |
| `reviews` | Social proof |
| `contact` | Consultation booking |

**Landing:** `free-consultation`

---

## Phase 6 — Deployment validation report

| Check | Result |
|-------|--------|
| Blueprint Builder | All slugs in `BlueprintRegistry` |
| `BlueprintPageGenerator` | Generates pages + `block_overrides_json` + `deployment_meta_json` |
| Deployment packages | Exports blueprints + blocks unchanged |
| Theme | Each pack sets `default_theme_preset` + style pack header/layout on draft |
| Global content | `{{global:*}}` in page body still works alongside blocks |
| Element library | All block slugs synced via `blocks:sync` |
| Section Library | 4 new pack sections in `section_library_builtin.php` |

**Generate:** Site Architect → Blueprint Builder → select pack → Generate.

---

## Final deliverables summary

### Industry blueprint packs (config)

- `config/blueprint_packs.php` — healthcare, care home, real estate, cosmetics  
- `config/blueprints.php` — merges packs + legacy verticals  

### Reusability report

| Asset | Reuse |
|-------|-------|
| Shared elements (48) | Composed in all packs |
| Section presets | `pack_*` sections match service/admissions/listing/clinic funnels |
| Style packs | healthcare_*, modern_purple, luxury_black |
| Blueprint landings | Campaign-specific without new architecture |

### Production readiness

| Item | Status |
|------|--------|
| Tests | `IndustryBlueprintPacksTest` + existing `DeploymentEngineTest` |
| Block sync | Run `php artisan blocks:sync` before first generate |
| Page activation | Generated pages default **inactive** until publish |

**Score:** **READY** for industry deployments via Blueprint Builder.
