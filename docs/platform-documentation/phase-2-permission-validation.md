# Phase 2 — Role & Permission Validation

**Status:** PASS (with documented gaps)  
**Date:** 2026-05-30

## Authorization model

Two layers (both required for admin routes):

1. **`module:{key}`** — `EnsureModuleAccess` → `User::hasModuleAccess()` from `users.module_access` JSON.
2. **`role:...`** — `CheckRole` exact match on `users.role`.

**Roles:** `viewer`, `editor`, `manager`, `admin`, `super_admin`  
**Modules:** `dashboard`, `site_architect`, `operations`, `marketing`, `growth_center`, `user_management`, `security`, `settings`

**System sidebar** uses supplemental key `system` mapped to **settings** grant (`AdminNavigation::accessModuleKey`).

## Role × module matrix (route middleware ceiling)

When `module_access[{module}] = true`:

| Module | viewer | editor | manager | admin | super_admin |
|--------|:------:|:------:|:-------:|:-----:|:-----------:|
| dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| site_architect | ✗ | ✓ | ✓ | ✓ | ✓ |
| operations | ✗ | ✗ | ✓ | ✓ | ✓ |
| marketing | ✗ | ✗ | ✓ | ✓ | ✓ |
| growth_center (read) | ✓ | ✓ | ✓ | ✓ | ✓ |
| growth_center (mutate) | ✗ | ✓ | ✓ | ✓ | ✓ |
| user_management (index) | ✓ | ✓ | ✓ | ✓ | ✓ |
| user_management (CRUD) | ✗ | ✗ | ✓ | ✓ | ✓ |
| user_management (destroy) | ✗ | ✗ | ✗ | ✗ | ✓ |
| security | ✗ | ✗ | ✗ | ✓ | ✓ |
| settings + system nav | ✗ | ✗ | ✗ | ✓ | ✓ |
| settings backup/maintenance | ✗ | ✗ | ✗ | ✗ | ✓* |

\*Backup **actions** also require `backup.operator` name allowlist.

**Legacy API:** `/admin/settings/integrations/*` — `admin` middleware only (no module check).

## Policy-level refinements (examples)

| Capability | Minimum role |
|------------|----------------|
| Blueprint Builder generate | manager+ |
| Block presets mutate | editor+ |
| Deployment packages | admin+ |
| Theme / appearance write | admin+ |
| Marketing lead export | manager+ |
| Competitor mutate | editor+ |

## Test coverage

| Area | Tests |
|------|-------|
| Module middleware | `ModuleAccessTest` |
| User Management | `UserManagementTest` |
| Marketing security | `MarketingSecurityTest` |
| Theme security | `ThemeSecurityTest` |
| Role matrix per route | **Not automated** |

## Gaps (documentation only — no changes this phase)

1. `module_access === null` → all modules granted at runtime.
2. Create user: missing role UI; `module_access` may not persist on store.
3. Edit user: `role` not changeable via UI; `role_label` only.
4. Manager+ can POST `role: super_admin` on update without hierarchy check.
5. Integrations JSON API without `module:settings`.

See `module-permission-matrix.md` for full reference.
