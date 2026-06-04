# Complete Module Inventory

**Persisted keys:** 8 (`App\ModuleAccess`)  
**Supplemental nav:** 1 (`system` → maps to `settings` grant)

| Key | Label | Sidebar route | Middleware | Description |
|-----|-------|---------------|------------|-------------|
| `dashboard` | Dashboard | `dashboard` | `module:dashboard` | Executive overview, widgets |
| `site_architect` | Site Architect | `site-architect.pages.index` | `module:site_architect` | CMS, blocks, deployment |
| `operations` | Operations | `modules.operations` | `module:operations` | Job portal, services, bookings, PINs |
| `marketing` | Marketing | `modules.marketing` | `module:marketing` | Campaigns, intelligence, reports |
| `growth_center` | Growth Center | `modules.growth-center` | `module:growth_center` | SEO, competitors, GA4, AI Pulse |
| `user_management` | User Management | `user-management.index` | `module:user_management` | Users, roles, module access UI |
| `security` | Security | `modules.security` | `module:security` | Posture, audit, access events |
| `settings` | Settings | `settings.appearance` | `module:settings` | Theme, global content, backup |
| `system` *(nav only)* | System | `system.index` | Uses `settings` grant | Integrations, webhooks, ops views |

## Controllers & Livewire (by module)

### Dashboard
- `DashboardController`

### Site Architect
- Livewire: Pages, Blogs, Navigation, Media, BlockFactory, BlockPresets, BlockStudio, BlueprintBuilder, DeploymentPackages, ModuleBuilder
- Shell views under `resources/views/site-architect/`

### Operations
- `Operations/*` controllers (JobPortal, Services, Bookings, PinCodes)
- Livewire bookings

### Marketing
- `MarketingReportController`
- Livewire: `Marketing\Dashboard`, `Marketing\IntelligenceDashboard`

### Growth Center
- `Growth/*` controllers
- Competitor hub Livewire/partials

### User Management
- `UserManagement\UserController` (frozen)

### Security
- Module surface via `ModuleController` / `modules.security`

### System
- `System\SystemOverviewController`
- Settings integrations/webhooks (shared controllers)

### Settings
- `SettingsController`, `ThemePreviewController`
- Livewire: Appearance, GlobalContent, WebhookManager

## Integrations (cross-cutting)

- `App\Models\Integration` — OpenAI, Meta, GTM, etc.
- `config('gemini.api_key')` for Marketing Insights / AI features
- Webhook dispatcher for lead events
