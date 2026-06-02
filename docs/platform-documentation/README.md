# Platform Documentation

Post-restructure stabilization audit package (2026-05-30).

## Start here

**[PLATFORM-BIBLE-MASTER.md](./PLATFORM-BIBLE-MASTER.md)** — **Definitive operator bible & forensic autopsy** (20 sections: IA, features, roles, deployment, 70 elements, blueprints, marketing, growth, lead intelligence, integrations, security, infra, workflows, readiness, limitations, operator manual, scores).

**PDF:** [PLATFORM-BIBLE-MASTER.pdf](./PLATFORM-BIBLE-MASTER.pdf) — same content with table of contents (regenerate: `pandoc docs/platform-documentation/PLATFORM-BIBLE-MASTER.md -o docs/platform-documentation/PLATFORM-BIBLE-MASTER.pdf --pdf-engine=wkhtmltopdf --toc --toc-depth=2`).

**[00-executive-summary-production-readiness.md](./00-executive-summary-production-readiness.md)** — Earlier executive snapshot (score **82/100**); superseded for day-to-day ops by the Platform Bible above.

## Phase reports

| File | Topic |
|------|-------|
| [phase-1-route-validation.md](./phase-1-route-validation.md) | Routes, redirects, orphans |
| [phase-2-permission-validation.md](./phase-2-permission-validation.md) | Roles & modules |
| [phase-3-feature-preservation.md](./phase-3-feature-preservation.md) | Feature matrix |
| [phase-4-ui-consistency.md](./phase-4-ui-consistency.md) | UI patterns |
| [phase-5-deployment-engine-validation.md](./phase-5-deployment-engine-validation.md) | Deployment engine |
| [phase-6-performance-review.md](./phase-6-performance-review.md) | Performance findings |
| [phase-7-security-validation.md](./phase-7-security-validation.md) | Security |

## Reference documentation (Phase 8)

| File | Content |
|------|---------|
| [platform-sitemap.md](./platform-sitemap.md) | Public + admin paths |
| [module-inventory.md](./module-inventory.md) | Modules & components |
| [route-inventory.md](./route-inventory.md) | 141 admin route table |
| [module-permission-matrix.md](./module-permission-matrix.md) | Role × module |
| [feature-inventory.md](./feature-inventory.md) | Capability list |
| [sidebar-structure.md](./sidebar-structure.md) | Sidebar IA |
| [deployment-engine.md](./deployment-engine.md) | Blueprints & theme |
| [operations.md](./operations.md) | Operations module |
| [marketing.md](./marketing.md) | Marketing module |
| [growth-center.md](./growth-center.md) | Growth module |

## Related

- [../navigation-ia-restructure-audit.md](../navigation-ia-restructure-audit.md) — IA change log

## Regenerate route inventory

```bash
php artisan route:list --json > /tmp/routes.json
# Re-run project script or filter admin prefixes
```

## Test baseline

```bash
php artisan test
# Expected: 350 passed
```
