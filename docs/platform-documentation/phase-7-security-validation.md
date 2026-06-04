# Phase 7 — Security Validation Report

**Status:** PASS with caveats  
**Date:** 2026-05-30

## Middleware protection (admin)

Standard stack for module routes:

```
auth → active → verified → auto.logout → module:{key} → role:{roles}
```

| Surface | Module | Role gate |
|---------|--------|-----------|
| Dashboard | dashboard | all 5 roles |
| Site Architect | site_architect | editor+ |
| Operations | operations | manager+ |
| Marketing | marketing | manager+ |
| Growth (GET) | growth_center | all 5 |
| Growth (mutate) | growth_center | editor+ |
| User Management | user_management | layered (viewer index, manager CRUD, super destroy) |
| Security | security | admin+ |
| Settings + System | settings | admin+ |
| Backup | settings | super_admin + backup.operator |
| Maintenance | settings | super_admin |

## Unauthorized access handling

- Missing module → 403 via `EnsureModuleAccess`
- Wrong role → 403 via `CheckRole`
- Inactive user → `active` middleware logout
- Unverified email → blocked on verified routes (not profile)

**Tests:** `ModuleAccessTest`, `SecurityModuleTest`, `MarketingSecurityTest`, `ThemeSecurityTest`, `UserManagementTest`

## Module access restrictions

- Persisted per user in `module_access` JSON
- Sidebar hides modules without grant
- **Gap:** `module_access = null` resolves to full grants (`User::resolvedModuleAccess`)

## Admin-only areas

- Security module surface
- Settings / System (admin, super_admin)
- Theme writes (admin+ policies)
- Legacy integrations JSON API (admin middleware)

## Super Admin-only areas

- User destroy
- Maintenance mode POST
- Backup routes (plus operator name list)
- `settings.system.*` operations

## Root account

- Email-based `isRootSuperAdmin()` — separate from `super_admin` role
- Protected from edit/delete by non-root users

## API security

- `routes/api.php`: Sanctum + `module:growth_center` on competitor API
- Policies enforce mutation roles (editor+)
- Public lead capture: separate validation/throttling (see `LeadCaptureApiTest`)

## Security module UI

Exposes: failed logins, role denials, session timeouts, upload rejections, firewall rules, audit log preview — read-only metrics from `activity_logs` and config.

## Findings summary

| Severity | Finding |
|----------|---------|
| Medium | Null `module_access` = full module access |
| Medium | Integrations API without module middleware |
| Low | No rate-limit audit on workspace search (auth required) |
| Low | Role case sensitivity in `CheckRole` |

No security regressions introduced by navigation restructure (routes additive, same middleware groups).
