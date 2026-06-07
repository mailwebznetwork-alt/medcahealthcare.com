# Phase 1C Foundation Reports

```json
{
    "generated_at": "2026-06-06T08:35:54+00:00",
    "category_seo_geo_aeo": {
        "categories_total": 1,
        "with_seo": 0,
        "with_schema": 0,
        "with_faqs": 0,
        "discoverable_public": 1
    },
    "visibility_governance": {
        "service": "VisibilityGovernanceService",
        "flags": [
            "featured",
            "top_rated",
            "show_on_homepage",
            "show_on_about",
            "show_on_contact"
        ]
    },
    "universal_page_registry": {
        "sync_counts": {
            "synced": 7,
            "manual": 6,
            "generated": 0,
            "planned": 1
        },
        "registry_rows": 7
    },
    "site_architect_compatibility": {
        "compatible": true,
        "issues": [],
        "checked": 6
    },
    "import_architecture": {
        "registered_importers": [
            "pincodes"
        ],
        "entity_matrix": {
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
            },
            "reviews": {
                "importer": "",
                "status": "planned",
                "formats": [
                    "csv",
                    "xlsx"
                ]
            },
            "category_assignments": {
                "importer": "",
                "status": "planned",
                "formats": [
                    "csv",
                    "xlsx"
                ]
            },
            "visibility": {
                "importer": "",
                "status": "planned",
                "formats": [
                    "csv",
                    "xlsx"
                ]
            },
            "featured_flags": {
                "importer": "",
                "status": "planned",
                "formats": [
                    "csv",
                    "xlsx"
                ]
            },
            "top_rated_flags": {
                "importer": "",
                "status": "planned",
                "formats": [
                    "csv",
                    "xlsx"
                ]
            }
        },
        "entity_plans": {
            "categories": {
                "required_columns": [
                    "code",
                    "name"
                ],
                "optional_columns": [
                    "slug",
                    "description",
                    "parent_code",
                    "is_featured",
                    "meta_title",
                    "meta_description",
                    "focus_keywords"
                ],
                "validation": [
                    "unique_code",
                    "valid_parent_chain"
                ]
            },
            "services": {
                "required_columns": [
                    "service_code",
                    "title"
                ],
                "optional_columns": [
                    "description",
                    "category_codes",
                    "is_featured",
                    "is_top_rated",
                    "show_on_homepage",
                    "meta_title"
                ],
                "validation": [
                    "unique_service_code",
                    "category_codes_exist"
                ]
            },
            "sub_services": {
                "required_columns": [
                    "parent_service_code",
                    "sub_service_code",
                    "title"
                ],
                "optional_columns": [
                    "description",
                    "is_featured",
                    "sort_order"
                ],
                "validation": [
                    "parent_exists",
                    "unique_sub_code_per_parent"
                ]
            },
            "mappings": {
                "required_columns": [
                    "service_code",
                    "pincode"
                ],
                "optional_columns": [
                    "priority",
                    "is_visible",
                    "is_featured",
                    "coverage_notes",
                    "category_filter_codes"
                ],
                "validation": [
                    "service_exists",
                    "pincode_exists"
                ]
            },
            "visibility": {
                "required_columns": [
                    "entity_type",
                    "entity_key"
                ],
                "optional_columns": [
                    "show_on_homepage",
                    "show_on_about",
                    "show_on_contact",
                    "is_featured",
                    "is_top_rated"
                ],
                "validation": [
                    "entity_resolvable"
                ]
            }
        },
        "workflow": {
            "stages": [
                "upload",
                "validate",
                "preview",
                "approve",
                "commit",
                "audit"
            ],
            "rollback": {
                "strategy": "transaction_per_batch",
                "import_log_table": "import_batches",
                "revert_by_batch_id": true
            }
        }
    },
    "catalog_hierarchy": {
        "conflicts": []
    },
    "page_ownership": "docs/PAGE-OWNERSHIP.md",
    "database_first_compliance": {
        "compliant": true,
        "violations": []
    },
    "foundation_complete": true
}
```
