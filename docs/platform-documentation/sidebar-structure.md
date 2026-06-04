# Sidebar Structure Documentation

**Source of truth:** `App\ModuleAccess::navigation()`, `App\Support\AdminNavigation`

## Visual order

```
Dashboard
Site Architect
Operations
─────────────────
Marketing
Growth Center
─────────────────
User Management
Security
System          ← supplemental (settings grant)
Settings
```

## Section dividers

`AdminNavigation::sidebarSections()` inserts visual breaks between three groups (see above).

## Per-item configuration

| Sidebar key | Access key | Route | Icon |
|-------------|------------|-------|------|
| dashboard | dashboard | `dashboard` | layout-dashboard |
| site_architect | site_architect | `site-architect.pages.index` | drafting-compass |
| operations | operations | `modules.operations` | workflow |
| marketing | marketing | `modules.marketing` | megaphone |
| growth_center | growth_center | `modules.growth-center` | trending-up |
| user_management | user_management | `user-management.index` | users-round |
| security | security | `modules.security` | shield-check |
| system | **settings** | `system.index` | server |
| settings | settings | `settings.appearance` | settings |

## Active state rules

`AdminNavigation::activeRoutePatterns($navKey)` — examples:

- **operations:** `modules.operations`, `operations.*`
- **system:** `system.*`, `settings.integrations`, `settings.webhooks`, `admin.settings.integrations.*`
- **settings:** `settings.*` (appearance, global-content, backup, maintenance, theme preview) — excludes integrations when on system patterns

## Sidebar scroll

`resources/views/components/mom-sidebar-nav.blade.php`:

```html
class="mom-sidebar-nav-scroll ... overflow-y-auto ... custom-scrollbar"
```

Custom scrollbar CSS should be present in admin layout (4px width, `#1e293b` thumb).

## Visibility logic

`User::visibleSidebarNodes()`:

1. Iterates `AdminNavigation::sidebarOrder()`
2. Skips if `!hasModuleAccess(accessModuleKey($navKey))`
3. Injects supplemental System entry when settings granted
4. Does **not** change UM forms or stored permissions

## Submodule navigation (not in sidebar)

Each module uses horizontal tab strips or anchor nav — see:

- `site-architect/partials/primary-tabs.blade.php`
- `marketing/partials/primary-tabs.blade.php`
- `growth-center/partials/primary-tabs.blade.php`
- `operations/partials/primary-tabs.blade.php`
- `system/partials/nav.blade.php`
- `settings/partials/nav.blade.php`
- `security/partials/nav.blade.php`
