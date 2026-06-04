# Platform composition repair — blockers

**Status:** B1–B6 **resolved** in Phase B (2026-06-03). See `PLATFORM-COMPOSITION-PHASEB-REPORT.md`.

Low-risk recovery and Phase B consolidation are complete. Remaining items below need **future** work only if product expands scope (not blockers for current architecture).

---

## Resolved in Phase B

| ID | Decision | Implementation |
|----|----------|----------------|
| B1 | Block `settings_json.content` owns hero/CTA copy | `config/block_content_schemas.php`, `BlockContent`, Block Studio content panel, updated hero/CTA/contact blades |
| B2 | Preview-first editor; production render path | Pages edit iframe → `site-architect.pages.preview` |
| B3 | Forms: submission API, pages placement, blocks presentation | `config/contact_forms.php`, `form-callback` / `contact-info` updates |
| B4 | Page SEO canonical when linked page has meta | `ServiceSeoOwnership`, `_seo-canonical-banner`, readonly service SEO fields |
| B5 | Deprecate empty Section Library UI | `platform_composition.section_library_deprecated`, admin notices; parser unchanged |
| B6 | Blocks first-class; elements not exposed in admin | Block Studio copy; no Element Library nav added |

---

## Remaining (non-blocking — architectural expansion)

| Item | Why not done in Phase B |
|------|-------------------------|
| Embedded Livewire contact form on every page | Requires module registration + front-end form component (no new tables, but new UI surface) |
| Auto-seed `settings_json.content` for all 71 blocks | Optional migration command; defaults in schema preserve current public output |
| Remove Section Library routes entirely | Would break bookmarks; deprecation banner + legacy tab label preserves compatibility |
| Full visual page builder | Explicitly out of scope per B2 |

---

## Historical reference

Original blocker text and options are archived in git history and `PLATFORM-COMPOSITION-REPAIR-PLAN.md` §6.
