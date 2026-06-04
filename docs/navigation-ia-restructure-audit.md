# Navigation & Information Architecture Restructure Audit

**Project:** MarkOnMinds / Medca Platform  
**Date:** 2026-05-30  
**Scope:** Navigation, sidebar hierarchy, URL aliases, discoverability — **no feature removal**

---

## 1. Current Navigation Structure (pre-restructure baseline)

| Order | Sidebar label | Module key | Default route | Notes |
|------:|---------------|------------|---------------|-------|
| 1 | Dashboard | `dashboard` | `dashboard` | Unchanged |
| 2 | Site Architect | `site_architect` | `site-architect.pages.index` | Flat / scattered sub-nav |
| 3 | Operations | `operations` | `modules.operations` → Job Portal | Frozen behavior |
| 4 | Marketing | `marketing` | `modules.marketing` | Intelligence secondary |
| 5 | Growth Center | `growth_center` | `modules.growth-center` | Many routes tab-redirect only |
| 6 | User Management | `user_management` | `user-management.index` | **Frozen** |
| 7 | Security | `security` | `modules.security` | Single surface page |
| 8 | Settings | `settings` | `settings.integrations` (legacy) | Integrations mixed with theme |

**Module access keys (unchanged):** `dashboard`, `site_architect`, `operations`, `marketing`, `growth_center`, `user_management`, `security`, `settings` — no `system` key in persistence.

---

## 2. Proposed / Implemented Navigation Structure

| Order | Sidebar label | Access grant | Entry route | Change type |
|------:|---------------|--------------|-------------|-------------|
| 1 | Dashboard | `dashboard` | `dashboard` | Order only |
| 2 | Site Architect | `site_architect` | `site-architect.pages.index` | Grouped workspace tabs |
| 3 | Operations | `operations` | `modules.operations` | **No workflow change** |
| 4 | Marketing | `marketing` | `modules.marketing` | Primary tab strip |
| 5 | Growth Center | `growth_center` | `growth-center.competitors.index` | Expanded tab strip + deep links |
| 6 | User Management | `user_management` | `user-management.*` | **Unchanged** |
| 7 | Security | `security` | `modules.security` | Anchor nav + section IDs |
| 8 | **System** | `settings` (alias) | `system.index` → `system.overview` | Supplemental nav only |
| 9 | Settings | `settings` | `settings.appearance` | Theme/content/backup/maintenance |

**Implementation:** `App\Support\AdminNavigation`, `User::visibleSidebarNodes()`, `mom-sidebar-nav.blade.php`.

---

## 3. Route Inventory (admin IA touchpoints)

### System (new read-only hub)

| Method | URI | Name |
|--------|-----|------|
| GET | `/system` | `system.index` → redirects `system.overview` |
| GET | `/system/overview` | `system.overview` |
| GET | `/system/queue` | `system.queue` |
| GET | `/system/scheduler` | `system.scheduler` |
| GET | `/system/health` | `system.health` |
| GET | `/system/integrations` | `system.integrations` → `/settings/integrations` |

Middleware: `module:settings`, `role:admin,super_admin` (same as Settings).

### Site Architect aliases

| Alias URI | Redirect target | Route name |
|-----------|-----------------|------------|
| `/site-architect/sections` | `/site-architect/section-library` | `site-architect.sections.index` |
| `/site-architect/presets` | `/site-architect/block-presets` | `site-architect.presets.index` |

Legacy URIs `/section-library`, `/block-presets` **unchanged**.

### Growth Center alias

| Alias URI | Redirect target |
|-----------|-----------------|
| `/growth-center/war-room` | `/growth-center/war-room/dashboard` |

### Settings

| Route | Change |
|-------|--------|
| `settings.index` | Redirect → `settings.appearance` (was integrations) |
| `settings.integrations`, `settings.webhooks` | **Preserved**; UI shell → `<x-system.shell>` |

All other admin routes in `routes/web.php` remain registered; full count validated by test suite (350 tests).

---

## 4. Orphan Route Inventory

Routes that existed but were hard to discover — now linked in UI (legacy URLs preserved):

| Area | Route(s) | Exposure |
|------|----------|----------|
| Growth | `growth-center.seo.*`, `growth-center.geo.*`, `growth-center.war-room.*`, `growth-center.aeo.index` | Growth primary tabs + deep-link row |
| Growth | Tab-only redirects (`readiness`, `ga4`, `ai-pulse`, etc.) | Competitor hub `?tab=` |
| Marketing | `modules.marketing.intelligence` | Marketing tab strip |
| System | Queue / scheduler / health | System sub-nav |
| Site Architect | Section library, block presets, module builder, etc. | Grouped Site Architect tabs |

**No routes removed** from `routes/web.php`.

---

## 5. Permission Impact Analysis

| Change | Impact |
|--------|--------|
| New `system` sidebar label | Uses existing `settings` module grant via `AdminNavigation::accessModuleKey()` |
| No new `module_access` key | Super Admin module toggles unchanged |
| System routes | Same middleware stack as Settings |
| User Management | **Zero** middleware / policy / form changes |
| Operations | Same `module:operations`; entry still Job Portal redirect |

Super Admin remains authoritative for all module grants and per-user `module_access` JSON.

---

## 6. User Management Verification

**Files intentionally not modified:**

- `app/Http/Controllers/UserManagement/*`
- `app/Http/Requests/UserManagement/*`
- `resources/views/user-management/*`
- User Management routes in `routes/web.php`
- `EnsureModuleAccess` / role middleware for UM

**Only related change:** `User::visibleSidebarNodes()` reorders display using `AdminNavigation::sidebarOrder()` — same `hasModuleAccess()` checks, no new permissions.

---

## 7. Feature Preservation Verification

| Requirement | Status |
|-------------|--------|
| No features removed | ✅ |
| No routes removed | ✅ |
| No modules removed from `ModuleAccess::keys()` | ✅ |
| No permissions removed | ✅ |
| Operations workflows unchanged | ✅ |
| User Management frozen | ✅ |
| Super Admin authority | ✅ |
| `php artisan test` | ✅ 350 passed |

---

## 8. Redirect Mapping Plan

| From | To | HTTP |
|------|-----|------|
| `/site-architect/sections` | `/site-architect/section-library` | 301 |
| `/site-architect/presets` | `/site-architect/block-presets` | 301 |
| `/growth-center/war-room` | `/growth-center/war-room/dashboard` | 301 |
| `/system` | `/system/overview` | 301 |
| `/system/integrations` | `/settings/integrations` | 301 |
| `/settings` | `/settings/appearance` | 302 (controller) |

Bookmarks to legacy paths continue to work.

---

## 9. Sidebar Mapping Plan

| Sidebar | Sub-navigation |
|---------|----------------|
| Site Architect | Content / Blocks / Deploy / Advanced (`primary-tabs.blade.php`) |
| Marketing | Dashboard, Intelligence, Campaigns, Attribution, Reports |
| Growth Center | Readiness, Competitors, War Room, SEO, AEO, GEO, GA4, AI Pulse |
| Security | Overview, Audit, Activity, Failed logins, Access events (`security.partials.nav`) |
| System | Overview, Integrations, Webhooks, API (integrations UI), Queue, Scheduler, Health |
| Settings | Appearance, Global Content, Backup*, Maintenance*, link → System |

\*Role-gated as before.

---

## 10. Risk Assessment

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Users bookmarked `/settings` → integrations | Low | Integrations URL unchanged; Settings index → appearance |
| Duplicate highlight Settings vs System | Low | `AdminNavigation::activeRoutePatterns()` splits routes |
| Alias route names in tabs | Low | Named redirects; tests in `NavigationRestructureTest` |
| Scheduler page empty in web context | Low | Controller try/catch around `Schedule::events()` |

**Overall risk:** Low — navigation-only, backward-compatible redirects.

---

## Final Validation Checklist

- [x] No features removed  
- [x] No modules removed  
- [x] No permissions removed  
- [x] No workflows removed (Operations, UM)  
- [x] User Management unchanged (routes, controllers, views)  
- [x] Super Admin authority unchanged  
- [x] All tests passing  
- [x] Navigation clarity improved (grouped tabs + System hub)  
- [x] URL consistency improved (aliases)  
- [x] Discoverability improved (Growth deep links, Marketing tabs, Security anchors)

---

## Key Files

- `app/Support/AdminNavigation.php`
- `app/Http/Controllers/System/SystemOverviewController.php`
- `resources/views/system/*`, `resources/views/components/system/shell.blade.php`
- `resources/views/site-architect/partials/primary-tabs.blade.php`
- `resources/views/marketing/partials/primary-tabs.blade.php`
- `resources/views/growth-center/partials/primary-tabs.blade.php`
- `tests/Feature/NavigationRestructureTest.php`
