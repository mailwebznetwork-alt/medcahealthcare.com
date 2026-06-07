# Medca Master XLS Operations Guide

Operations manages the catalog primarily through two workbooks. All columns map into existing Phase 1–4 importers and database tables.

## Quick start

1. Generate blank templates:
   ```bash
   php artisan medca:export-import-templates
   ```
   Files: `storage/imports/templates/services.xlsx`, `pincodes.xlsx`

2. Upload at **Operations → Bulk Import** (Master workbook mode).

3. Preview per sheet → Approve & Commit → audit batches appear in the log.

4. CLI equivalent:
   ```bash
   php artisan medca:import services storage/imports/templates/services.xlsx --preview
   php artisan medca:import services path/to/services.xlsx
   php artisan medca:import pincodes path/to/pincodes.xlsx
   ```

## services.xlsx

### Categories sheet

Required: `code`, `name`

Optional: `slug`, `description`, `parent_code`, `sort_order`, `is_active`, `is_featured`, `visibility`, `show_on_homepage`, `show_on_about`, `show_on_contact`, SEO/AEO columns (`meta_title`, `meta_description`, `focus_keywords`, `secondary_keywords`, `canonical_url`, `robots_index`, `og_*`, `aeo_question`, `aeo_answer`, `faq_pairs`), `h1`, `breadcrumb_title`.

### Services sheet

Required: `service_code`, `title`

Key optional groups:

- **Catalog:** `primary_category_code`, `category_codes`, `short_summary`, `description`, `key_benefits`, `eligibility`, `process_steps`, `trust_signals`, `procedures`, `shifts`
- **SEO:** `meta_title`, `meta_description`, `focus_keywords`, `secondary_keywords`, `canonical_url`, `robots_index`, `og_*`, `twitter_*`, `breadcrumb_title`
- **Headings:** `h1`, `h2_lines`, `h3_lines`, `h4_lines`, `h5_lines`, `h6_lines` (pipe or newline separated)
- **AEO:** `faq_pairs`, `ai_summary`, `ai_recommendation_summary`, `target_keywords`, `ai_keywords`, `voice_search_queries`, `conversational_queries`, `entity_references`, `search_intent`, `ai_context`
- **Schema (optional override):** `schema_type`, `schema_json_override` — default generation remains active when omitted
- **Discovery:** `is_featured`, `is_top_rated`, `show_on_homepage`, `show_on_about`, `show_on_contact`, `show_on_category_pages`, `show_on_location_pages`, `display_priority`
- **Relationships:** `related_service_codes`, `related_category_codes`, `related_sub_service_codes`, `related_location_pincode`
- **Location templates:** `location_h1_template`, `location_h2_template`, `location_h3_template`, `location_intro_template`, `location_description_template`, `location_faq_template`, `location_cta_heading`, `location_cta_content`, `location_meta_title_template`, `location_meta_description_template`  
  Tokens: `{service}`, `{area}`, `{city}`, `{pincode}`, `{coverage}`
- **Media:** `featured_image_url`, `banner_image_url`, `icon_url`, `gallery_image_urls`, `video_url`, `image_alt`

Fields without dedicated DB columns are stored in `services.custom_fields` for governance and future provisioner use.

### SubServices sheet

Required: `parent_service_code`, `sub_service_code`, `title`

Optional: content, visibility, SEO, `faq_pairs`, `ai_summary`, `h1`, `h2_lines`, `h3_lines`, `schema_json_override`.

### ServiceDefaults sheet (optional)

Rows without `service_code` apply globally; rows with `service_code` apply per service. Values fill empty cells on the Services sheet only.

## pincodes.xlsx

### Pincodes sheet

Required: `pincode`, `area_name`, `city`

Optional: `state`, `locality`, `is_serviceable`, `is_active`, `priority`, `service_radius_km`, `coverage_type`, `meta_title`, `meta_description`, `seo_keywords`.

Workbook import **updates** existing pincodes (single-file import still skips duplicates).

### GeoEnrichment sheet

Required: `pincode`

Optional: `coverage_text`, `emergency_coverage_text`, `landmark_names`, `hospital_names`, `nearby_areas`, `faq_pairs`, `geo_entity_signals`, `local_intent_keywords`.

List columns use `|` separator. FAQ format: `Question|Answer;;Question2|Answer2`.

### Mappings sheet (optional)

Required: `service_code`, `pincode`

Optional: `priority`, `is_visible`, `is_featured`, `coverage_notes`, `category_filter_codes`, `effective_from`, `effective_until`.

## Import order

**services.xlsx:** Categories → Services → SubServices → ServiceDefaults (load only)

**pincodes.xlsx:** Pincodes → GeoEnrichment → Mappings

## Data formats

| Type | Format |
|------|--------|
| Boolean | `true`, `false`, `1`, `0`, `yes`, `no` |
| Lists | Comma or pipe separated |
| Line arrays | Pipe, newline, or JSON array |
| FAQ pairs | `Q|A;;Q2|A2` |
| JSON override | Valid JSON string |

## What stays automatic

After commit, existing systems handle:

- Category → Service → Sub Service → Location → Pincode chain
- SEO / GEO / AEO scoring and page sync
- Schema generation (`ServiceSchemaGenerator`) unless `schema_json_override` is set
- Discovery and internal linking (`RelatedContentEngine`)
- Universal Page Registry
- Location page provisioning (`ServiceLocationPageProvisioner`)

No Site Architect or discovery architecture changes are required for workbook operations.
