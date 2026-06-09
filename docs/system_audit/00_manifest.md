# Phase 0 — System Manifest

**Audit started:** 2026-06-08  
**Governance mode:** Documentation only — no code, DB, or config changes  
**Evidence source:** Read-only inspection of `/var/www/medca_healthcare` on audit host  
**Status:** Phase 0 complete — **awaiting owner approval before Phase 1**

---

## 1. Project purpose

| Field | Value | Evidence |
|-------|--------|----------|
| **Product** | Medca Health Care — premium home healthcare platform (Bangalore / Arekere radius) | `config/medca.php`, `docs/platform-documentation/README.md` |
| **Public site** | Marketing + service catalog + location pages + lead capture + SEO/GEO/AEO | `routes/web.php`, `app/Http/Controllers/Public/` |
| **Admin platform** | Custom Laravel admin (not Filament): Operations, Site Architect, Marketing, Growth Center, System | `routes/web.php`, `app/Livewire/`, `docs/platform-documentation/module-inventory.md` |
| **Core principle** | Database-first source of truth for services, pincodes, generated pages, registry, schema | `app/Services/Operations/ServiceMasterOrchestrator.php`, `app/Services/Governance/UniversalPageRegistry.php` |
| **Legacy naming** | Internal composer/npm name still references **MarkOnMinds** | `composer.json` (`laravel/laravel`), `package.json` (`markonminds`), `config/app.php` `APP_NAME` default |

---

## 2. Framework & runtime versions

| Component | Declared constraint | Observed runtime | Evidence |
|-----------|---------------------|------------------|----------|
| **PHP** | `^8.3` | **8.4.22** (CLI) | `composer.json`, `php -v` |
| **Laravel** | `^13.0` | **13.7.0** | `composer.json`, `php artisan --version` |
| **Livewire** | `^4.3` | **v4.3.0** | `composer.json`, `php artisan about` |
| **Filament** | — | **NOT INSTALLED** | No `filament/*` in `composer.json`; no Filament classes in codebase |
| **Node build** | Vite 8, Tailwind 3 | UNKNOWN (not executed in audit) | `package.json`, `vite.config.js` |
| **Composer** | — | **2.7.1** | `php artisan about` |

---

## 3. Git state (audit host)

| Field | Value | Evidence |
|-------|--------|----------|
| **Branch** | `main` | `git branch --show-current` |
| **Commit (full)** | `1ecb085d75248b9a975453e59beed1b5b99b01f7` | `git rev-parse HEAD` |
| **Commit (short)** | `1ecb085` | `git log -1` |
| **Last commit message** | Hide public breadcrumb UI while preserving BreadcrumbList JSON-LD. | `git log -1 --format=%s` |
| **Last commit date** | 2026-06-08 21:58:44 +0530 | `git log -1 --format=%ci` |
| **Remote** | `git@github.com:mailwebznetwork-alt/medcahealthcare.com.git` | `git remote -v` |
| **Sync with remote** | Up to date at audit time (prior session) | `git status` |

---

## 4. Environment architecture

### 4.1 Application identity (runtime — audit host)

| Setting | Value | Evidence |
|---------|--------|----------|
| `APP_NAME` | MarkOnMinds | `php artisan about` |
| `APP_ENV` | production | `php artisan about` |
| `APP_DEBUG` | OFF | `php artisan about` |
| `APP_URL` | medcahealthcare.com | `php artisan about` |
| **Timezone** | UTC | `config/app.php`, `php artisan about` |
| **Locale** | en | `php artisan about` |
| **Maintenance** | OFF | `php artisan about` |

### 4.2 Intended vs actual drivers (important)

`.env.example` documents **enterprise defaults** (Redis cache/queue/session).  
**This audit host runtime** (from `php artisan about`) differs:

| Driver | `.env.example` default | **Runtime (audit host)** | Risk note |
|--------|----------------------|---------------------------|-----------|
| **Database** | sqlite (example) | **sqlite** (`database/database.sqlite`, ~13.5 MB) | Dev/single-server footprint |
| **Cache** | redis | **database** | Performance / stampede behavior differs |
| **Queue** | redis | **sync** | Jobs run inline; no async worker |
| **Session** | redis | **database** | Session table load |
| **Mail** | log | **log** | No real mail delivery |
| **Broadcast** | log | log | No realtime broadcast |

**UNKNOWN:** Production server `.env` on live host may use Redis/MySQL — not verified in this phase (no SSH/production read).

### 4.3 Public branding (config-driven)

| Key | Purpose | File |
|-----|---------|------|
| `MEDCA_BRAND_NAME` | Public display name | `config/medca.php` |
| `MEDCA_COMPANY_LEGAL_NAME` | Footer / compliance | `config/medca.php` |
| `medca.hide_visual_breadcrumbs` | Hide UI breadcrumbs; keep JSON-LD | `config/medca.php` (default `true`) |

---

## 5. Database architecture

| Field | Value | Evidence |
|-------|--------|----------|
| **Engine (audit host)** | SQLite 3 | `DB_CONNECTION=sqlite`, `database/database.sqlite` |
| **Supported engines** | sqlite, mysql, mariadb, pgsql | `config/database.php` |
| **Migrations** | 93 files | `database/migrations/` count |
| **Models** | 80 files | `app/Models/` count |
| **FK constraints** | Enabled for sqlite (`DB_FOREIGN_KEYS`) | `config/database.php` |

**Schema domains (high level — detail in Phase 2):** services/catalog, pincodes/geo, CMS pages/blocks, marketing/leads, governance/registry/tombstones, theme/settings, jobs/audit.

---

## 6. Storage architecture

| Disk | Root / purpose | Evidence |
|------|----------------|----------|
| **local** (default) | `storage/app/private` | `config/filesystems.php` |
| **public** | `storage/app/public` → `/storage` URL | `config/filesystems.php`, `public/storage` symlink LINKED |
| **s3** | Optional AWS (env-driven) | `config/filesystems.php` |
| **Imports** | `storage/imports/` | Referenced in go-live docs |
| **Backups / snapshots** | `storage/app/backups/`, `storage/app/integrity-snapshots/` | Prior integrity sessions |
| **Build assets** | `public/build/` via Vite | `vite.config.js` |

---

## 7. Cache architecture

| Layer | Driver (audit host) | Config file |
|-------|---------------------|-------------|
| **Application cache** | database | `config/cache.php`, `CACHE_STORE` |
| **Config cache** | NOT CACHED | `php artisan about` |
| **Route cache** | NOT CACHED | `php artisan about` |
| **View cache** | CACHED | `php artisan about` |
| **Event cache** | CACHED | `php artisan about` |
| **Tagged flush** | Used in governance purger (`pages`, `sitemap`, `registry`) | `app/Services/Governance/DownstreamArtifactPurger.php` |

**Redis:** Supported via `predis/predis`; intended in `.env.example` — **UNKNOWN** if enabled on production.

---

## 8. Queue architecture

| Field | Value | Evidence |
|-------|--------|----------|
| **Default connection (audit host)** | sync | `php artisan about` |
| **Available drivers** | sync, database, redis, sqs, … | `config/queue.php` |
| **Job classes** | 10 files | `app/Jobs/` count |
| **Dev script** | `composer dev` runs `queue:listen` | `composer.json` scripts |

**Implication on audit host:** Background jobs execute synchronously in web/CLI request — queue backlog behavior differs from Redis production.

---

## 9. Session & auth architecture

| Component | Audit host | Evidence |
|-----------|------------|----------|
| **Session driver** | database | `php artisan about` |
| **Auth** | Laravel Breeze-style + custom middleware | `config/auth.php`, `app/Http/Middleware/` |
| **API auth** | Sanctum | `laravel/sanctum` in `composer.json` |
| **Role gates** | `admin`, `role`, `module`, `active`, … | `bootstrap/app.php` middleware aliases |

---

## 10. Installed packages (direct requirements)

### 10.1 PHP — production (`composer.json` require)

| Package | Version constraint | Purpose (documented) |
|---------|-------------------|----------------------|
| `laravel/framework` | ^13.0 | Core framework |
| `livewire/livewire` | ^4.3 | Admin UI + interactive public components |
| `laravel/sanctum` | ^4.3 | API token auth |
| `laravel/tinker` | ^3.0 | REPL |
| `google/analytics-data` | ^0.23.3 | GA4 Data API (Growth/Marketing) |
| `intervention/image` | 3.11 | Image processing (media) |
| `openai-php/client` | ^0.19.2 | AI integrations |
| `phpoffice/phpspreadsheet` | ^5.7 | Import/export workbooks |
| `predis/predis` | ^3.4 | Redis client |

### 10.2 PHP — development (`require-dev`)

| Package | Purpose |
|---------|---------|
| `pestphp/pest` + `pest-plugin-laravel` | Test runner |
| `laravel/breeze` | Auth scaffolding |
| `laravel/pint` | Code style |
| `laravel/boost` | AI agent tooling |
| `fakerphp/faker` | Test data |

### 10.3 JavaScript (`package.json`)

| Package | Role |
|---------|------|
| `vite` + `laravel-vite-plugin` | Asset bundling |
| `tailwindcss` + `@tailwindcss/forms` | Public + admin CSS |
| `alpinejs` | Lightweight JS |
| `apexcharts` | Admin charts |
| `sortablejs` | Drag-and-drop (Site Architect) |
| `lucide` | Icons |

### 10.4 Custom packages

**None.** All application code is first-party under `app/`, `resources/`, `config/`.

---

## 11. Codebase scale (inventory preview — full list Phase 1)

| Artifact | Count | Location |
|----------|------:|----------|
| Eloquent models | 80 | `app/Models/` |
| Migrations | 93 | `database/migrations/` |
| HTTP controllers | 56 | `app/Http/Controllers/` |
| Service classes | 228 | `app/Services/` |
| Artisan commands | 36 | `app/Console/Commands/` |
| Livewire components | 35 | `app/Livewire/` |
| Observers | 10 | `app/Observers/` |
| Jobs | 10 | `app/Jobs/` |
| Config files | 57 | `config/` |

---

## 12. Application structure (logical modules)

Evidence: `routes/web.php`, `docs/platform-documentation/module-inventory.md`, sidebar configs.

| Module | Primary paths | Admin technology |
|--------|---------------|------------------|
| **Public marketing** | `/`, `/services/*`, `/service-categories/*`, `/locations/*` | Blade + Livewire (pincode modal, reviews) |
| **Operations** | `/operations/*` | Livewire lists + controllers |
| **Site Architect** | `/site-architect/*` | Livewire + block/page CMS |
| **Marketing** | `/marketing/*` | Livewire dashboards |
| **Growth Center** | `/growth-center/*` | Livewire + GA4/SEO tools |
| **System / Settings** | `/system/*`, `/settings/*` | Livewire + governance dashboards |
| **Careers / Job portal** | `/careers/*`, operations job portal | Controllers + Livewire |

**Filament:** Not used. Admin is custom Livewire + Blade workspace shells.

---

## 13. Deployment architecture

| Aspect | Documented / observed | Evidence |
|--------|---------------------|----------|
| **Web root** | `/var/www/medca_healthcare/public` | Standard Laravel layout |
| **Primary domain** | `https://medcahealthcare.com` | `.env.example`, domain migration docs |
| **HTTPS** | Configured (prior migration session) | Conversation history; **live cert state UNKNOWN** this phase |
| **Process model** | PHP-FPM + Nginx (typical) | **UNKNOWN** — not read from `/etc/nginx` in this phase |
| **Asset build** | `npm run build` → Vite → `public/build/` | `package.json`, `vite.config.js` |
| **Deploy docs** | Platform Bible, Deployment Engine | `docs/platform-documentation/` |
| **Health check** | `/up` | `bootstrap/app.php` |
| **CI/CD** | **UNKNOWN** | No `.github/workflows` verified in Phase 0 |

---

## 14. Existing documentation (pre-audit)

| Location | Role |
|----------|------|
| `docs/platform-documentation/` | Operator bible, module/route inventories (2026-05-30 baseline) |
| `docs/GO-LIVE-CERTIFICATION.md` | Auto-generated launch audit |
| `docs/PRODUCTION-LAUNCH-REPORT.md` | Launch metrics |
| `docs/SEO-GEO-AEO-HARDENING-REPORTS.md` | SEO/GEO/AEO reports |
| **`docs/system_audit/`** | **This governance audit (new permanent reference)** |

---

## 15. Governance rules (active for this audit)

| Rule | Summary |
|------|---------|
| **A** | No implementation unless owner says **PROCEED TO CODE** |
| **B** | Pre-change: outcome, files, dependencies, risks, rollback |
| **C** | One feature at a time |
| **D** | Stop after each phase; wait for approval |
| **E** | Documentation first, code second |
| **F–H** | Traceability, history, owner control |

---

## 16. Known uncertainties (Phase 0)

| Item | Status |
|------|--------|
| Production `.env` (Redis/MySQL vs SQLite) | **UNKNOWN** |
| Live server Nginx/PHP-FPM topology | **UNKNOWN** |
| Exact route count | **UNKNOWN** (route:list output truncated in audit shell) |
| Production queue worker processes | **UNKNOWN** |
| CI/CD pipeline | **UNKNOWN** |
| Filament | **Confirmed absent** |

---

## 17. Phase 0 completion statement

This manifest is a **factual baseline** from read-only inspection. It does not assert production parity with the audit host. Phases 1–20 will expand inventory, wiring, risks, and owner control using the same evidence rules.

**Next step (blocked until approval):** Phase 1 — `docs/system_audit/01_inventory.md`

---

*Generated under Project Governance Mode — Phase 0 only. No application files modified except this documentation.*
