# Feature Inventory

**Total automated tests:** 350  
**Admin capability areas:** 9 sidebar modules + supplemental System

## Dashboard
- Module-aware widgets (users, vacancies, applications)
- Near-you public payload preview
- Quick links to granted modules

## Site Architect
- **Content:** Pages (CRUD, preview, SEO), Blogs, Navigation menus, Media library
- **Blocks:** Section library, Block presets, Block studio, Block factory
- **Deploy:** Blueprint builder, Deployment packages
- **Advanced:** Dynamic module builder
- **Deployment engine:** Style packs, block tokens, page generation (Phase 8.5)

## Operations
- Job portal (overview, vacancies, applications, resume download)
- Services (CRUD, preview, duplicate, detail pages)
- Bookings / leads (list, show)
- PIN codes (overview, directory, bulk import, CRUD)

## Marketing
- Campaign dashboard (Livewire)
- Intelligence platform (attribution, WhatsApp, calls, conversions, reporting tabs)
- Lead export report
- Integrations: GTM, Meta CAPI, etc. (via settings)

## Growth Center
- Competitor hub with tabs: Readiness, Competitors, War Room, Hijack, SEO, AEO, GEO, GA4, AI Pulse
- Dedicated routes: SEO entity/technical, GEO location/pincodes, War room dashboard/intercepts
- Competitor API (Sanctum)
- Gemini-powered insights where configured (`config('gemini.api_key')`)

## User Management (frozen)
- User directory, create, edit, activate/deactivate
- Role assignment (API/form gaps documented)
- Per-user module access checkboxes
- Root account protection

## Security
- Security overview metrics
- Firewall rules display
- Audit log preview
- Failed login / role denial / session timeout / upload rejection stats
- Anchor navigation between sections

## System
- Platform overview (app, DB, queue)
- Queue connection / failed jobs count
- Scheduler task list (read-only)
- Health page
- Integrations & webhooks (UI relocated from Settings nav)

## Settings
- Appearance (colors, typography, layout, theme presets, preview/publish)
- Global content (header/footer config, etc.)
- Backup download/restore (operator-gated)
- Maintenance mode (super_admin)

## Cross-cutting
- Workspace global search (⌘K)
- Profile & logout
- Activity logging
- Auto-logout middleware
- Email verification gate
