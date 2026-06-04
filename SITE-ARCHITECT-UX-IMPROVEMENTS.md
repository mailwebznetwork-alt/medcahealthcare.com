# Site Architect — SAFE UX Improvements Log

**Date:** 2026-06-03  
**Related audit:** `SITE-ARCHITECT-USER-JOURNEY-AUTOPSY.md`  
**Scope:** Labels, grouping hints, helper text, empty states, onboarding — **no** routes, permissions, DB, or rendering changes  

---

## What changed

### 1. Central UX copy helper

| File | Purpose |
|------|---------|
| `app/Support/SiteArchitectUxCopy.php` | Workspace welcome, 5-step compose journey, tab group hints, mental model matrix (for docs/tests) |

### 2. Compose journey banner

| File | Purpose |
|------|---------|
| `resources/views/site-architect/partials/compose-journey.blade.php` | Visible 5-step path: Page → Section → Content → Preview → Live |
| `resources/views/components/site-architect/workspace.blade.php` | Shows banner on Pages, Blocks Studio, Blocks Factory, Templates |

### 3. Navigation hints

| File | Change |
|------|--------|
| `resources/views/site-architect/partials/primary-tabs.blade.php` | One-line description under each tab group (Content / Blocks / Deploy / Advanced) |

### 4. Pages workspace (primary user surface)

| File | Change |
|------|--------|
| `resources/views/livewire/site-architect/pages.blade.php` | Empty state with CTA; “Page sections” heading; “Add existing block”; “Edit content” + Blocks Studio link; developer-only code hint in modal |

### 5. Blocks workspaces

| File | Change |
|------|--------|
| `resources/views/livewire/site-architect/block-studio.blade.php` | Title “Edit section content”; plain-language instructions |
| `resources/views/livewire/site-architect/block-factory.blade.php` | “For developers” banner at top of list |
| `resources/views/site-architect/block-presets-shell.blade.php` | Templates marked optional |
| `resources/views/site-architect/blueprint-builder-shell.blade.php` | Admin setup wording (not “second CMS”) |

### 6. Media

| File | Change |
|------|--------|
| `resources/views/livewire/site-architect/media-library.blade.php` | Hint linking uploads to Blocks Studio Media panel |

### 7. Default workspace welcome

| File | Change |
|------|--------|
| `resources/views/components/site-architect/workspace.blade.php` | Default welcome via `SiteArchitectUxCopy::workspaceWelcome()` |

---

## What did NOT change

- Routes, middleware, policies  
- `ContentParser`, block sync, seeders  
- Operations / Services / Leads  
- Database schema  
- Public rendering  
- Tab visibility rules (from simplification project)  

---

## How to verify

1. Log in as **editor** → Site Architect → Pages  
   - See compose journey banner and Content/Blocks hints  
   - No Deploy/Advanced tabs  
2. Edit **Home** → Page sections → **Edit content** on a block → lands in Blocks Studio  
3. Open **Blocks Factory** → see developer banner  
4. Log in as **admin** → Deploy + Advanced tabs still present  

```bash
php artisan test --filter=SiteArchitectSimplification
```

---

## Recommended next (MEDIUM — not implemented)

1. **Block slug picker** on Pages (“Add section” dropdown of active blocks)  
2. **Hide Blocks Factory tab** for `editor` role (keep route)  
3. **Templates** tab as collapsible panel inside Blocks Studio  
4. Rename **Navigation** tab to **Menus**  

---

*End of SAFE UX improvements log.*
