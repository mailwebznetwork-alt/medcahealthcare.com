# SEO / GEO / AEO Hardening Reports

Generated: 2026-06-05T14:10:30+00:00

## 1. Schema Cleanup Report

```json
{
    "unified_graph_builder": "active",
    "sample_service_graph_nodes": [],
    "duplicate_suppression": "site-seo-meta suppresses org/faq/service duplicates on service+location pages",
    "page_json_ld": "single @graph script per page"
}
```

## 2. Internal Link Integrity Report

```json
{
    "services_with_snapshot": 0,
    "services_missing_snapshot": 0,
    "queue_jobs": [
        "RefreshServiceInternalLinksJob",
        "RefreshPeerServiceInternalLinksJob"
    ],
    "observers": [
        "Service",
        "PinCode",
        "ServiceLocationPage"
    ]
}
```

## 3. Location Content Uniqueness Report

```json
{
    "sample_count": 0,
    "unique_intro_hashes": 0,
    "samples": []
}
```

## 4. Pincode Expansion Report

```json
{
    "active_bangalore_pincodes": 26,
    "location_pages": 0,
    "indexable_location_pages": 0,
    "published_services": 0
}
```

## 5. Sitemap Validation Report

```json
{
    "services_sitemap_bytes": 110,
    "includes_about_us": true,
    "includes_services_catalog": true
}
```

## 6. Duplicate Content Risk Report

```json
{
    "duplicate_page_slugs": [],
    "location_cms_pages": 0
}
```

## 7. GEO Readiness Report

```json
{
    "pincodes_with_coverage_text": 0,
    "pincodes_with_landmarks": 0,
    "pincodes_geo_page_ready": 0,
    "avg_location_geo_score": 0
}
```

## 8. AEO Readiness Report

```json
{
    "pincode_location_faqs": 0,
    "avg_location_aeo_score": 0,
    "noindex_location_pages": 0
}
```
