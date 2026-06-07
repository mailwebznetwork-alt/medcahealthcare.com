# MEDCA HEALTH CARE — Phase 2 Discovery Reports

**Generated:** 2026-06-06  
**Scope:** Page Generation + Discovery + SEO/GEO/AEO Expansion  
**Foundation:** Unchanged (DB-first, Field Registry, SEO Ownership, Visibility Governance, Universal Page Registry, Site Architect)

---

## 1. Category Page Generation Report

| Metric | Value |
|--------|-------|
| Active categories | 1 |
| Categories with CMS `page_id` | 1 |
| Provisioner | `CategoryPageProvisioner` |
| Orchestrator | `CategoryMasterOrchestrator` |
| Auto-sync | `ServiceCategoryObserver` + `medca:sync-category-pages` |
| Slug pattern | `category-{code}` (`config/phase2_discovery.php`) |

**Capabilities delivered:**
- SEO, GEO, AEO, FAQ, Schema via expansion engines
- Related categories, related services, featured services, top-rated services via `RelatedContentEngine`, `FeaturedContentEngine`, `TopRatedEngine`
- Internal linking snapshot persisted on `service_categories.internal_links_snapshot`
- CMS blocks: `category-discovery-hero`, `category-services-list`, `category-related`
- Public route via `ServiceCategoryPublicController` (CMS page when `page_id` exists)
- Auto-registration in Universal Page Registry (`entity_type: category`)

**Verify:** `php artisan medca:sync-category-pages` — 1 category synced  
**Test:** `Phase2PageGenerationTest` — category page generation passes

---

## 2. Service Page Generation Report

| Metric | Value |
|--------|-------|
| Provisioner | `ServiceDetailPageProvisioner` (Phase 1, extended) |
| Orchestrator | `ServiceMasterOrchestrator` |
| Registry upsert | Added post-sync `upsertServiceEntry()` |

**Capabilities delivered:**
- Description, benefits, eligibility, process, FAQ, images from DB
- SEO/GEO/AEO/Schema via existing service SEO tables + `UnifiedJsonLdGraphBuilder`
- Related services, related locations, sub-services via `RelatedContentEngine`
- Featured and top-rated via discovery engines
- CTA from service + page blocks
- Location pages via `ServiceLocationPageProvisioner`

**Verify:** Existing service sync pipeline unchanged; registry now auto-updates on orchestrator sync  
**Test:** Phase 1B/1C service tests remain green

---

## 3. Sub Service Page Generation Report

| Metric | Value |
|--------|-------|
| Sub-services in DB | 0 (staging) |
| With CMS `page_id` | 0 |
| Provisioner | `SubServicePageProvisioner` |
| Orchestrator | `SubServiceMasterOrchestrator` |
| Auto-sync | `SubServiceObserver` + `medca:sync-sub-service-pages` |
| Public route | `GET /services/{code}/sub/{subCode}` |

**Capabilities delivered:**
- Parent service linkage, related services, SEO/GEO/AEO/Schema
- FAQ sync from `sub_service_faqs`
- Internal linking snapshot on `sub_services.internal_links_snapshot`
- CMS blocks: `sub-service-detail-hero`, `sub-service-related`
- Independent ranking readiness (`page_id`, dedicated slug, registry entry)
- JSON-LD via `SubServiceJsonLdBuilder`

**Verify:** `php artisan medca:sync-sub-service-pages` — ready (0 rows until data imported)  
**Test:** `Phase2PageGenerationTest` — sub-service page + registry passes (factory data)

---

## 4. Location Page Generation Report

| Component | Status |
|-----------|--------|
| Provisioner | `ServiceLocationPageProvisioner` |
| Data sources | Location, pincode, `service_pincodes` matrix, landmarks, hospitals |
| SEO/GEO/AEO | Dynamic via expansion engines on location pages |
| Schema | `UnifiedJsonLdGraphBuilder` + location entities |

**Capabilities delivered:**
- Dynamic meta, canonical, JSON-LD from DB (no hardcoded locality copy)
- Coverage data from matrix pivot (`priority`, `is_visible`, `coverage_notes`)
- Nearby areas, landmarks, hospitals from geo tables
- Location FAQ from page/service FAQ tables
- Registry entries via `UniversalPageRegistry::upsertLocationEntry()`

**Verify:** Location pages remain Site Architect editable (`page_source: generated`, not locked)  
**Test:** `LocationPageQualityScorer` + matrix tests from Phase 1B pass

---

## 5. Discovery Engine Report

| Engine | Class |
|--------|-------|
| Core | `HealthcareDiscoveryEngine` |
| Category → Service | `discoverServices($categoryId, $pincode)` |
| Service → Sub Service | `discoverSubServices($serviceId)` |
| Location → Service | `discoverLocations($serviceId, $pincode)` |
| Service → Location | `discoverLocations($serviceId)` |
| Pincode filter | `discoverForPincode()`, `discoverCategories($pincode)` |

**Verify:** Discovery returns only DB-linked, visibility-governed entities  
**Test:** `Phase2PageGenerationTest` — hierarchy discovery passes

---

## 6. Category Display Engine Report

| Surface | Engine method |
|---------|---------------|
| Homepage | `CategoryDisplayEngine::forSurface('homepage')` |
| About / Contact / Location / Landing | `forSurface($surface, $context)` |
| Hierarchy | Category → Service → Sub Service (never random) |

**Integration:** `PublicPagePresenter` uses display engine on homepage variables  
**Config limits:** `phase2_discovery.display.*` (category, service, sub-service limits)

**Verify:** Selected categories/services follow pivot attachments, not random queries  
**Test:** Featured + discovery tests confirm DB-driven selection

---

## 7. Featured Service Report

| Engine | `FeaturedContentEngine` |
|--------|-------------------------|
| Featured categories | `featuredCategories($surface)` |
| Featured services | `featuredServices($surface)` |
| Featured sub-services | `featuredSubServices($surface)` |
| Surfaces | homepage, about, contact, location, landing |

**Data flags:** `is_featured`, `show_on_homepage`, surface-specific visibility  
**Verify:** Only flagged DB records surface  
**Test:** `Phase2PageGenerationTest` — featured services from DB flags

---

## 8. Top Rated Engine Report

| Engine | `TopRatedEngine` |
|--------|------------------|
| Top rated services | `topRatedServices($categoryId, $pincode, $limit)` |
| Top rated sub-services | `topRatedSubServices($serviceId, $limit)` |
| Thresholds | `min_reviews: 3`, `min_rating: 4.5` (config) |

**Data source:** `avg_rating_cache`, `is_top_rated`, review aggregates  
**Verify:** Empty when thresholds not met (no fake ratings)

---

## 9. Related Content Report

| Engine | `RelatedContentEngine` |
|--------|------------------------|
| Related categories | `buildForCategory()` |
| Related services | Category + service pivots |
| Related sub-services | Parent service scope |
| Related locations / pincodes | Matrix + location pages |

**Output:** `internal_links_snapshot` JSON on category/sub-service records  
**Verify:** Links generated from DB relationships only  
**Test:** `Phase2PageGenerationTest` — related content links pass

---

## 10. Change Pincode Report

| Component | `ChangePincodeEngine` |
|-----------|----------------------|
| Current pincode | `current()` |
| Switch | `switch($pincode)` — session + discovery refresh |
| Search | `searchServiceable($query)` |
| Integration | `LocationController` JSON responses |

**Session:** `UserLocationService` + `EnsurePincodeDetected` middleware  
**Refresh payload:** categories, services, locations for new pincode  
**Verify:** Rejects non-serviceable pincodes with DB-driven hint  
**Test:** `Phase2PageGenerationTest` — pincode switch passes

---

## 11. SEO Expansion Report

| Engine | `SeoExpansionEngine` |
|--------|----------------------|
| Outputs | meta title, description, canonical, slug, robots, OG, Twitter, keywords, breadcrumbs |
| Ownership | Respects `SeoOwnershipGuard` — no Growth mirror overwrite |
| Applied on | Category, sub-service, service provisioners |

**Verify:** No manual duplication; SEO tables are source of truth  
**Test:** SEO ownership tests from Phase 1B pass

---

## 12. GEO Expansion Report

| Engine | `GeoExpansionEngine` |
|--------|----------------------|
| Outputs | coverage data, landmarks, hospitals, nearby areas, geo entities, relationships |
| Data | `BusinessProfile`, pincode, locality, matrix pivot |
| Applied on | All page provisioners + location pages |

**Verify:** `LocalityContextResolver` — no hardcoded Bangalore/Arekere in `app/`  
**Test:** `GeoEntityGenerationTest` passes

---

## 13. AEO Expansion Report

| Engine | `AeoExpansionEngine` |
|--------|----------------------|
| Outputs | questions, answers, FAQ blocks, answer patterns, entity relationships |
| Tables | `*_faqs`, `*_seo` AEO fields |
| AI readability | Structured Q&A synced to page FAQs |

**Verify:** FAQ blocks populated from DB FAQ tables, not placeholders

---

## 14. Schema Report

| Engine | `SchemaExpansionEngine` |
|--------|---------------------------|
| Builders | `CategoryJsonLdBuilder`, `SubServiceJsonLdBuilder`, `UnifiedJsonLdGraphBuilder` |
| Types | Organization, LocalBusiness, Service, FAQ, Place, WebPage, Breadcrumb, SearchAction, CollectionPage, ItemList |
| Sync | `schema_json` on pages + `*_schema` tables |

**Verify:** JSON-LD `@graph` present on generated category pages  
**Test:** Category page test asserts `schema_json` has `@graph`

---

## 15. AI Discoverability Report

| Engine | `AiDiscoverabilityEngine` |
|--------|----------------------------|
| Targets | ChatGPT, Gemini, Copilot, Perplexity, Google AI Overviews |
| Signals | entity relationships, structured data, answer readiness, knowledge graph hints |
| Score | Computed on sync (category/sub-service SEO enrichment) |

**Verify:** Entity tags and discovery score fields populated from DB entities

---

## 16. Universal Page Registry Report

| Command | `medca:sync-page-registry` |
|---------|----------------------------|
| Registry rows | 8 |
| Manual | 6 |
| Generated | 1 |
| Planned | 0 |

**Entity types registered:** category, service, sub_service, location, landing, future  
**Category source:** `generated` when `page_id` present  
**Verify:** Every provisioned page appears in `page_registry`  
**Test:** Sub-service registry entry test passes

---

## 17. Site Architect Report

| Validator | `SiteArchitectCompatibilityValidator` |
|-----------|---------------------------------------|
| Compatible | Yes |
| Issues | 0 |
| Pages checked | 6+ |

**Guarantees:**
- Generated pages use `page_source: generated` (not locked)
- Blocks, templates, overrides, navigation, revision history remain editable
- Manual enhancement supported on all generated pages

**Verify:** `medca:phase2-report` site_architect check passes

---

## 18. Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Empty sub-service dataset on staging | Medium | Run XLS import (Phase 1C plan) then `medca:sync-sub-service-pages` |
| Bulk category/service sync on large catalogs | Low | Use `--code` / `--service` flags; observers auto-sync on save |
| Duplicate JSON-LD on legacy Blade fallbacks | Low | CMS pages preferred; legacy fallback only when no `page_id` |
| Top-rated empty until reviews imported | Expected | Engine returns empty — no fake ratings |
| Pincode null on first visit | Expected | `EnsurePincodeDetected` + Change Pincode UX handles refresh |

---

## 19. Recommendations

1. **Import production catalog** — categories, services, sub-services, pincodes via `ImportPipeline` before go-live.
2. **Bulk sync** — `medca:sync-category-pages && medca:sync-sub-service-pages && medca:sync-page-registry` after import.
3. **Review generated pages in Site Architect** — add manual blocks/overrides where marketing wants enhancement.
4. **Enable pincode on homepage** — verify Change Pincode component returns discovery payload in production.
5. **Monitor registry drift** — schedule `medca:sync-page-registry` after bulk operations.
6. **Phase 3 prep** — landing page templates per category vertical, analytics on discovery funnels.

---

## 20. Phase 3 Readiness Assessment

| Criterion | Status |
|-----------|--------|
| Category → Service → Sub Service → Location → Pincode hierarchy | Ready |
| All pages DB-driven | Ready |
| Discovery engines wired | Ready |
| SEO/GEO/AEO/Schema expansion | Ready |
| Universal Page Registry | Ready |
| Site Architect compatibility | Ready |
| Foundation tests (Phase 1B/1C) | 15/15 pass |
| Phase 2 tests | 6/6 pass |
| Production data volume | Pending import |

**Verdict:** Phase 2 implementation is **complete**. Await approval before Phase 3.

---

## Commands Reference

```bash
php artisan migrate
php artisan medca:sync-category-pages
php artisan medca:sync-sub-service-pages
php artisan medca:sync-page-registry
php artisan medca:reconcile-service-location-matrix
php artisan medca:phase2-report
./vendor/bin/pest --filter="Phase2PageGeneration"
```

## Runtime Snapshot (JSON)

```json
{
    "generated_at": "2026-06-06T08:45:00+00:00",
    "category_pages": { "categories": 1, "with_page_id": 1 },
    "sub_service_pages": { "sub_services": 0, "with_page_id": 0 },
    "page_registry": { "synced": 8, "manual": 6, "generated": 1, "planned": 0 },
    "site_architect": { "compatible": true, "issues": [], "checked": 6 },
    "database_first": { "compliant": true, "violations": [] },
    "phase2_complete": true
}
```
