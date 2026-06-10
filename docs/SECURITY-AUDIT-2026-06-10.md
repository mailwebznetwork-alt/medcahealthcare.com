# Medca Healthcare — Security Audit Report

**Date:** 10 June 2026  
**Scope:** Full Laravel platform (`/var/www/medca_healthcare`)  
**Auditor:** Ravi (Lead Developer)  
**Environment:** Production (`medcahealthcare.com`)

---

## Executive Summary

The platform has a solid Laravel foundation: authentication middleware on admin routes, CSRF protection, role-based access, policy enforcement, login throttling, and `APP_DEBUG=false` in production. The largest gaps were **missing HTTP security headers**, **notification blind spots for the sole administrator**, and **verbose per-row audit logging during bulk deletes** that never surfaced in the bell UI.

Low-risk fixes were applied in this pass. Medium/high-risk items are documented below for staged rollout.

---

## Critical Incident: Unauthorized Root Password Change

**Date/time:** 10 June 2026, 14:42 UTC  
**Account:** WDJERRIE (`mail.webznetwork@gmail.com`, user ID 1)  
**Cause:** Automated browser debugging on this server ran `php artisan tinker` and executed:

```php
User::find(1)->password = bcrypt('password'); // save()
```

**You did not change your password.** Confirmed:
- No `password_changed` activity log existed before this fix
- `users.updated_at` = 14:42 matches the tinker command timestamp
- Your last successful login was **14:39** — *before* the unauthorized overwrite
- Hash still matches the debug value `password` as of this audit

**Safeguard added:** `UserObserver` now logs `password_changed` to Security module and sends an admin notification (including sole-admin awareness) on every password hash change — including CLI/tinker changes tagged `source=unauthenticated/system`.

**Recovery:** Log in with the temporary debug credential, then immediately set a new password via **Profile → Password**. Do not reuse `password`.

---

## Why You Saw No Notifications

| Symptom | Root Cause |
|---------|------------|
| Bulk pin-code / service deletes | `AdminNotificationRecipientResolver` excluded the acting user. WDJERRIE is the **only** admin → recipient list was empty. |
| "Password error" on bulk delete | Modal requires typing `DELETE` exactly. Wrong text shows a validation error but **did not log or notify** until this fix. |
| Category purge notifications | Artisan purge ran **without** an authenticated user (`actorUserId = null`), so the actor exclusion did not apply. |

**Fixes applied:**
- Critical/destructive actions now notify the actor when they are the sole admin.
- `bulk_delete`, `bulk_delete_blocked`, `bulk_action_failed`, and `login_failure` always reach the actor.
- Per-item `pincode_deleted` / `service_deleted` activity logs suppressed during bulk (aggregate `bulk_delete` log retained).
- Bulk confirmation failures and exceptions are logged and notified.

---

## Vulnerability List & Risk Classification

### Critical — None identified in code review

No unauthenticated admin routes, no SQL injection vectors in user input paths, no exposed `.env` in web root.

### High

| ID | Finding | Status |
|----|---------|--------|
| H-01 | No Content-Security-Policy / HSTS / X-Frame-Options headers | **Fixed** — `SecurityHeaders` middleware |
| H-02 | Sole-admin notification blind spot on destructive ops | **Fixed** — recipient resolver update |
| H-03 | `LOG_LEVEL=debug` in production `.env` | **Open** — set `LOG_LEVEL=warning` or `error` |
| H-04 | HTTPS not enforced at application layer (relies on Nginx) | **Open** — verify Nginx `return 301 https://` |

### Medium

| ID | Finding | Status |
|----|---------|--------|
| M-01 | `SESSION_ENCRYPT=false` | **Open** — enable after Redis session stability verified |
| M-02 | CSP uses `unsafe-inline` / `unsafe-eval` (Livewire requirement) | **Mitigated** — document; tighten incrementally |
| M-03 | Password policy used Laravel defaults (weak) | **Fixed** — min 12 chars, mixed case, numbers; symbols + uncompromised in production |
| M-04 | Bulk deletes logged 200+ per-row activity entries | **Fixed** — aggregate-only during bulk |
| M-05 | `marketing/track` CSRF exempt | **Accepted** — public analytics endpoint; rate-limited |
| M-06 | API Sanctum routes lack per-route rate limits beyond global 60/min | **Open** — add stricter limits on write endpoints |

### Low

| ID | Finding | Status |
|----|---------|--------|
| L-01 | `upload_validation_failure` muted from notifications | **Accepted** — reduces noise |
| L-02 | SVG uploads excluded from image processing | **OK** — XSS vector avoided |
| L-03 | Registration disabled in `routes/auth.php` | **OK** |
| L-04 | `ProductionStaffEmail` blocks disposable domains | **OK** |
| L-05 | BCRYPT_ROUNDS=12 | **OK** |
| L-06 | Session regeneration on login | **OK** |
| L-07 | Remember-me uses Laravel encrypted cookies | **OK** |

---

## Area-by-Area Audit

### 1. Authentication Security ✅ Mostly OK

- Bcrypt hashing (`BCRYPT_ROUNDS=12`)
- Login throttle: 5 attempts / 60s per email+IP
- Session regeneration on successful login
- Inactive account blocked at login
- Password reset uses `Password::defaults()` (now strengthened)
- Registration route disabled

### 2. Authorization & Roles ✅ OK

- All admin modules use `auth`, `active`, `verified`, `auto.logout`, `module:*`, `role:*` middleware stacks
- Policies registered for Service, PinCode, User, Media, Page, etc.
- Super-admin-only routes for system settings and backups
- Root super-admin email protected in config

### 3. Session Security ⚠️ Partial

- `SESSION_DRIVER=redis`, `SESSION_SECURE_COOKIE=true` (production)
- `SESSION_ENCRYPT=false` — recommend enabling
- Auto-logout middleware present
- Session invalidation on logout

### 4. CSRF Protection ✅ OK

- Global CSRF validation enabled
- Single exempt route: `marketing/track` (documented)

### 5. XSS Protection ✅ OK

- Blade `{{ }}` auto-escaping used throughout views
- SVG excluded from image derivative pipeline
- CSP header now set (relaxed for Livewire)

### 6. SQL Injection ✅ OK

- Eloquent ORM used consistently
- Limited `whereRaw` / `selectRaw` in analytics — no user-bound raw SQL found in mutation paths

### 7. File Upload Security ✅ OK

- Max size enforced (`media.max_upload_kb`)
- MIME allowlist for images
- UUID-based storage paths (randomized)
- Original extension sanitized via `Str::slug`

### 8. API Security ⚠️ Partial

- Sanctum auth on admin API routes
- Payment ingest requires signature middleware
- Lead endpoints rate-limited (5/min)
- CORS: default Laravel (review if mobile app added)

### 9. Rate Limiting ⚠️ Partial

- Login: 5/min
- Public leads: 5/min
- API default: 60/min
- Admin UI routes: no global throttle (rely on auth)

### 10. Security Headers ✅ Fixed

Implemented via `App\Http\Middleware\SecurityHeaders`:
- Content-Security-Policy
- Strict-Transport-Security (HTTPS/production)
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy: camera/microphone/geolocation disabled

### 11. Environment Protection ✅ OK

- `.env` outside web root
- `APP_KEY` set
- `APP_DEBUG=false`

### 12. Audit Logging ✅ Improved

- Activity log on all significant actions
- Automated write audit for master data
- Bulk operations now produce single summary log + notification
- Failed login attempts logged and now notify sole admin

### 13. Error Handling ✅ OK

- Production debug off
- Exceptions reported via Laravel handler
- Bulk failures logged with `bulk_action_failed`

### 14. Dependencies ⚠️ Review

Run `COMPOSER_ALLOW_SUPERUSER=1 composer audit` in CI on each deploy.

---

## Implemented Fixes Summary

| Fix | Files |
|-----|-------|
| Security headers middleware | `app/Http/Middleware/SecurityHeaders.php`, `config/security.php`, `bootstrap/app.php` |
| Sole-admin notifications | `AdminNotificationRecipientResolver.php`, `config/notifications.php` |
| Bulk delete notification labels & URLs | `AdminNotificationPresenter.php` |
| Bulk confirmation/failure logging | `InteractsWithBulkActions.php` |
| Suppress per-row bulk activity noise | `PinCodeMasterDataAudit.php`, `MasterDataAudit.php` |
| Password policy hardening | `AppServiceProvider.php` |
| Tests | `tests/Feature/AdminNotificationTest.php` |

---

## Remaining Recommendations (PR-Style Summaries)

### PR-1: Production Log Level (Medium)

**Change:** `LOG_LEVEL=warning` in production `.env`  
**Risk:** Low — may hide useful debug info during incidents  
**Benefit:** Prevents sensitive data in verbose logs

### PR-2: Session Encryption (Medium)

**Change:** `SESSION_ENCRYPT=true`  
**Risk:** Medium — requires testing with Redis sessions across deploys  
**Benefit:** Encrypted session payload at rest

### PR-3: Nginx HTTPS Redirect (High)

**Change:** Add `if ($scheme != "https") { return 301 https://$host$request_uri; }` or dedicated port-80 server block  
**Risk:** Low if cert valid  
**Benefit:** Eliminates mixed-content and credential leakage over HTTP

### PR-4: Stricter CSP (High — staged)

**Change:** Remove `unsafe-eval`, nonce Livewire scripts  
**Risk:** High — can break admin UI if not tested thoroughly  
**Benefit:** Strong XSS mitigation

### PR-5: Admin Route Rate Limiting (Medium)

**Change:** `throttle:120,1` on authenticated admin groups  
**Risk:** Low — may affect bulk import UX  
**Benefit:** Brute-force protection on authenticated session hijack

### PR-6: Dependency CI Audit (Low)

**Change:** GitHub Action / deploy hook running `composer audit`  
**Risk:** None  
**Benefit:** Early CVE detection

---

## OWASP Top 10 Mapping

| OWASP 2021 | Status |
|------------|--------|
| A01 Broken Access Control | ✅ Middleware + policies |
| A02 Cryptographic Failures | ⚠️ Session encrypt pending |
| A03 Injection | ✅ Eloquent ORM |
| A04 Insecure Design | ✅ Bulk governance + DELETE confirm |
| A05 Security Misconfiguration | ⚠️ LOG_LEVEL, HTTPS redirect |
| A06 Vulnerable Components | ⚠️ Run composer audit in CI |
| A07 Auth Failures | ✅ Throttle + strengthened passwords |
| A08 Data Integrity Failures | ✅ CSRF + signed payment ingest |
| A09 Logging Failures | ✅ Fixed notification gap |
| A10 SSRF | ✅ No user-controlled URL fetch found |

---

## Verification Checklist

- [x] `php artisan test --filter=AdminNotificationTest`
- [x] Security headers present on HTTP responses
- [x] Bulk delete creates one notification for sole admin
- [x] Wrong DELETE confirmation creates `bulk_delete_blocked` notification
- [ ] Change production `LOG_LEVEL` (manual ops)
- [ ] Verify Nginx HTTPS redirect (manual ops)
- [ ] Reset WDJERRIE password from temporary debug value (manual ops)

---

*Report generated as part of security hardening sprint. Re-audit recommended after CSP tightening and session encryption rollout.*
