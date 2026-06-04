# Complete Platform Sitemap

**MarkOnMinds / Medca — Admin & Public**  
**Updated:** 2026-05-30

## Public site

| Path | Purpose |
|------|---------|
| `/` | Home CMS page |
| `/p/{slug}` | CMS pages |
| `/services`, `/services/{slug}` | Service catalog |
| `/blog/{slug}` | Blog posts |
| `/contact`, `/about-us`, `/locations` | Marketing pages |
| `/careers`, `/careers/{slug}` | Job listings |
| `/location/{slug}` | Location pages |
| `/robots.txt`, `/sitemap.xml` | SEO |
| `/llm.txt`, `/ai-discovery` | AEO discovery |

## Authenticated workspace

### Top level

| Path | Module |
|------|--------|
| `/dashboard` | Dashboard |
| `/site-architect/*` | Site Architect |
| `/operations/*` | Operations |
| `/marketing`, `/marketing/intelligence` | Marketing |
| `/growth-center/*` | Growth Center |
| `/user-management/*` | User Management |
| `/security` | Security |
| `/system/*` | System (settings grant) |
| `/settings/*` | Settings |
| `/workspace/search` | Global search |
| `/profile` | Account |

### Site Architect (`/site-architect`)

| Path | Feature |
|------|---------|
| `/pages` | Pages workspace |
| `/blogs` | Blogs |
| `/navigation` | Header/footer menus |
| `/media` | Media library |
| `/section-library`, `/sections` (alias) | Section library |
| `/block-presets`, `/presets` (alias) | Block presets |
| `/block-studio` | Block studio |
| `/block-factory` | Block factory |
| `/blueprint-builder` | Blueprint builder |
| `/deployment-packages` | Deployment packages |
| `/modules` | Module builder |

### Operations (`/operations`)

| Path | Feature |
|------|---------|
| `/job-portal/overview` | Job portal hub |
| `/job-portal/vacancies/*` | Vacancies CRUD |
| `/job-portal/applications/*` | Applications |
| `/services/*` | Services |
| `/bookings/*` | Bookings / leads |
| `/pin-codes/*` | PIN codes |

### Marketing

| Path | Feature |
|------|---------|
| `/marketing` | Campaign dashboard |
| `/marketing/intelligence` | Intelligence platform |
| `/marketing/reports/leads/export` | Lead export |

### Growth Center

| Path | Feature |
|------|---------|
| `/growth-center/competitors` | Hub (tabs) |
| `/growth-center/readiness` | → tab redirect |
| `/growth-center/seo/*` | SEO entity/technical |
| `/growth-center/aeo` | AEO |
| `/growth-center/geo/*` | GEO |
| `/growth-center/war-room`, `/war-room/dashboard` | War room |
| API `/api/growth/*` | Competitor API |

### System

| Path | Feature |
|------|---------|
| `/system/overview` | Platform overview |
| `/system/queue` | Queue status |
| `/system/scheduler` | Scheduled tasks |
| `/system/health` | Health signals |
| `/settings/integrations` | Integrations UI |
| `/settings/webhooks` | Webhooks |

### Settings

| Path | Feature |
|------|---------|
| `/settings/appearance` | Theme & branding |
| `/settings/global-content` | Global content |
| `/settings/backup` | Backup (operator) |
| `/settings/maintenance` | Maintenance mode |

### User Management

| Path | Feature |
|------|---------|
| `/user-management` | User list |
| `/user-management/create` | Create user |
| `/user-management/{user}/edit` | Edit user |

### Legacy admin API

| Path | Feature |
|------|---------|
| `/admin/settings/integrations/*` | JSON integrations API |
