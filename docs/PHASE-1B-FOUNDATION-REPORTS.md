# Phase 1B Foundation Reports

```json
{
    "generated_at": "2026-06-06T08:29:43+00:00",
    "sub_service_architecture": {
        "tables": [
            "sub_services",
            "sub_service_seo",
            "sub_service_schema",
            "sub_service_faqs"
        ],
        "parent_relationship": "sub_services.service_id \u2192 services.id",
        "standalone_promotion": "sub_services.standalone_service_id \u2192 services.id",
        "schema_integration": "UnifiedJsonLdGraphBuilder hasPart nodes"
    },
    "seo_ownership": {
        "operations_canonical": true,
        "mirror_to_growth_layer": false,
        "canonical_service_source": "service_seo",
        "generated_schema_source": "unified_json_ld_graph_builder"
    },
    "matrix_architecture": {
        "pivot_table": "service_pincodes",
        "pivot_fields": [
            "priority",
            "is_visible",
            "is_featured",
            "coverage_notes",
            "category_filter_ids",
            "effective_from",
            "effective_until"
        ],
        "mapping_table": "service_location_pages",
        "reconcile_command": "medca:reconcile-service-location-matrix"
    },
    "geo_enrichment": {
        "pin_codes_total": 26,
        "with_landmarks": 0,
        "with_hospitals": 0,
        "with_nearby_areas": 0,
        "with_location_faqs": 0,
        "with_coverage_text": 0,
        "with_geo_location_fk": 0,
        "location_pages": 0,
        "indexable_location_pages": 0,
        "enrichment_tables": {
            "pin_code_landmarks": true,
            "pin_code_hospitals": true,
            "pin_code_nearby_areas": true,
            "pin_code_location_faqs": true
        },
        "geo_entity_builder": "App\\Services\\Seo\\UnifiedJsonLdGraphBuilder",
        "readiness_score": 0
    },
    "import_readiness": {
        "categories": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "services": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "sub_services": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "pincodes": {
            "importer": "App\\Services\\Import\\PinCodeEntityImporter",
            "status": "implemented",
            "formats": [
                "csv"
            ]
        },
        "locations": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "mappings": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "seo": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "geo": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "aeo": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "schema": {
            "importer": "",
            "status": "planned",
            "formats": [
                "json",
                "csv"
            ]
        },
        "meta": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        },
        "faq": {
            "importer": "",
            "status": "planned",
            "formats": [
                "csv",
                "xlsx"
            ]
        }
    },
    "database_first": {
        "locality_resolver": "App\\Services\\Seo\\LocalityContextResolver",
        "hardcoded_locality_in_app_php": "removed_from_core_engines"
    }
}
```
