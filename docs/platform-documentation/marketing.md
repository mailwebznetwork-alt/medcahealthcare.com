# Marketing Documentation

**Module key:** `marketing`  
**Sidebar entry:** `modules.marketing`

## Primary navigation tabs

| Tab | Route / target | Feature |
|-----|----------------|---------|
| Dashboard | `modules.marketing` | Campaign dashboard (Livewire) |
| Intelligence | `modules.marketing.intelligence` | Full intelligence workspace |
| Campaigns | `modules.marketing#marketing-campaigns` | In-page anchor |
| Attribution | `modules.marketing.intelligence?tab=attribution` | Intelligence sub-tab |
| Reports | `modules.marketing.reports.leads.export` | CSV/export (manager+ role) |

## Intelligence platform

Livewire component: `marketing.intelligence-dashboard`

Tabs (in-component): Executive, WhatsApp, Calls, Attribution, Conversions, Reporting

Shell: `resources/views/marketing/intelligence-shell.blade.php` (uses `x-admin.workspace` + primary tabs)

## Integrations

Marketing tracking depends on integrations configured under **System → Integrations**:

- Google Tag Manager
- Meta Conversions API
- OpenAI / Gemini (insights — use `config('gemini.api_key')`)

## Access

- **Module:** `marketing`
- **Roles:** manager, admin, super_admin (routes)
- Lead export: additional role check in `MarketingReportController`

## Tests

`MarketingIntelligenceDashboardTest`, `MarketingAttributionTest`, `MarketingSecurityTest`, `LeadPipelineTest`, `PublicMarketingShellTest`

## Security

- Module middleware on all `/marketing/*` routes
- Export endpoint restricted to manager+
