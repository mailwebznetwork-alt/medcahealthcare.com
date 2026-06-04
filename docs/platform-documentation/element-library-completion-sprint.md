# Element Library Completion Sprint

**Date:** 2026-05-30  
**Scope:** Expand reusable blocks (Git-managed) for blueprints, sections, and landing pages — no Operations, Marketing, Growth, Security, or navigation changes.

---

## 1. Element inventory

**Canonical registry:** `config/block_templates.php` + `config/block_templates_shared.php`  
**Blade markup:** `resources/views/blocks/{category}/{slug}.blade.php`  
**Wrapper component:** `resources/views/components/blocks/element-wrap.blade.php`

| # | Category | Slugs (representative) | Count |
|---|----------|------------------------|-------|
| 1 | Hero | `hero-home` … + `hero-centered`, `hero-split`, `hero-video`, `hero-healthcare` | 11 |
| 2 | CTA | `cta-home`, `cta-services`, `cta-simple`, `cta-split`, `cta-banner`, `cta-sticky` | 6 |
| 3 | Features | `features-grid`, `features-icons` | 2 |
| 4 | Services | Service grids, carousel, `services-benefits`, detail layouts | 10+ |
| 5 | Statistics | `statistics-row`, `statistics-cards` | 2 |
| 6 | Process | `process-steps`, `process-flow` | 2 |
| 7 | Testimonials | `testimonials-carousel`, `testimonials-grid`, `testimonials-highlight` | 3 |
| 8 | Reviews | `reviews-grid`, `reviews-bar` | 2 |
| 9 | FAQ | `faq-accordion`, `faq-columns` | 2 |
| 10 | Team | `team-grid`, `team-leaders` | 2 |
| 11 | Gallery | `gallery-grid`, `gallery-showcase`, `before-after` | 3 |
| 12 | Video | `video-embed`, `video-sidecar` | 2 |
| 13 | Contact | `contact-info`, `contact-split`, contact heroes | 3+ |
| 14 | Forms | `form-callback`, `form-newsletter` | 2 |
| 15 | Pricing | `pricing-tiers`, `pricing-table` | 2 |
| 16 | Comparison | `comparison-features` | 1 |
| 17 | Trust Bar | `trust-bar-icons`, `trust-bar-badges` | 2 |
| 18 | Logos / Partners | `logos-partners` | 1 |
| 19 | Location Coverage | `locations-coverage`, `locations-overview-home`, `location-radius` | 3 |
| 20 | Before / After | `before-after` | 1 |
| 21 | Timeline | `timeline-milestones` | 1 |
| 22 | Cards | `cards-icon-row`, `cards-image-row` | 2 |
| 23 | Content Blocks | `body-about`, `content-prose`, `content-split` | 3+ |
| 24 | Callouts | `callout-tip`, `callout-highlight` | 2 |
| 25 | Lead Magnets | `lead-magnet-guide`, `lead-magnet-webinar` | 2 |

**Totals:** **70** Git-managed templates (22 page-specific + **48** shared elements).

**Sync:** `php artisan blocks:sync` or `php artisan blocks:sync --category=shared`

---

## 2. Missing elements report

| Category | Pre-sprint | Post-sprint |
|----------|------------|-------------|
| Hero variants (centered/split/video/healthcare) | Partial (page heroes only) | **Added** |
| CTA variants | 2 page CTAs | **+4 shared** |
| Features, Statistics, Process | Missing | **Added** |
| Testimonials, Reviews, FAQ, Team | Style-pack only | **Added** |
| Gallery, Video, Pricing, Comparison | Missing | **Added** |
| Trust, Logos, Timeline, Cards, Callouts, Lead magnets | Missing | **Added** |
| Forms | Module tokens only | **+2 CTA-to-form blocks** |

**Remaining gaps (by design):**

- Live **dynamic form** rendering still uses `{{module:key}}` — form blocks link to `/contact` until a module is inserted.
- **Google review feed** in `reviews-*` blocks is placeholder copy — wire to Growth/reviews API in a future sprint (out of scope here).

---

## 3. Variant inventory

| Family | Variants (slugs) |
|--------|------------------|
| **Hero** | `hero-centered`, `hero-split`, `hero-video`, `hero-healthcare` (+ 7 page heroes) |
| **CTA** | `cta-simple`, `cta-split`, `cta-banner`, `cta-sticky` |
| **Testimonials** | `testimonials-carousel`, `testimonials-grid`, `testimonials-highlight` |
| **FAQ** | `faq-accordion`, `faq-columns` |
| **Statistics** | `statistics-row`, `statistics-cards` |
| **Process** | `process-steps`, `process-flow` |
| **Gallery** | `gallery-grid`, `gallery-showcase` |
| **Video** | `video-embed`, `video-sidecar` |
| **Pricing** | `pricing-tiers`, `pricing-table` |
| **Lead magnet** | `lead-magnet-guide`, `lead-magnet-webinar` |

**Style variants:** Each slug also supports `style_1`…`style_5` via style packs (`design_system.variant_classes`).

---

## 4. Deployment compatibility report

| Capability | Status |
|------------|--------|
| `{{block:slug}}` tokens | All 70 slugs |
| Blueprint `blocks_json` | Compatible — see new section packs |
| Section Library | **2 new builtins:** `landing_healthcare_full`, `landing_conversion_strip` |
| Deployment packages | Export/import unchanged — includes blocks + sections |
| Block presets | `settings_json` only — compatible |
| Media mapping | Hero/video/gallery families use `design_system.media_slots` |
| Global content | Page-level `{{global:*}}` interpolation unchanged |
| Theme | Draft/publish flow unchanged |

**New section presets:**

- `landing_healthcare_full` — hero → trust → features → stats → testimonial → FAQ → CTA banner  
- `landing_conversion_strip` — split hero → pricing → lead magnet → sticky CTA  

---

## 5. Blueprint compatibility report

| Check | Result |
|-------|--------|
| Existing blueprints (`home_healthcare`, etc.) | Unchanged — still use original slugs |
| New elements referenceable in Blueprint Builder | **Yes** — all `shared` category |
| `BlockSettingsResolver` + style packs | Extended `block_type_families` for new types |
| `BlueprintPageGenerator` | No code change required |
| Landing page definitions | Can compose any shared slug in `landing_pages` arrays |

**Recommendation:** Duplicate `home_healthcare` as a v2 blueprint using `hero-healthcare` + shared funnel blocks when ready (optional content ops task).

---

## 6. Production readiness report

| Check | Status |
|-------|--------|
| `php artisan blocks:sync --category=shared` | Run on deploy |
| Tests | `ElementLibraryCompletionTest`, `BlockTemplateGovernanceTest` (70 templates) |
| Full suite | 380 tests passing |
| Responsive layout | Tailwind `md:` / `lg:` breakpoints on all shared elements |
| Theme / style packs | `healthcare_*` packs map families |
| Breaking changes | None — existing 22 templates untouched |

**Score:** **READY** for blueprint packs and landing page composition.

---

## Architecture (unchanged)

```
config/block_templates.php
    → BlockTemplateSyncService
    → blocks table (is_managed)
    → ContentParser + BlockSettingsResolver
    → Blade (medca-block wrapper)
```

**Design system updates:** `config/design_system.php` — `block_type_families` for Features, Statistics, Testimonials, FAQ, Team, Gallery, etc.  
**Block Factory UI:** `config/block_factory.php` — expanded type groups for dropdown filtering.
