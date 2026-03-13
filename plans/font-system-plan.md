# Font System — Plus Jakarta Sans Migration Plan
**Project:** `ai-agent-wp` React Portal  
**Font:** Plus Jakarta Sans (Google Fonts / @fontsource)  
**Stack:** React + MUI v5  
**Goal:** Replace Roboto/system font with Plus Jakarta Sans across all components

---

## Color Reference (already applied)
| Role | Value |
|---|---|
| Primary | `#8E43F0` |
| Primary Dark | `#6A1FCC` |
| Background | `#F7F5FF` |
| Text Primary | `#1A0A3C` |
| Text Secondary | `#5C4A8A` |

---

## Execution Order

```
[x] Module 1  → src/main.jsx           @fontsource/plus-jakarta-sans installed + 6 weight imports
[x] Module 2  → src/theme/index.js     All typography variants (h1-h6, body, button, caption, overline, subtitles)
[x] Module 3  → src/theme/index.js     MuiCssBaseline override with font smoothing + feature settings
[x] Module 4  → src/App.jsx            ThemeProvider + CssBaseline already correct ✓ (no change needed)
[x] Module 5  → src/theme/index.js     25+ component overrides applied (Button, Input, Table, Chip, Tab, Dialog…)
[x] Module 6  → src/index.css          Global * font reset + font-feature-settings + Inter/Roboto removed
[x] Module 7  → index.html             Preconnect + Google Fonts link added + title → "AI Agent Portal"
[x] Module 8  → Multiple files         Scanned — no @fontsource/roboto or Roboto font references found ✓
[x] Module 9  → All .jsx/.css files    Scanned — no hardcoded Roboto/Helvetica fontFamily strings found ✓
[x] Module 10 → src/theme/index.js     Final consolidated theme — Plus Jakarta Sans + full purple palette ✓
[ ] Module 11 → Verify in browser      Run: window.getComputedStyle(document.body).fontFamily in DevTools

✅ BUILT & DEPLOYED — 2026-03-11
```

---

## Module 1 — Install Font Package ⭐

**File:** `src/main.jsx`

```bash
npm install @fontsource/plus-jakarta-sans
```

Then add to the **very top** of `src/main.jsx` (before all other imports):

```js
import '@fontsource/plus-jakarta-sans/300.css';
import '@fontsource/plus-jakarta-sans/400.css';
import '@fontsource/plus-jakarta-sans/500.css';
import '@fontsource/plus-jakarta-sans/600.css';
import '@fontsource/plus-jakarta-sans/700.css';
import '@fontsource/plus-jakarta-sans/800.css';
```

### Prompt 1
```
In my React + MUI project at src/main.jsx, install and import Plus Jakarta Sans via @fontsource.
Run: npm install @fontsource/plus-jakarta-sans
Then add these 6 imports at the very top of src/main.jsx before all other imports:
  import '@fontsource/plus-jakarta-sans/300.css';
  import '@fontsource/plus-jakarta-sans/400.css';
  import '@fontsource/plus-jakarta-sans/500.css';
  import '@fontsource/plus-jakarta-sans/600.css';
  import '@fontsource/plus-jakarta-sans/700.css';
  import '@fontsource/plus-jakarta-sans/800.css';
Show me the updated src/main.jsx.
```

---

## Module 2 — MUI Theme Typography Config ⭐

**File:** `src/theme/index.js`

Update the `typography` key inside `createTheme()`:

```js
typography: {
  fontFamily: [
    'Plus Jakarta Sans',
    '-apple-system',
    'BlinkMacSystemFont',
    '"Segoe UI"',
    'Roboto',
    '"Helvetica Neue"',
    'Arial',
    'sans-serif',
  ].join(','),
  h1: { fontWeight: 800, fontSize: 'clamp(1.8rem, 4vw, 2.4rem)', lineHeight: 1.15, letterSpacing: '-0.025em' },
  h2: { fontWeight: 700, fontSize: 'clamp(1.4rem, 3vw, 1.9rem)', lineHeight: 1.2, letterSpacing: '-0.015em' },
  h3: { fontWeight: 700, fontSize: '1.25rem', lineHeight: 1.3, letterSpacing: '-0.01em' },
  h4: { fontWeight: 600, fontSize: '1.05rem', lineHeight: 1.35 },
  h5: { fontWeight: 600, fontSize: '0.95rem' },
  h6: { fontWeight: 600, fontSize: '0.88rem' },
  body1: { fontWeight: 400, fontSize: '1rem', lineHeight: 1.75 },
  body2: { fontWeight: 400, fontSize: '0.875rem', lineHeight: 1.65 },
  button: { fontWeight: 600, fontSize: '0.875rem', letterSpacing: '0.01em', textTransform: 'none' },
  caption: { fontWeight: 400, fontSize: '0.75rem', lineHeight: 1.5 },
  overline: { fontWeight: 700, fontSize: '0.7rem', letterSpacing: '0.12em', textTransform: 'uppercase' },
  subtitle1: { fontWeight: 500, fontSize: '0.95rem', lineHeight: 1.6 },
  subtitle2: { fontWeight: 500, fontSize: '0.85rem', lineHeight: 1.57 },
},
```

### Prompt 2
```
In src/theme/index.js, update the typography section of createTheme() to use Plus Jakarta Sans
as the fontFamily and configure all variants (h1-h6, body1, body2, button, caption, overline,
subtitle1, subtitle2) with the following weights and sizes:
  fontFamily: 'Plus Jakarta Sans, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
  h1: fontWeight 800, fontSize clamp(1.8rem,4vw,2.4rem), letterSpacing -0.025em
  h2: fontWeight 700, fontSize clamp(1.4rem,3vw,1.9rem), letterSpacing -0.015em
  h3: fontWeight 700, fontSize 1.25rem, letterSpacing -0.01em
  h4: fontWeight 600, fontSize 1.05rem
  h5: fontWeight 600, fontSize 0.95rem
  h6: fontWeight 600, fontSize 0.88rem
  body1: fontWeight 400, fontSize 1rem, lineHeight 1.75
  body2: fontWeight 400, fontSize 0.875rem, lineHeight 1.65
  button: fontWeight 600, textTransform none, letterSpacing 0.01em
  caption: fontWeight 400, fontSize 0.75rem
  overline: fontWeight 700, fontSize 0.7rem, letterSpacing 0.12em, textTransform uppercase
  subtitle1: fontWeight 500, fontSize 0.95rem, lineHeight 1.6
  subtitle2: fontWeight 500, fontSize 0.85rem, lineHeight 1.57
Show me the complete updated theme file.
```

---

## Module 3 — MuiCssBaseline Override

**File:** `src/theme/index.js`

Add inside `components` block of `createTheme()`:

```js
MuiCssBaseline: {
  styleOverrides: `
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
    }
    * {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    input, textarea, select, button {
      font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
  `,
},
```

### Prompt 3
```
In src/theme/index.js, add a MuiCssBaseline styleOverrides entry to the components section
that sets Plus Jakarta Sans globally with antialiased font smoothing and font-feature-settings
'cv02','cv03','cv04','cv11'. Make the * selector force the font on all elements, and use
!important on input/textarea/select/button to override browser defaults.
Show me the complete updated theme file.
```

---

## Module 4 — Verify ThemeProvider Wrap

**File:** `src/App.jsx`

Verify `App.jsx` wraps with `<ThemeProvider theme={theme}>` and `<CssBaseline />`.

> ✅ Already done in this project — `App.jsx` has `ThemeProvider` + `CssBaseline`. Just confirm the import is from `./theme/index`.

### Prompt 4
```
In src/App.jsx, confirm that the entire app is wrapped with ThemeProvider and CssBaseline:
  import theme from './theme/index';
  <ThemeProvider theme={theme}><CssBaseline />{...}</ThemeProvider>
If already present, just verify the theme import path is './theme/index' and both are imported
from @mui/material. Show me the relevant section.
```

---

## Module 5 — Component-Level Font Overrides ⭐

**File:** `src/theme/index.js`

Add to the `components` section (alongside existing MuiButton, MuiCard etc.):

| Component | Key override |
|---|---|
| `MuiAppBar` | `fontFamily` on root |
| `MuiToolbar` | `fontFamily` on root |
| `MuiButton` | `fontFamily`, `fontWeight 600`, `textTransform none` |
| `MuiIconButton` | `fontFamily` on root |
| `MuiInputBase` | `fontFamily`, `fontSize 0.9rem` on root + input |
| `MuiInputLabel` | `fontFamily`, `fontWeight 500`, `fontSize 0.875rem` |
| `MuiTextField` | `fontFamily` on root |
| `MuiSelect` | `fontFamily`, `fontWeight 400` on select |
| `MuiMenuItem` | `fontFamily`, `fontSize 0.875rem` |
| `MuiListItemText` | primary: `fontWeight 500`, `0.875rem`; secondary: `0.78rem` |
| `MuiTableCell` | head: `fontWeight 700`, `0.72rem`, uppercase; body: `0.85rem` |
| `MuiChip` | `fontWeight 600`, `fontSize 0.75rem` |
| `MuiTooltip` | `fontSize 0.75rem`, `fontWeight 500` |
| `MuiAlert` | `fontSize 0.875rem`, `fontWeight 500` |
| `MuiDialogTitle` | `fontWeight 700`, `fontSize 1.1rem` |
| `MuiTab` | `fontWeight 600`, `fontSize 0.85rem`, `textTransform none` |
| `MuiBreadcrumbs` | `fontSize 0.82rem` |
| `MuiBadge` | `fontWeight 700`, `fontSize 0.68rem` |
| `MuiFormHelperText` | `fontSize 0.72rem` |
| `MuiPaginationItem` | `fontWeight 600`, `fontSize 0.82rem` |

### Prompt 5
```
In src/theme/index.js, add component-level font overrides in the components section for:
MuiAppBar, MuiToolbar, MuiButton (fontWeight 600, textTransform none), MuiIconButton,
MuiInputBase (fontSize 0.9rem), MuiInputLabel (fontWeight 500, fontSize 0.875rem),
MuiTextField, MuiSelect, MuiMenuItem (fontSize 0.875rem),
MuiListItemText (primary fontWeight 500 0.875rem, secondary 0.78rem),
MuiTableCell (head: uppercase fontWeight 700 0.72rem; body: 0.85rem),
MuiChip (fontWeight 600, 0.75rem),
MuiTooltip (0.75rem, fontWeight 500),
MuiAlert (0.875rem, fontWeight 500),
MuiDialogTitle (fontWeight 700, 1.1rem),
MuiDialogContentText (0.9rem),
MuiTab (fontWeight 600, 0.85rem, textTransform none),
MuiBreadcrumbs (0.82rem),
MuiBadge (fontWeight 700, 0.68rem),
MuiFormHelperText (0.72rem),
MuiPaginationItem (fontWeight 600, 0.82rem).
All use fontFamily 'Plus Jakarta Sans, sans-serif'.
Show me the complete updated theme file.
```

---

## Module 6 — Global CSS Reset

**File:** `src/index.css`

Add at the top (before existing `:root` block):

```css
/* ── Global Font Reset ── */
*, *::before, *::after {
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

html { font-size: 16px; }

body {
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  font-weight: 400;
  line-height: 1.7;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
}

input, textarea, select, button, a {
  font-family: inherit;
}
```

### Prompt 6
```
In src/index.css, add a global font reset at the top of the file that:
1. Sets 'Plus Jakarta Sans' on all elements via * selector
2. Sets html font-size to 16px
3. Updates the body rule to use 'Plus Jakarta Sans', lineHeight 1.7, antialiased smoothing,
   font-feature-settings 'cv02','cv03','cv04','cv11'
4. Ensures input/textarea/select/button/a use font-family: inherit
Preserve the existing :root CSS variables block below. Show me the complete updated file.
```

---

## Module 7 — index.html Preconnect + Link Tag

**File:** `index.html` (Vite root)

Add inside `<head>` after viewport meta, before other links:

```html
<!-- Google Fonts: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
  rel="stylesheet"
/>
```

### Prompt 7
```
In the root index.html file (Vite project, not in public/), add Google Fonts preconnect and
stylesheet link tags inside <head> after the charset/viewport meta tags:
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
Show me the updated <head> section.
```

---

## Module 8 — Remove Roboto References

**Files:** All `.jsx`, `.js`, `.css`, `index.html`, `package.json`

Search and remove:
- `import '@fontsource/roboto/...'`
- `<link href="...Roboto..." rel="stylesheet" />`
- Any `fontFamily: 'Roboto'` in theme or component files

Run if found: `npm uninstall @fontsource/roboto`

### Prompt 8
```
Search the entire src/ directory and public/index.html for any Roboto font references:
1. Remove any: import '@fontsource/roboto/xxx.css' from src/main.jsx
2. Remove any Roboto <link> tags from index.html
3. In src/theme/index.js, ensure 'Plus Jakarta Sans' is the first entry in fontFamily, not Roboto
4. Search all .jsx/.js/.css files for fontFamily: 'Roboto' and replace with 'Plus Jakarta Sans, sans-serif'
5. Check package.json — if @fontsource/roboto is listed, run: npm uninstall @fontsource/roboto
Show all changed files.
```

---

## Module 9 — Replace Hardcoded fontFamily in Components

**Files:** All `.jsx` / `.css` in `src/components/` and `src/pages/`

Search for and replace:
- `fontFamily: 'Roboto, sans-serif'` → `'Plus Jakarta Sans, sans-serif'`
- `fontFamily: '"Helvetica Neue", Arial, sans-serif'` → `'Plus Jakarta Sans, sans-serif'`
- Any hardcoded `font-family: Roboto` in `.css` files

### Prompt 9
```
Search all .jsx, .js, and .css files in src/ for hardcoded fontFamily or font-family strings
that reference Roboto, Helvetica, or Arial as the primary font. Replace all with:
  fontFamily: 'Plus Jakarta Sans, sans-serif'   (for JS/JSX sx props)
  font-family: 'Plus Jakarta Sans', sans-serif  (for CSS)
Also check src/components/saas/ and src/pages/ subdirectories.
Show all changed files.
```

---

## Module 10 — Final Consolidated Theme ⭐ (Fonts + Colors)

**File:** `src/theme/index.js`

Generate a complete production-ready theme combining:
- **Purple color palette** (already applied in Module 1 of color migration)
- **Plus Jakarta Sans** for all typography + component overrides

### Prompt 10
```
Generate a complete, production-ready src/theme/index.js for my React + MUI v5 portal that:
1. Uses Plus Jakarta Sans as the ONLY font family (imported via @fontsource)
2. Includes the full purple color palette:
     Primary: #8E43F0 | Primary Dark: #6A1FCC | Primary Light: #A96BF5
     Secondary: #D4006A | Background: #F7F5FF | Surface: #FFFFFF
     Text Primary: #1A0A3C | Text Secondary: #5C4A8A | Disabled: #9B89C4
     Success: #1A9E6A | Warning: #B87200 | Error: #CC2222 | Info: #1A6FBB
3. Full typography config for h1-h6, body1, body2, button, caption, overline, subtitle1, subtitle2
4. MuiCssBaseline override with font smoothing + font-feature-settings
5. Component overrides for: MuiButton, MuiInputBase, MuiInputLabel, MuiInputBase, MuiSelect,
   MuiMenuItem, MuiTableCell, MuiChip, MuiTab, MuiAlert, MuiTooltip, MuiListItemText,
   MuiAppBar, MuiDrawer, MuiDialogTitle, MuiBreadcrumbs, MuiBadge, MuiFormHelperText
6. All buttons/tabs: textTransform none
7. Purple-tinted shadows: 0 4px 14px rgba(142,67,240,0.15)
Export as default from src/theme/index.js.
```

---

## Module 11 — Verify Font in Browser

Paste this in Chrome DevTools Console to verify:

```js
window.getComputedStyle(document.body).fontFamily
// Expected: "Plus Jakarta Sans", ...
```

Or check: **DevTools → Elements → select `<body>` → Computed → font-family**

### Prompt 11 (optional debug component)
```
Create a temporary FontDebug.jsx component that:
1. Displays window.getComputedStyle(document.body).fontFamily on mount
2. Renders Typography variants h1-h6, body1, body2, button, caption, overline with
   the text "Plus Jakarta Sans — The quick brown fox" and computed font-family next to each
3. Includes an MUI TextField and Button to verify input/button font
4. Add it temporarily to App.jsx
Show me the complete FontDebug.jsx.
```

---

## Build & Deploy

After completing all modules:

```powershell
# Build
$env:NODE_OPTIONS="--max-old-space-size=4096"
npm run build

# Deploy to nginx container
docker cp "d:\test-website\ai-agent\ai-agent-wp\dist\." ai_agent_frontend:/usr/share/nginx/html/
docker exec ai_agent_frontend nginx -s reload
```

---

## Browser Verification Checklist

```
[ ] DevTools Network tab → Font → see Plus Jakarta Sans .woff2 files loading
[ ] DevTools Elements → <body> → Computed → font-family = "Plus Jakarta Sans"
[ ] All headings (h1-h6) render with correct weights
[ ] Buttons, inputs, table cells, chips all use Plus Jakarta Sans
[ ] No Roboto requests in Network tab
```
