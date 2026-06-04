# Phase 6 — Performance Review

**Status:** Informational only (no optimizations implemented)  
**Date:** 2026-05-30

## Methodology

Static review of controllers, Livewire components, and dashboard query patterns. No APM profiling run in this phase.

## Slow page candidates

| Page | Concern | Severity |
|------|---------|----------|
| Dashboard | Multiple sequential `count()` queries when UM + Operations widgets enabled | Medium |
| Growth competitor hub | Large tabbed surface; multiple Livewire islands (GA4, AI Pulse) | Medium |
| Site Architect pages list | Livewire pagination + preview generation | Medium |
| Marketing intelligence | Livewire dashboard with date-range aggregates | Low–Medium |
| Public CMS pages | `ContentParser` block resolution (mitigated by request-scoped cache) | Low |

## N+1 query observations

| Area | Finding |
|------|---------|
| Dashboard `recent_users` | Single query with limit 5 — OK |
| Dashboard metrics | 4–6 separate count queries — could be one aggregate |
| Workspace search | 8 entity groups × 1 query each (capped at 12) — acceptable |
| Growth competitor lists | Review Livewire components for `->with()` on relations during manual profile |

**Recommendation:** Enable `DB::listen` or Telescope in staging for Growth + Pages list profiling.

## Heavy Livewire components

| Component | View shell |
|-----------|------------|
| `marketing.intelligence-dashboard` | `intelligence-shell` |
| `marketing.dashboard` | `dashboard-shell` |
| `site-architect.pages` | `pages-shell` |
| `site-architect.block-factory` | `block-factory-shell` |
| `settings.appearance-settings` | `appearance.blade.php` |
| Growth GA4 / AI Pulse partials | Embedded in competitor hub |

## Duplicate query patterns

- Dashboard: separate `users_active` / `users_inactive` / `users_verified` counts — could use conditional aggregation.
- `ContentParser`: static block cache cleared at depth 0 — good; avoids duplicate block fetches within one render.

## Expensive dashboard loads

`DashboardController::buildMetrics()` runs only when corresponding module widget is enabled — good gating. Still runs up to 5 user queries when UM module on.

## Optimization opportunities (not implemented)

1. Single SQL aggregate for user metrics on dashboard.
2. Cache Growth readiness scores (TTL 5–15 min) if external APIs called.
3. Eager-load media relations on Site Architect page editor open.
4. Queue heavy blueprint generation for large blueprints (future).
5. Redis cache for `ThemeResolver` published config in production.

## Production config reminders

- `config:cache`, `route:cache`, `view:cache` in deploy pipeline
- Queue worker for webhooks and async jobs
- `schedule:run` cron for scheduler page accuracy
