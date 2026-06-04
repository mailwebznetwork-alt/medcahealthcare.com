# Growth Center Documentation

**Module key:** `growth_center`  
**Sidebar entry:** `modules.growth-center` → `growth-center.competitors.index`

## Primary navigation tabs

| Tab | Navigation | Notes |
|-----|------------|-------|
| Readiness | `?tab=readiness` on competitor hub | Legacy route `growth-center.readiness` redirects here |
| Competitors | `?tab=competitors` | Core competitor CRUD |
| War Room | `?tab=war-room` | Alias `/growth-center/war-room` → dashboard |
| Hijack Ops | `?tab=hijack-opportunities` | |
| SEO | `?tab=seo` | Deep links to entity/technical routes |
| AEO | `growth-center.aeo.index` | May redirect into SEO tab context |
| GEO | `growth-center.geo.location` | Pincodes sub-route available |
| GA4 | `?tab=ga4` | Livewire partial |
| AI Pulse | `?tab=ai-pulse` | Livewire partial |

## Deep links (legacy URLs preserved)

Displayed in `growth-center/partials/primary-tabs.blade.php`:

- `growth-center.seo.entity`
- `growth-center.seo.technical`
- `growth-center.geo.pincodes`
- `growth-center.war-room.dashboard`
- `growth-center.war-room.intercepts`

## War Room

- `/growth-center/war-room` → 301 → `/growth-center/war-room/dashboard`
- Intercept creation routes under `growth-center.war-room.*`
- API tests: `CompetitorWarRoomApiTest`, `WarRoomRollupFeatureTest`

## API

- Sanctum-authenticated growth competitor endpoints
- `module:growth_center` middleware
- `CompetitorPolicy` for mutations (editor+)

## AI / Gemini

Marketing Insights and AI Pulse features should read **`config('gemini.api_key')`** for Gemini integrations.

## Access

- **Read routes:** all roles with growth module grant
- **Mutating routes:** editor, manager, admin, super_admin

## Tests

`GrowthCenterCompetitorPageTest`, `GrowthEcosystemTest`, `GrowthReadinessHubTest`, `GrowthCenterGa4TabTest`, `GrowthCenterAiPulseTabTest`, `GrowthCenterGlobalDiscoveryRoutesTest`, `HijackOpportunityTest`

## Discoverability improvement (post-restructure)

Previously orphaned tab-only routes are now visible via primary tabs and deep-link row without removing canonical URLs.
