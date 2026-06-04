# Page Builder UX Autopsy

**Scope:** User experience only — create → section → edit → preview → publish.  
**Out of scope:** Architecture, parsers, DB schema, rendering pipeline, route inventory.  
**Date:** 2026-06-02  
**Status:** Pre-change forensic (before/after captured in `PAGE-BUILDER-UX-REPORT.md` and `PAGE-BUILDER-CHANGELOG.md`).

---

## Executive summary

Editors were forced to think like developers: block slugs in dropdowns, `{{block:slug}}` tokens in lists, “Block Factory,” `settings_json.content`, and deploy-centric navigation. The primary happy path (Pages) was buried under factory/registry language. **Root cause:** internal block model leaked into every surface, not missing features.

---

## Flow 1 — Create Page

| Step | Before (friction) | Severity |
|------|-------------------|----------|
| Entry | Site Architect → **Pages** (OK) but tab group mixed Deploy/Advanced with content | Medium |
| New page | Form fields clear; slug field still technical for non-devs | Low |
| Mental model | “Page” vs “Blueprint” vs “Package” — three creation metaphors | High (admin only) |
| Editor role | Deploy/Advanced tabs visible → false paths | High |

**Hidden workflow:** Blueprint Builder could create pages without visiting Pages — power users only, but confused editors who clicked Deploy first.

**Jargon:** Blueprint, deployment package, block preset (vs template).

---

## Flow 2 — Add Block / Section

| Step | Before (friction) | Severity |
|------|-------------------|----------|
| Primary action | **Add block** + `<select>` of `hero-healthcare`, `cta-split`, … | Critical |
| Discovery | No category, no description, no preview | Critical |
| Duplicate | “Add block line” vs picking from Factory vs Blueprint seed | Medium |
| Slug exposure | Raw slug in list + token `{{block:slug}}` always visible | High |

**Developer concepts shown:** `block_slug`, Element — prefix names, managed vs unmanaged.

**After target (implemented):** **Add section** → visual modal (name, description, category, gradient preview tile); slug internal only.

---

## Flow 3 — Edit Block / Section Content

| Step | Before (friction) | Severity |
|------|-------------------|----------|
| Link from page | “Edit block” / studio route with `?block=` slug | Medium |
| Block Studio | Tab labels referenced **content** schema paths; “Block” in titles | High |
| Section dropdown | Flat slug list | High |
| Context | No helper explaining Settings → Global content for phone/WhatsApp | Medium |

**Jargon:** `settings_json`, schema, Block Factory, Legacy Sections, presets vs templates.

**After target:** **Section Content**, grouped section selector, **Section content** / **Images & media** tabs, contextual copy.

---

## Flow 4 — Preview

| Step | Before (friction) | Severity |
|------|-------------------|----------|
| Page preview | Public preview link (OK) | Low |
| Section preview | “Preview block” in studio | Medium (wording) |
| Expectation | Editors unsure if preview is page-level or section-level | Medium |

No route changes; labels → **Preview section** / page-level preview unchanged.

---

## Flow 5 — Publish

| Step | Before (friction) | Severity |
|------|-------------------|----------|
| Save draft | Clear on Pages | Low |
| Publish | Status field + publish action (OK) | Low |
| Fear | “Deployment Engine” branding nearby suggested infra deploy | Medium (admin) |

Publish path unchanged; navigation no longer implies deploy is required to go live.

---

## Cross-cutting findings

### Technical jargon inventory (editor-visible)

- Block / Add block / block slug dropdown  
- Blocks Factory, Module Builder, Legacy Sections  
- Blueprint Builder, Packages, Deployment Engine  
- `{{block:…}}` tokens in UI  
- Element — Hero Healthcare style names  
- settings_json / schema field keys in labels  

### Slug exposure surfaces (audited)

1. Pages / Blogs — section list (fixed: friendly name; token admin-only)  
2. Pages / Blogs — add section (fixed: visual picker)  
3. Block Studio — section select (fixed: optgroups by category)  
4. Block Factory — intentional (admin/dev; hidden tab for editor)  
5. URL query `?block=` — acceptable deep-link; not shown as primary UX  

### Duplicate / confusing actions

| Action A | Action B | Confusion |
|----------|----------|-----------|
| Add block (Pages) | Register in Factory | Same outcome, different doors |
| Presets | Templates | Renamed in nav only |
| Sections tab | Legacy Sections | “Sections” meant blocks vs old library |
| Blueprint generate | Create page | Two “new page” paths |

### Role matrix (visibility only)

| Surface | Editor | Manager | Admin |
|---------|--------|---------|-------|
| Pages, Blogs, Media | Yes | Yes | Yes |
| Section Content, Style Templates | Yes | Yes | Yes |
| Blocks Factory | **Hidden tab** | Yes | Yes |
| Deploy / Advanced | Hidden | Hidden | Yes |

Routes and permissions unchanged; direct URL still works for authorized roles.

---

## Discoverability score (subjective)

| Concept | Before | After |
|---------|--------|-------|
| Pages | 7/10 | 8/10 |
| Sections | 3/10 | 8/10 |
| Templates | 5/10 | 7/10 |
| Media | 7/10 | 7/10 |
| Factory / Deploy | 2/10 for editors | N/A (hidden) |

---

## Remaining friction (not fixed in this pass)

- Page **slug** field on create/edit still technical.  
- **Style Templates** still visible to editors (product may want to hide).  
- Section picker uses **gradient tiles**, not screenshot assets (no image library wired).  
- Blogs compose header copy less aligned than Pages (minor).  
- Developer modal “create block with code” still reachable for manager/admin on picker footer.  

---

## References

- Prior Site Architect work: `SITE-ARCHITECT-USER-JOURNEY-AUTOPSY.md`, `SITE-ARCHITECT-UX-IMPROVEMENTS.md`  
- Implementation: `PAGE-BUILDER-CHANGELOG.md`, `PAGE-BUILDER-UX-REPORT.md`
