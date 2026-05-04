# MarkOnMinds — Backend UI Design Audit Report

**Scope:** Authenticated shell (`x-app-layout` / `x-layouts.markonminds`) — `resources/css/markonminds.css`, `resources/css/app.css`, `tailwind.config.js`, and `variant="mom"` components.  
**Generated from codebase** (single source of truth: CSS variables in `:root`).  
**Swatch column:** HTML snippets suitable for Word/PDF paste (`bgcolor` + HEX).

---

## How to read swatches

Each color row includes a compact preview cell:

| Preview | Name | HEX | Notes |
|:-------:|------|-----|-------|

---

## 1. Color audit (complete)

### 1.1 Primary / secondary / accent (semantic roles)

| Role | Token / usage | HEX | RGB / RGBA |
|------|-----------------|-----|------------|
| **Primary text** | `--text-primary` | `#FFFFFF` | `rgb(255, 255, 255)` |
| **Secondary text** | `--text-secondary` | `#8E847E` | `rgb(142, 132, 126)` |
| **Muted text** | `--text-muted` | `#6A625C` | `rgb(106, 98, 92)` |
| **Accent (gold)** | `--accent-gold` | `#C5A059` | `rgb(197, 160, 89)` |
| **Wordmark / sidebar brand** | `--mom-wordmark` | `#A08750` | `rgb(160, 135, 80)` |

**Preview**

| Preview | Name | HEX |
|:-------:|------|-----|
| <span style="background:#ffffff;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | text-primary | `#FFFFFF` |
| <span style="background:#8e847e;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | text-secondary | `#8E847E` |
| <span style="background:#6a625c;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | text-muted | `#6A625C` |
| <span style="background:#c5a059;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | accent-gold | `#C5A059` |
| <span style="background:#a08750;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | mom-wordmark | `#A08750` |

---

### 1.2 Background system

| Name | Token | HEX | RGBA (if not opaque) | Where it appears |
|------|-------|-----|----------------------|------------------|
| Base app | `--bg-app` | `#120F0D` | — | `body.mom-body`, `.mom-content-pane` base, inherited by transparent rails |
| Sidebar base | `--bg-sidebar` | `#100D0B` | — | `.mom-sidebar-pane` |
| Surface | `--bg-surface` | `#151210` | — | Token available; Tailwind `mom-surface` |
| Elevated | `--bg-elevated` | `#1A1614` | — | Token available; Tailwind `mom-elevated` |
| Hover wash | `--bg-hover` | `#221D1A` | — | Sidebar row hover (`mom-sidebar-nav`) |
| Topbar scrim | `--bg-topbar-scrim` | — | `rgba(18, 15, 13, 0.9)` | `.mom-topbar-scrim` (sticky header) |
| Card matte | `--bg-card-matte` | `#1C1816` | — | `.mom-card`, modals (`mom` variant) |
| Card deep | `--bg-card-matte-deep` | `#161412` | — | Nested / depth (token) |
| Card raised | `--bg-card-matte-raised` | `#211C19` | — | `.elevated-surface`, `.matte-panel` |
| Table head | `--bg-card-table-head` | — | `rgba(42, 36, 32, 0.62)` | Table `<thead>` rows |
| Card track | `--bg-card-track` | — | `rgba(36, 30, 26, 0.75)` | Token |
| Card nested | `--bg-card-nested` | — | `rgba(48, 40, 34, 0.45)` | Dashboard nested tiles |
| Search field (topbar) | (inline) | — | `rgba(28, 22, 22, 0.92)` | Topbar search input `bg-[color:rgba(28,22,22,0.92)]` |
| Input fill (forms) | (inline) | — | `rgba(28, 22, 18, 0.75)` | Many selects/textareas `variant="mom"` |

**Preview (opaque)**

| Preview | Token | HEX |
|:-------:|-------|-----|
| <span style="background:#120f0d;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | --bg-app | `#120F0D` |
| <span style="background:#100d0b;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | --bg-sidebar | `#100D0B` |
| <span style="background:#1c1816;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | --bg-card-matte | `#1C1816` |
| <span style="background:#211c19;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | --bg-card-matte-raised | `#211C19` |

---

### 1.3 Accent derivatives (gold family)

| Name | Token | Value |
|------|-------|-------|
| Soft wash | `--accent-gold-soft` | `rgba(197, 160, 89, 0.12)` |
| Border (subtle) | `--accent-gold-border` | `rgba(197, 160, 89, 0.28)` |
| Glow mist | `--accent-gold-glow` | `rgba(197, 160, 89, 0.14)` |
| Border (strong / CTAs) | `--accent-gold-border-strong` | `rgba(197, 160, 89, 0.42)` |

**Usage:** `.mom-cta-primary`, `.mom-cta-ghost--active`, `.mom-nav-active`, card hover glow, chart/tooltip accents, focus rings (with explicit alphas in components).

---

### 1.4 Border & divider system

| Name | Token | Value |
|------|-------|-------|
| Soft (legacy utility) | `--border-soft` | `rgba(255, 255, 255, 0.045)` |
| Tabstrip hairline | `--border-tabstrip-divider` | `rgba(255, 255, 255, 0.05)` |
| Fade | `--border-fade` | `rgba(255, 255, 255, 0.025)` |
| Panel (solid reference) | `--border-panel` | `#3D342D` |
| Panel soft | `--border-panel-soft` | `rgba(61, 52, 45, 0.55)` |
| Default border rule | `--border-default` | `1px solid var(--border-panel-soft)` |
| Hover border | `--border-hover` | `1px solid rgba(197, 160, 89, 0.2)` |
| Active border | `--border-active` | `1px solid rgba(197, 160, 89, 0.32)` |

**Where:** Shell sidebar/topbar borders (`--border-panel-soft`); `.mom-backend-tabstrip` / hairlines (`--border-tabstrip-divider`); `.mom-card` border (`--border-default`); table wrappers (`border-[var(--border-panel-soft)]`); modal still uses `rgba(255,255,255,0.045)` in one place (see §6 consolidation).

---

### 1.5 Shadows & glows

| Name | Token | Value |
|------|-------|-------|
| Surface | `--shadow-surface` | `0 10px 30px rgba(0, 0, 0, 0.35)` |
| Elevated | `--shadow-elevated` | `0 20px 50px rgba(0, 0, 0, 0.45)` |
| Hover lift | `--shadow-hover` | `0 30px 60px rgba(0, 0, 0, 0.55)` |
| Gold glow | `--shadow-glow` | `0 0 40px rgba(197, 160, 89, 0.08)` |
| Inner highlight | `--shadow-inner` | `inset 0 1px 0 rgba(255, 255, 255, 0.04)` |

**Where:** Topbar `shadow-mom-surface`; cards `.mom-card`; interactive cards; `elevated-surface`; Apex tooltip; Lucide inherits foreground colors — no separate “icon token”; icons use `text-[var(--text-secondary)]`, `text-mom-gold`, etc.

---

### 1.6 Status colors

| Name | Token | HEX |
|------|-------|-----|
| Success | `--success` | `#62C370` |
| Danger | `--danger` | `#E25C5C` |
| Warning | `--warning` | `#E2B85C` |

**Info:** No dedicated `--info` token in `:root` (use secondary/muted or add token if needed).

**Preview**

| Preview | Token | HEX |
|:-------:|-------|-----|
| <span style="background:#62c370;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | success | `#62C370` |
| <span style="background:#e25c5c;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | danger | `#E25C5C` |
| <span style="background:#e2b85c;border:1px solid #ccc;display:inline-block;width:22px;height:22px;"></span> | warning | `#E2B85C` |

---

### 1.7 Gradients (documented stops)

**Sidebar `.mom-sidebar-pane`**

1. `radial-gradient(ellipse 90% 55% at 20% 0%, rgba(130, 88, 52, 0.12), transparent 52%)`
2. `linear-gradient(180deg, rgba(197, 160, 89, 0.06) 0%, transparent 40%)`
3. `linear-gradient(90deg, rgba(0, 0, 0, 0.28) 0%, transparent 42%)`

**Main `.mom-content-pane` / `.mom-main-pane`**

1. `radial-gradient(ellipse 88% 58% at 50% -5%, rgba(115, 78, 48, 0.14), transparent 55%)`
2. `radial-gradient(ellipse 75% 48% at 78% -6%, rgba(197, 160, 89, 0.09), transparent 52%)`
3. `linear-gradient(180deg, rgba(200, 160, 115, 0.07) 0%, transparent 50%)`

**Body overlays `body.mom-body::before`**

- Multiple radial gradients ending in transparent; gold/brown tints `rgba(197, 160, 89, 0.07)`, `rgba(100, 72, 48, 0.12)`, `rgba(255, 236, 210, 0.035)`.

**Body vignette `body.mom-body::after`**

- `rgba(12, 8, 5, 0.5)`, `rgba(28, 18, 12, 0.28)` (dark brown, not pure black).

**`.mom-card` stack**

- Top wash: `rgba(255, 250, 245, 0.028)` → transparent  
- Radial: `rgba(30, 24, 20, 0.45)` → transparent  
- Base: `var(--bg-card-matte)`

**`.mom-cta-primary`**

- `linear-gradient(180deg, rgba(197, 160, 89, 0.1), rgba(197, 160, 89, 0.02))`

**`.mom-section-separator`**

- Horizontal gradient: transparent → `rgba(255,255,255,0.05)` → `rgba(197,160,89,0.3)` → … → transparent

**Tailwind `backgroundImage.mom-sidebar-edge`**

- `linear-gradient(180deg, rgba(197,160,89,0.04) 0%, transparent 38%)`

---

### 1.8 Hover & focus (representative)

| Context | Typical rule |
|---------|----------------|
| Primary CTA hover | `border-color: rgba(197, 160, 89, 0.58)` + soft outer glow |
| Primary CTA focus-visible | Ring `2px` `--bg-app` + `4px` `rgba(197,160,89,0.35)` |
| Ghost / secondary hover | `border-color: rgba(197, 160, 89, 0.18)`; text → `--text-primary` |
| Ghost active (toolbar tab) | `--accent-gold-border-strong`, gold text, light gradient fill |
| Card interactive | `border: var(--border-hover)`, lift shadow + gold halo |
| Table row | `background-color: rgba(52, 44, 38, 0.35)` |
| Topbar search focus | `border rgba(197,160,89,0.35)`, glow shadow |
| Danger `mom` button | Hover: `border rgba(226,92,92,0.55)`, `bg rgba(226,92,92,0.18)` |

---

## 2. Shape / border-radius audit

### 2.1 CSS variables (`:root`)

| Token | Value | px |
|-------|-------|-----|
| `--radius-sm` | `10px` | 10 |
| `--radius-md` | `16px` | 16 |
| `--radius-card` | `12px` | 12 |
| `--radius-lg` | `22px` | 22 |
| `--radius-cta` | `8px` | 8 *(defined; primary CTAs use pill instead)* |
| `--radius-xl` | `28px` | 28 |
| `--radius-pill` | `999px` | full pill |

### 2.2 Tailwind extended radii (`tailwind.config.js`)

| Class | Resolves to |
|-------|-------------|
| `rounded-mom` | `22px` |
| `rounded-mom-sm` | `10px` |
| `rounded-mom-md` | `16px` |
| `rounded-mom-lg` | `22px` |
| `rounded-mom-xl` | `28px` |
| `rounded-mom-pill` | `999px` |

### 2.3 Component → radius mapping

| Component | Radius | Token / class | Where |
|-----------|--------|----------------|-------|
| **Primary button (`mom`)** | Pill (`999px`) | `var(--radius-pill)` | `.mom-cta-primary` via `primary-button` variant mom |
| **Secondary button (`mom`)** | Pill | `var(--radius-pill)` | `.mom-cta-ghost` |
| **Danger button (`mom`)** | `16px` | `rounded-mom-md` | `danger-button.blade.php` |
| **Card** | `12px` | `var(--radius-card)` | `.mom-card` |
| **Elevated surface utility** | `12px` | `var(--radius-card)` | `.elevated-surface` |
| **Modal panel (`mom`)** | `22px` | `rounded-mom-lg` | `modal.blade.php` |
| **ApexCharts tooltip** | `16px` | `var(--radius-md)` | `.mom-apex .apexcharts-tooltip` |
| **Operations tabs (link)** | Bottom border only | `border-b` (not radius on strip) | `primary-tabs`, `secondary-tabs` |
| **Sidebar active pill** | Pill | `var(--radius-pill)` | `.mom-nav-active` |
| **Topbar search** | Pill | `rounded-full` | `markonminds.blade.php` |
| **Avatar / icon buttons** | Circle | `rounded-full` / `h-10 w-10` | Topbar |
| **Scrollbar thumb** | `10px` | `var(--radius-sm)` | `.custom-scrollbar` |
| **Sidebar scrollbar** | `10px` | hardcoded `10px` | `.mom-sidebar-nav-scroll` |
| **Text input (`mom`)** | `16px` | `rounded-mom-md` | `text-input.blade.php` |
| **Kbd hint** | Small rounded | `rounded-md` | Topbar |

### 2.4 Radius categories (summary)

| Category | px | Examples |
|----------|-----|----------|
| **Small** | 10 | Scrollbars, sparkline context |
| **Medium** | 12–16 | Cards (12), inputs & danger button (16), tooltip (16) |
| **Large** | 22–28 | Modal (22), Tailwind `mom`/`mom-lg` (22), `mom-xl` (28) |
| **Pill / full** | 999 / full | `.mom-cta-*`, `.mom-nav-active`, search field, avatars |

---

## 3. Button system breakdown

### 3.1 Primary (`variant="mom"` → `.mom-cta-primary`)

| Property | Value |
|----------|--------|
| Background | `linear-gradient(180deg, rgba(197,160,89,0.1), rgba(197,160,89,0.02))` |
| Text | `var(--accent-gold)` → `#C5A059` |
| Border | `1px solid var(--accent-gold-border-strong)` → `rgba(197,160,89,0.42)` |
| Inner shadow | `inset 0 1px 0 rgba(255,255,255,0.04)` |
| Hover border | `rgba(197,160,89,0.58)` |
| Hover shadow | Inset highlight + `0 0 18px rgba(197,160,89,0.12)` |
| Focus ring | `0 0 0 2px var(--bg-app)`, `0 0 0 4px rgba(197,160,89,0.35)` |
| Border radius | `var(--radius-pill)` (999px) |
| Typography | `0.75rem`, semibold, uppercase, `letter-spacing: 0.18em` |

### 3.2 Secondary / ghost (`variant="mom"` → `.mom-cta-ghost`)

| Property | Value |
|----------|--------|
| Background | `transparent` |
| Text | `var(--text-secondary)` → `#8E847E` |
| Border | `1px solid transparent` (default) |
| Hover border | `rgba(197,160,89,0.18)` |
| Hover text | `var(--text-primary)` |
| Focus ring | Similar gold ring at `0.22` alpha outer |
| Border radius | `var(--radius-pill)` |

### 3.3 Ghost — active state (toolbar tabs, e.g. “Manage vacancies”)

| Property | Value |
|----------|--------|
| Class | `.mom-cta-ghost--active` |
| Border | `var(--accent-gold-border-strong)` |
| Text | `var(--accent-gold)` |
| Background | `linear-gradient(180deg, rgba(197,160,89,0.08), rgba(197,160,89,0.02))` |
| Inner shadow | `inset 0 1px 0 rgba(255,255,255,0.04)` |

### 3.4 Danger (`variant="mom"` → inline Tailwind in `danger-button.blade.php`)

| Property | Value |
|----------|--------|
| Border | `1px solid rgba(226,92,92,0.35)` |
| Background | `rgba(226,92,92,0.12)` |
| Text | `var(--danger)` → `#E25C5C` |
| Hover border | `rgba(226,92,92,0.55)` |
| Hover background | `rgba(226,92,92,0.18)` |
| Focus ring | `rgba(226,92,92,0.45)` |
| Border radius | `rounded-mom-md` → **16px** |
| Note | **Different radius family than primary/secondary** (pill vs md). |

### 3.5 Non-mom defaults (Breeze legacy)

`primary-button`, `secondary-button`, `danger-button` **default** variants still use gray/red Tailwind — not used in authenticated mom shell if only `variant="mom"` is used.

---

## 4. Typography (backend)

| Class | Size / weight | Color token |
|-------|----------------|-------------|
| `.mom-title-page` | `28px`, semibold | `--text-primary` |
| `.mom-section-title` | `18px`, medium | `--text-primary` |
| `.mom-metric` | `32px`, bold | `--text-primary` |
| `.mom-micro` | `11px`, uppercase, tracking | `text-mom-gold` → `--accent-gold` |
| `.mom-body-text` | `text-sm` | `--text-secondary` |
| `.mom-subtext` | `13px` | `--text-secondary` |
| `.mom-sidebar-label` | `text-sm` medium | `--text-secondary` |

**Font stack:** `"Noto Sans"`, system-ui (see `tailwind.config.js`).

---

## 5. Known duplicates & consolidation opportunities

1. **Modal border:** `border-[rgba(255,255,255,0.045)]` vs `--border-soft` / `--border-tabstrip-divider` — align to tokens.
2. **Danger button radius:** 16px vs CTA pill — intentional contrast or standardize.
3. **`--radius-cta` (8px):** defined but CTAs use `--radius-pill` — either adopt 8px for a “capsule but not full pill” or remove token.
4. **Tailwind `rounded-mom` and `rounded-mom-lg`:** both **22px** — redundant naming.
5. **Inline form backgrounds** `rgba(28,22,18,0.75)` — candidate for `--bg-input` token.
6. **Table hover** `rgba(52,44,38,0.35)` — candidate for `--bg-table-row-hover`.

---

## 6. File reference map

| Concern | Primary files |
|---------|----------------|
| All tokens | `resources/css/markonminds.css` `:root` |
| Component text utilities | `resources/css/app.css` `@layer components` |
| Tailwind bridge | `tailwind.config.js` `theme.extend` |
| Shell layout | `resources/views/components/layouts/markonminds.blade.php` |
| Buttons | `resources/views/components/*-button.blade.php`, `.mom-cta-*` in CSS |
| Operations rails | `.mom-backend-tabstrip`, `.mom-sticky-toolbar`, hairlines |

---

*End of report — suitable for import into Word/Google Docs; preserve HTML swatch spans for color cells.*
