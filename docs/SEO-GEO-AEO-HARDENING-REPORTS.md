# SEO / GEO / AEO Hardening Reports

Generated: 2026-06-08T16:04:29+00:00

## 1. Schema Cleanup Report

```json
{
    "unified_graph_builder": "active",
    "sample_service_graph_nodes": [
        4,
        4,
        5,
        5,
        5
    ],
    "duplicate_suppression": "site-seo-meta suppresses org/faq/service duplicates on service+location pages",
    "page_json_ld": "single @graph script per page"
}
```

## 2. Internal Link Integrity Report

```json
{
    "services_with_snapshot": 8,
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
    "sample_count": 10,
    "unique_intro_hashes": 8,
    "samples": [
        {
            "url": "https://medcahealthcare.com/services/icu-care-at-home/singasandra",
            "intro_hash": "8d50a6574f531f6bfdaabf2f85b42330",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/physiotherapy-at-home/koramangala",
            "intro_hash": "805af3132982a0bf95d5da4e11b018ec",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/icu-care-at-home/arekere",
            "intro_hash": "7587bb9e5ed0fffeb03ebc777db41c16",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/caregivers/bannerghatta",
            "intro_hash": "b41af34d065c42152ca461f5bf6de116",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/elder-care/hulimavu",
            "intro_hash": "49672d743ad3dea205c194fba67dd70e",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/medical-lab/btm-layout",
            "intro_hash": "54678f3b25720f380cbb886dbc6907b0",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/caregivers/singasandra",
            "intro_hash": "8d50a6574f531f6bfdaabf2f85b42330",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/medical-lab/koramangala",
            "intro_hash": "805af3132982a0bf95d5da4e11b018ec",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/homenursing-services/jayanagar-south",
            "intro_hash": "16e5afa8da516c4891f8c8329b276e2e",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        },
        {
            "url": "https://medcahealthcare.com/services/medical-lab/electronic-city",
            "intro_hash": "3b77b45ca14a9c610eea652b5679d634",
            "faq_count": 2,
            "composite": 78,
            "is_indexable": true
        }
    ]
}
```

## 4. Pincode Expansion Report

```json
{
    "active_primary_city_pincodes": 19,
    "location_pages": 84,
    "indexable_location_pages": 84,
    "published_services": 8
}
```

## 5. Sitemap Validation Report

```json
{
    "services_sitemap_bytes": 9745,
    "includes_about_us": true,
    "includes_services_catalog": true
}
```

## 6. Duplicate Content Risk Report

```json
{
    "duplicate_page_slugs": [],
    "location_cms_pages": 89
}
```

## 7. GEO Readiness Report

```json
{
    "pincodes_with_coverage_text": 14,
    "pincodes_with_landmarks": 14,
    "pincodes_geo_page_ready": 14,
    "avg_location_geo_score": 100
}
```

## 8. AEO Readiness Report

```json
{
    "pincode_location_faqs": 28,
    "avg_location_aeo_score": 35,
    "noindex_location_pages": 0
}
```
