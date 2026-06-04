# APPEARANCE & TYPOGRAPHY SYSTEM

## TYPOGRAPHY

**Font family (Headings):** Plus Jakarta Sans  
**Font family (Body):** Noto Sans  

*(Override in Settings → Appearance → Typography, then Publish.)*

---

### H1

- **Font family:** Plus Jakarta Sans  
- **Font size (Desktop):** 44px (2.75rem)  
- **Font size (Tablet):** 40px (2.5rem)  
- **Font size (Mobile):** 36px (2.25rem)  
- **Font weight:** 700  
- **Line height (Desktop):** 1.12 · **Tablet:** 1.14 · **Mobile:** 1.16  

### H2

- **Font family:** Plus Jakarta Sans  
- **Font size (Desktop):** 34px (2.125rem)  
- **Font size (Tablet):** 31px (1.9375rem)  
- **Font size (Mobile):** 28px (1.75rem)  
- **Font weight:** 600  
- **Line height (Desktop):** 1.18 · **Tablet:** 1.2 · **Mobile:** 1.22  

### H3

- **Font family:** Plus Jakarta Sans  
- **Font size (Desktop):** 28px (1.75rem)  
- **Font size (Tablet):** 26px (1.625rem)  
- **Font size (Mobile):** 24px (1.5rem)  
- **Font weight:** 600  
- **Line height (Desktop):** 1.22 · **Tablet:** 1.24 · **Mobile:** 1.26  

### H4

- **Font family:** Plus Jakarta Sans  
- **Font size (Desktop):** 22px (1.375rem)  
- **Font size (Tablet):** 20px (1.25rem)  
- **Font size (Mobile):** 18px (1.125rem)  
- **Font weight:** 600  
- **Line height (Desktop):** 1.28 · **Tablet:** 1.3 · **Mobile:** 1.32  

### H5

- **Font family:** Plus Jakarta Sans  
- **Font size (Desktop):** 18px (1.125rem)  
- **Font size (Tablet):** 17px (1.0625rem)  
- **Font size (Mobile):** 16px (1rem)  
- **Font weight:** 600  
- **Line height (Desktop):** 1.32 · **Tablet:** 1.34 · **Mobile:** 1.35  

### H6

- **Font family:** Plus Jakarta Sans  
- **Font size (Desktop):** 16px (1rem)  
- **Font size (Tablet):** 15px (0.9375rem)  
- **Font size (Mobile):** 14px (0.875rem)  
- **Font weight:** 600  
- **Line height (Desktop):** 1.35 · **Tablet:** 1.36 · **Mobile:** 1.38  

### Body Large

- **Font family:** Noto Sans  
- **Font size (Desktop):** 18px (1.125rem)  
- **Font size (Tablet):** 17px (1.0625rem)  
- **Font size (Mobile):** 16px (1rem)  
- **Font weight:** 400  
- **Line height (Desktop):** 1.6 · **Tablet:** 1.6 · **Mobile:** 1.55  

### Body Regular

- **Font family:** Noto Sans  
- **Font size (Desktop):** 16px (1rem)  
- **Font size (Tablet):** 16px (1rem)  
- **Font size (Mobile):** 15px (0.9375rem)  
- **Font weight:** 400  
- **Line height:** 1.5 (all breakpoints)  

### Small Text

- **Font family:** Noto Sans  
- **Font size (Desktop):** 14px (0.875rem)  
- **Font size (Tablet):** 14px (0.875rem)  
- **Font size (Mobile):** 13px (0.8125rem)  
- **Font weight:** 400  
- **Line height:** 1.45 (all breakpoints)  

### Button Text

- **Font family:** Noto Sans  
- **Font size (Desktop):** 15px (0.9375rem)  
- **Font size (Tablet):** 15px (0.9375rem)  
- **Font size (Mobile):** 14px (0.875rem)  
- **Font weight:** 600  
- **Line height:** 1.2 (all breakpoints)  

---

## Desktop typography

Base breakpoint: viewport **> 1023px**. Values above (Desktop column) apply.

## Tablet typography

Breakpoint: **≤ 1023px**. Tablet column sizes/line heights apply via CSS variables.

## Mobile typography

Breakpoint: **≤ 767px**. Mobile column sizes/line heights apply via CSS variables.

---

## Font scale (Appearance)

| Scale   | Effect                          |
|---------|----------------------------------|
| Compact | −6.25% on all spec sizes        |
| Default | As documented                 |
| Large   | +12.5% on all spec sizes      |

---

## Technical implementation

| File | Role |
|------|------|
| `config/typography.php` | Source of truth |
| `app/Services/Theme/TypographyScaleResolver.php` | CSS + admin spec |
| `resources/views/components/theme/public-vars.blade.php` | Injects on public layout |
