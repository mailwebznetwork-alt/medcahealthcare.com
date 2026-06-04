# Site composition — ownership map (Medca)

**Purpose:** One place to answer “ഫ്രണ്ടിൽ കാണുന്നത് എവിടെ എഡിറ്റ് ചെയ്യണം?” so Pages, Blocks, Sections, Modules, Operations, and Settings work as one pipeline—not four disconnected tools.

**Status:** Phase B complete — see `PLATFORM-COMPOSITION-PHASEB-REPORT.md` (2026-06-03).  
**Repair plan:** `PLATFORM-COMPOSITION-REPAIR-PLAN.md`.  
**Preview:** Operations service preview and Pages edit iframe use production `layouts.app` + `ContentParser`.

---

## 1. How public pages render (single pipeline)

```
Visitor URL
    → Route resolves Page (or Service → linked Page, or fallback Blade)
    → layouts/app.blade.php
    → ContentParser::parse(page.content)
         expand {{section:slug}}  → Section Library → nested {{block:slug}}
         expand {{block:slug}}    → blocks.code (+ BlockSettingsResolver)
         expand {{module:slug}}   → Livewire / DynamicModuleRenderer
         expand {{service:code}}  → register Service; often inside block code
    → HTML on frontend
```

**Rule:** Admin preview and public site must use this same parser path. Any screen that shows raw `{{block:...}}` to visitors is a bug; showing tokens in the **Pages list UI** is current admin design (to be replaced by composer + preview).

---

## 2. Ownership matrix (what edits what on the live site)

| What visitors see | Primary owner (data) | Composition (where it appears) | Implementation (how it looks) |
|-------------------|----------------------|--------------------------------|-------------------------------|
| Page layout order | **Page** `content` tokens | Site Architect → Pages | Block + Section slugs |
| Hero headline, CTA, bg image | **Block settings** or **Global content** keys (pick one per block) | Page includes `{{block:hero-*}}` | Block Factory `code` / view |
| Section packs (hero + trust + CTA) | **Section Library** item | `{{section:slug}}` on Page | Expands to blocks |
| Service title, summary, price, procedures | **Operations → Service** (`services` row) | Service detail Page + blocks using `$service` | `service-detail-*` blocks |
| Related service cards | **Page** or block inner `{{service:code}}` lines | `service-detail-related` block region | Carousel partial + catalog |
| Service SEO on live URL (when page linked) | **Page** meta (canonical); **Service** `service_seo` for fallback/migrate | Page SEO tab; Operations SEO tab | `ServiceDetailPageSeoSync` when page empty |
| Service FAQs on live page | **Page** `page_faqs` when filled; else **Service** `service_faqs` | Page / Operations | FAQ blocks + schema |
| Contact form fields & labels | **Block** + **Module config** + lang (today split) | `{{module:...}}` or contact block on Page | Livewire + validation |
| Leads from forms | **Leads** table (not Page content) | N/A (backend) | Controllers / Livewire |
| Header phone, logo, nav | **Theme / Settings / Global content** | Layout partials | ThemeResolver, settings |
| GEO pin messaging | **Pin codes** (Operations) + optional Page GEO | Location pages, service GEO tab | Modules / blocks |
| Blog body | **Blog** record | Blog route | ContentParser on blog.content |
| Careers jobs list | **Vacancies** (Operations) + careers blocks | Careers pages | Blocks + modules |

---

## 3. Editor journey (target — not all built yet)

### A. Marketing page (e.g. Home)

1. **Site Architect → Pages** — pick page; compose sections/blocks (target: visual composer + live preview, not token-only list).
2. **Block Factory** — edit structured fields for `hero-*`, `cta-*`, `trust-*` (target: settings UI; code = advanced).
3. **Global content** — only for values reused on many pages (phone, brand line).
4. **Preview** — same URL as public `/slug`.

### B. Service detail `/services/{code}`

1. **Operations → Enterprise Services** — catalog facts, publish, GEO, media, SEO/FAQ/schema (fallback + migrate).
2. **Link or provision detail Page** — `service-{code}` slug pattern.
3. **Site Architect → Page** — block order; insert related `{{service:other-code}}` where needed.
4. **Block Factory** — ensure `service-detail-hero` reads `$service`, not hardcoded copy.
5. **Public check** — open `/services/{code}` (not Operations preview-only fallback).

### C. Contact / lead capture

1. **Page** — place module/block token once.
2. **Module / block settings** — field list, labels, success message (target: single form builder).
3. **Leads** — submissions; no edit on Page content.

---

## 4. Known gaps (why frontend ≠ backend today)

| Gap | Symptom | Remediation direction |
|-----|---------|------------------------|
| Split ownership | Edit Operations; Page blocks unchanged | Bind blocks to `$service`; document per block |
| Token-only Page UI | Admin sees `{{block:slug}}`, not layout | Page composer + iframe preview |
| Block Factory = code textarea | “Only block code” | Block settings schema per template |
| Multiple SEO/FAQ homes | Meta differs Page vs Service | Banner + canonical rule (Page wins when linked) |
| Preview paths differ | Operations preview ≠ public page | Preview uses `ContentParser` + linked page |
| Inactive / missing block slug | Empty section on site | Health check on save |
| `{{service:code}}` without catalog row | Empty carousel slot | ServiceInsertCatalog + Operations create |
| Hardcoded copy in block `code` | Global/Operations edit ignored | Audit blocks; move to settings/global |

---

## 5. Reference implementation order (recommended)

| Phase | Scope | Outcome |
|-------|--------|---------|
| **R1** | One service detail (`service_code` TBD) end-to-end | Page + blocks + Operations + public URL match |
| **R2** | One marketing page (e.g. `home`) hero + one CTA | Section/block settings + preview parity |
| **R3** | One contact form module/block | Labels + submit + lead in one settings UI |
| **R4** | Site-wide block audit | Hardcoded → settings/global migration list |
| **R5** | Block Studio / composer UX | Replace token-only editing for editors |

---

## 6. Quick decision tree (Malayalam summary for editors)

- **സർവീസ് വില, വിവരണം, procedures, pincode, publish** → Operations → Enterprise Services  
- **ഏത് ബ്ലോക്കുകൾ, ഏത് ക്രമം** → Site Architect → Pages (അല്ലെങ്കിൽ service detail page)  
- **ഹീരോ/CTA ഡിസൈൻ, ഫോം ഫീൽഡ് ലേഔട്ട്** → Block Factory (ഭാവി: settings, ഇപ്പോൾ: code)  
- **ഹോംപേജ് ഹീരോ ടെക്സ്റ്റ് പല പേജിലും** → Global content (ഒരേ key ആണെങ്കിൽ മാത്രം)  
- **ഫോൺ, ലോഗോ, ഹെഡർ** → Settings / Theme  
- **ലീഡ് സബ്മിഷൻ കാണാൻ** → Leads (Page അല്ല)  

---

## 7. Related docs

- `SERVICES-IMPLEMENTATION-MASTERPLAN.md`
- `SERVICES-ARCHITECTURE-DECISION-REPORT.md`
- `PLATFORM-FORENSIC-AUTOPSY.md`

---

*Next step when implementation is approved: pick **R1** service code + **R2** page slug and wire preview parity + block `$service` binding audit.*
