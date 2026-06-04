# Module Permission Matrix

**Reference for operators and auditors.**  
Enforcement: `EnsureModuleAccess` + `CheckRole` + policies.

## Module grants (persistence)

Stored in `users.module_access` as JSON booleans. Keys:

`dashboard`, `site_architect`, `operations`, `marketing`, `growth_center`, `user_management`, `security`, `settings`

Super Admin configures grants in User Management edit UI (unchanged).

## Role × route access (when module granted)

| Module | viewer | editor | manager | admin | super_admin |
|--------|:------:|:------:|:-------:|:-----:|:-----------:|
| dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| site_architect | — | ✓ | ✓ | ✓ | ✓ |
| operations | — | — | ✓ | ✓ | ✓ |
| marketing | — | — | ✓ | ✓ | ✓ |
| growth_center (read) | ✓ | ✓ | ✓ | ✓ | ✓ |
| growth_center (write) | — | ✓ | ✓ | ✓ | ✓ |
| user_management (list) | ✓ | ✓ | ✓ | ✓ | ✓ |
| user_management (mutate) | — | — | ✓ | ✓ | ✓ |
| user_management (delete) | — | — | — | — | ✓ |
| security | — | — | — | ✓ | ✓ |
| settings / system nav | — | — | — | ✓ | ✓ |
| backup UI + actions | — | — | — | — | ✓† |
| maintenance | — | — | — | — | ✓ |

†Backup actions require `config('settings.backup_operator_names')` match.

## Policy overlays (selected)

| Resource | view | create/update | delete |
|----------|:----:|:-------------:|:------:|
| Competitors | growth module + viewer+ | editor+ | admin+ |
| Blueprint generate | site_architect + manager+ | — | — |
| Deployment packages | site_architect + admin+ | — | — |
| Theme config | settings + admin+ | admin+ | — |
| Users | user_management | manager+ (route) | super_admin |

## Special accounts

| Type | Behavior |
|------|----------|
| Root account (`config('root_account.email')`) | All modules; protected from peer edit |
| `module_access = null` | Treated as all modules **true** (audit gap) |
| Inactive user | Logged out via `active` middleware |

## API (Sanctum)

| Prefix | Module | Role middleware |
|--------|--------|-----------------|
| Growth competitor API | `growth_center` | None (policies only) |

## Not module-gated

| Route group | Gate |
|-------------|------|
| `admin/settings/integrations/*` | `AdminMiddleware` (admin, super_admin) |

See `phase-2-permission-validation.md` for full gap list.
