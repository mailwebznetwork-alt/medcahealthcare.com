# Medca Launch Changelog

## 2026-06-03 — Build & launch mode (initial)

### Added

- `php artisan medca:launch-seed` — seeds services, pages, pincodes, global contact
- `MedcaLaunchServicesSeeder` — 6 clinical lines (published)
- `MedcaLaunchPagesSeeder` — marketing pages + careers + carousel tokens
- `MedcaLaunchGlobalContentSeeder` — phone/WhatsApp/address consistency
- `MedcaLaunchMedia` — storage placeholders for featured + gallery
- `tests/Feature/MedcaLaunchDataTest.php` — 5 automated checks
- `MEDCA-LAUNCH-CHECKLIST.md`, `MEDCA-SEO-PASS-REPORT.md`, `MEDCA-LAUNCH-READINESS-REPORT.md`

### Verification

```bash
php artisan medca:launch-seed
php artisan test --filter=MedcaLaunch
```

**5 passed** (2026-06-03)

### Rollback

Backup before seed: `/var/backups/medca-launch-20260603-115837/`
