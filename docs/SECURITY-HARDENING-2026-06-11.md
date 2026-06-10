# Security Hardening Pass — 11 June 2026

## Breach Root Cause (Confirmed)

The 10 June unauthorized password change was **not** caused by a web endpoint bug. It was caused by **CLI access**:

```php
// Executed via php artisan tinker on the server
User::find(1)->password = bcrypt('password');
```

**Safeguards now in place:**
- `UserObserver` + `Log::warning()` on every password hash change
- `password_changed` / `password_change_failed` activity logs + admin notifications
- `DatabaseSeeder` no longer overwrites admin passwords in production unless `ALLOW_SEED_ADMIN_PASSWORD_RESET=true`

---

## Filament Note

This codebase uses a **custom MarkOnMinds admin** (Blade + Livewire), not Filament. There is no `AdminPanelProvider.php`. Backend access is enforced via:

- `User::canAccessPanel()` — staff roles with active accounts
- `User::canAccessIntegrationsAdmin()` — strict `admin` / `super_admin` only
- Route middleware: `auth`, `active`, `verified`, `auto.logout`, `module:*`, `role:*`

---

## Password Change Surfaces Audited

| Surface | Current password required? | Session invalidation | Rate limit |
|---------|---------------------------|----------------------|------------|
| `PUT /password` (profile) | ✅ `current_password` | ✅ `Auth::logoutOtherDevices()` | ✅ 5/min |
| `POST /reset-password` (email token) | N/A (token) | ✅ All DB sessions cleared | ✅ 5/min |
| `POST /forgot-password` | N/A | N/A | ✅ 5/min |
| `POST /confirm-password` | ✅ | N/A | ✅ 5/min |
| User Management `PUT /user-management/{user}` | ✅ Actor `admin_password` | ✅ Target sessions cleared | Policy + module gate |
| `PATCH /profile` | N/A (no password field) | N/A | — |
| Registration | Disabled | — | — |
| Livewire components | No password update paths found | — | — |

---

## Mass Assignment

**Removed from `User::$fillable`:** `password`, `role`, `module_access`, `is_active`

Sensitive attributes must be set via `forceFill()` / direct assignment in trusted controllers only.

**Role escalation guard:** managers cannot assign `admin`/`super_admin`; only super admins can assign `super_admin`.

---

## New / Modified Files

- `app/Services/Security/PasswordSecurityService.php`
- `app/Http/Requests/Auth/UpdatePasswordRequest.php`
- `app/Http/Controllers/Auth/PasswordController.php`
- `app/Http/Controllers/Auth/NewPasswordController.php`
- `app/Http/Controllers/Auth/ConfirmablePasswordController.php`
- `app/Http/Controllers/UserManagement/UserController.php`
- `app/Http/Requests/UserManagement/UpdateUserRequest.php`
- `app/Models/User.php`
- `app/Observers/UserObserver.php`
- `app/Http/Middleware/AdminMiddleware.php`
- `database/seeders/DatabaseSeeder.php`
- `database/factories/UserFactory.php`
- `routes/auth.php`
- `config/notifications.php`
- `tests/Feature/PasswordSecurityHardeningTest.php`

---

## Deploy Commands

```bash
cd /var/www/medca_healthcare
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan test tests/Feature/PasswordSecurityHardeningTest.php
```

---

## Remaining Recommendations

1. Set `LOG_LEVEL=warning` in production `.env`
2. Enable `SESSION_ENCRYPT=true` after Redis session verification
3. Enforce HTTPS at Nginx (`return 301 https://$host$request_uri`)
4. Restrict server SSH / disable `tinker` on production except break-glass
5. Rotate MOMJERRIE password if tinker access was ever exposed
