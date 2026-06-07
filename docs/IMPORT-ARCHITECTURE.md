# Medca Import Architecture

Phase 1–4 import stack — database-first catalog operations. No parallel engines.

## Primary operations files

| Workbook | Sheets | Entity importers |
|----------|--------|------------------|
| `services.xlsx` | Categories, Services, SubServices, ServiceDefaults (optional) | `CategoryEntityImporter`, `ServiceEntityImporter`, `SubServiceEntityImporter` |
| `pincodes.xlsx` | Pincodes, GeoEnrichment, Mappings (optional) | `PinCodeEntityImporter`, `GeoEnrichmentEntityImporter`, `MappingEntityImporter` |

Legacy single-file CSV imports remain supported.

## Pipeline flow

```
Upload → SpreadsheetReader → EntityImporter (preview)
       → Approve → ImportBatchRecorder → DB commit
       → ImportPostSyncService → orchestrators / registry
```

**Workbook path:** `WorkbookImportOrchestrator` reads each sheet, dispatches to the same importers, runs post-sync once per touched entity.

## Core classes

| Class | Role |
|-------|------|
| `ImportPipeline` | Preview / commit / post-sync for entity or parsed sheet data |
| `WorkbookImportOrchestrator` | Multi-sheet workbook dispatch |
| `ImportRegistry` | Entity → importer binding |
| `SpreadsheetReader` | CSV, XLS, XLSX (+ per-sheet read) |
| `AbstractSpreadsheetImporter` | Validate headers, preview, import, audit rows |
| `ImportBatchRecorder` | Audit trail + rollback snapshots |
| `ImportPostSyncService` | Artisan sync after commit |
| `ServiceImportDefaults` | ServiceDefaults sheet fallbacks (in-memory per commit) |

## Post-import sync (unchanged)

| Entity | Commands |
|--------|----------|
| categories | `medca:sync-category-pages`, `medca:sync-page-registry` |
| services | `medca:sync-page-registry` |
| sub_services | `medca:sync-sub-service-pages`, `medca:sync-page-registry` |
| pincodes / geo / mappings | `medca:reconcile-service-location-matrix`, `medca:sync-page-registry` |

Downstream orchestrators (`CategoryMasterOrchestrator`, `ServiceMasterOrchestrator`, `SubServiceMasterOrchestrator`, `ServiceLocationPageProvisioner`, `RelatedContentEngine`, `UniversalPageRegistry`) run via existing sync commands — not replaced.

## Entry points

| Surface | Path / command |
|---------|----------------|
| Operations UI | `/operations/bulk-import` — Master workbook or single entity |
| CLI entity | `php artisan medca:import categories file.csv` |
| CLI workbook | `php artisan medca:import services services.xlsx` (auto-detect) |
| CLI workbook explicit | `php artisan medca:import services services.xlsx --workbook` |
| Templates | `php artisan medca:export-import-templates` → `storage/imports/templates/` |
| Production populate | `medca:populate-production` (still uses per-entity CSV paths in `config/medca_launch.php`) |

## Configuration

- `config/import_registry.php` — entities, workbook sheet map, import order
- `docs/FIELD-REGISTRY.md` — DB field ownership
- `docs/MASTER-XLS-GUIDE.md` — column reference for operations teams

## Rollback

Each sheet commit creates an `ImportBatch`. Rollback via UI or `php artisan medca:rollback-import {batch}`.
