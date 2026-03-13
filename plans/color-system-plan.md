# 🎨 Portal Color System — Implementation Plan
**Primary:** `#8E43F0` Purple → **From:** Blue `#2563EB`  
**Theme:** Light | **Project:** `ai-agent-wp` React Portal

---

## Color Mapping Reference

| Old (Blue) | New (Purple) | Role |
|---|---|---|
| `#2563EB` | `#8E43F0` | Primary main |
| `#1D4ED8` | `#6A1FCC` | Primary dark / hover |
| `#3B82F6` | `#A96BF5` | Primary light |
| `#38BDF8` | `#0099C2` | Secondary (cyan accent) |
| `#F8FAFC` | `#F7F5FF` | Page background |
| `#FFFFFF` | `#FFFFFF` | Card surface |
| `#E2E8F0` | `#DDD4F8` | Borders / dividers |
| `#0F172A` | `#1A0A3C` | Text primary |
| `#64748B` | `#5C4A8A` | Text secondary |
| `#475569` | `#5C4A8A` | Text secondary alt |
| `#94A3B8` | `#9B89C4` | Text muted |
| `rgba(37,99,235,0.08)` | `rgba(142,67,240,0.08)` | Active nav bg |
| `rgba(37,99,235,0.06)` | `rgba(142,67,240,0.06)` | Hover nav bg |
| `rgba(15,23,42,0.08)` | `rgba(142,67,240,0.08)` | Card shadow |
| `#EFF6FF` | `#EDE8FC` | Primary pale bg (stats icon bg) |
| `#BFDBFE` | `#D4B8FA` | Primary pale border |
| `#DBEAFE` | `#EDE8FC` | Developer chip bg |
| `#7C3AED` | `#8E43F0` | Purple role → now primary |
| `#EDE9FE` | `#EDE8FC` | SuperAdmin chip bg |
| `#F1F5F9` | `#EDE8FC` | Scrollbar track / table head bg |
| `#CBD5E1` | `#D4B8FA` | Scrollbar thumb |

---

## Module 1 — `src/theme/index.js` ⭐ Foundation ✅ DONE (11/03/2026)

> **Highest-impact change** — all `color="primary"` MUI components now inherit purple.

### What was changed
- `palette.primary` → `#8E43F0` / `#A96BF5` / `#6A1FCC`
- `palette.secondary` → `#0099C2` (cyan)
- `palette.background.default` → `#F7F5FF`
- `palette.text` → `#1A0A3C` / `#5C4A8A`
- `palette.divider` → `#DDD4F8`
- `palette.grey` → purple-tinted 50→900 scale
- All shadows → `rgba(142,67,240,...)`
- `MuiButton` hover shadow → purple glow
- `MuiCard` border `#DDD4F8` + hover shadow purple
- `MuiPaper` border → `#DDD4F8`
- `MuiTextField` hover border → `#8E43F0`
- `MuiTableCell` head bg `#EDE8FC`, color `#5C4A8A`
- `MuiDrawer` shadow → purple

**Built & deployed ✅** (30.98s)

### Prompt 1 — Theme File
```
I'm updating a React MUI v5 portal's theme from blue (#2563EB) to purple (#8E43F0).
Below is my current theme/index.js file. Please rewrite the ENTIRE file replacing:

PRIMARY COLORS:
  main:  #2563EB → #8E43F0
  light: #3B82F6 → #A96BF5
  dark:  #1D4ED8 → #6A1FCC
  contrastText: keep #ffffff

SECONDARY COLORS:
  main:  #38BDF8 → #0099C2
  dark:  #0EA5E9 → #007DA6
  contrastText: #0F172A → #1A0A3C

BACKGROUND:
  default: #F8FAFC → #F7F5FF
  paper: keep #ffffff

TEXT:
  primary:   #0F172A → #1A0A3C
  secondary: #64748B → #5C4A8A

DIVIDER: #E2E8F0 → #DDD4F8

GREY SCALE (purple-tinted):
  50:  #F8FAFC → #F7F5FF
  100: #F1F5F9 → #EDE8FC
  200: #E2E8F0 → #DDD4F8
  300: #CBD5E1 → #C4B0EE
  400: #94A3B8 → #9B89C4
  500: #64748B → #5C4A8A
  600: #475569 → #4A3870
  700: #334155 → #32235A
  800: #1E293B → #1E1040
  900: #0F172A → #1A0A3C

ALL SHADOWS: Replace rgba(15,23,42,...) with rgba(142,67,240,...)
  Example: '0px 1px 2px rgba(15,23,42,0.06)' → '0px 1px 2px rgba(142,67,240,0.06)'

COMPONENT OVERRIDES:
  MuiButton hover shadow: rgba(37,99,235,...) → rgba(142,67,240,...)
  MuiCard border: #E2E8F0 → #DDD4F8
  MuiPaper elevation1 border: #E2E8F0 → #DDD4F8
  MuiTextField hover borderColor: #2563EB → #8E43F0
  MuiTableCell head: backgroundColor #F8FAFC → #EDE8FC, color #475569 → #5C4A8A
  MuiDrawer shadow: rgba(15,23,42,...) → rgba(142,67,240,...)

Here is the current file:
[PASTE src/theme/index.js]
```

---

## Module 2 — `src/index.css` ⭐ Global CSS ✅ DONE (11/03/2026)

### What was changed
- Added full `:root {}` CSS variables block (36 vars): primary purple scale, accents, surfaces, text, semantic, gradients, shadows, radii
- `body` background: `#F8FAFC` → `#F7F5FF`
- `body` color: `#0F172A` → `#1A0A3C`
- Scrollbar track: `#F1F5F9` → `#EDE8FC`
- Scrollbar thumb: `#CBD5E1` → `#D4B8FA`
- Scrollbar thumb hover: `#94A3B8` → `#9B89C4`

**Built & deployed ✅** (39.70s)

### Prompt 2 — Global CSS
```
Rewrite my React portal's index.css to add:
1. A :root {} CSS variables block at the top with the full purple color system:
   --color-primary: #8E43F0
   --color-primary-dark: #6A1FCC
   --color-primary-light: #A96BF5
   --color-primary-pale: #D4B8FA
   --color-accent-cyan: #0099C2
   --color-bg: #F7F5FF
   --color-surface: #FFFFFF
   --color-surface-tint: #EDE8FC
   --color-border: #DDD4F8
   --color-text: #1A0A3C
   --color-text-secondary: #5C4A8A
   --color-text-muted: #9B89C4
   --gradient-primary: linear-gradient(135deg, #8E43F0 0%, #D4006A 100%)
   --shadow-card: 0 2px 8px rgba(142,67,240,0.08)
   --shadow-primary: 0 8px 24px rgba(142,67,240,0.18)
   --shadow-hover: 0 12px 32px rgba(142,67,240,0.22)

2. Replace all hardcoded colors:
   body background-color: #F8FAFC → #F7F5FF
   body color: #0F172A → #1A0A3C    
   scrollbar-track: #F1F5F9 → #EDE8FC
   scrollbar-thumb: #CBD5E1 → #D4B8FA
   scrollbar-thumb:hover: #94A3B8 → #9B89C4

Here is the current file:
[PASTE src/index.css]
```

---

## Module 3 — Navigation & Layout ✅ DONE (11/03/2026)

### Files updated
- `src/layouts/DashboardLayout.jsx` — `bgcolor` `#F8FAFC` → `#F7F5FF`
- `src/components/saas/SaasSidebar.jsx`
- `src/components/saas/SaasNavbar.jsx`

### What was changed
**SaasSidebar.jsx:**
- Active nav bg: `rgba(37,99,235,0.08)` → `rgba(142,67,240,0.08)`
- Hover nav bg: `rgba(37,99,235,0.06)` → `rgba(142,67,240,0.06)`
- `roleMeta.SuperAdmin`: color `#7C3AED` → `#8E43F0`, bg `#EDE9FE` → `#EDE8FC`
- `roleMeta.Developer`: color `#2563EB` → `#6A1FCC`, bg `#DBEAFE` → `#EDE8FC`
- Logo icon box: `bgcolor: primary.main` → purple gradient `linear-gradient(135deg, #8E43F0, #6A1FCC)` + glow shadow
- Logo brand text: `#0F172A` → `#1A0A3C`
- User profile card: bg `#F8FAFC` → `#F7F5FF`, border `#E2E8F0` → `#DDD4F8`

**SaasNavbar.jsx:**
- AppBar `borderBottom`: `#E2E8F0` → `#DDD4F8`
- `notifTypeColor.task`: `#7C3AED` → `#8E43F0`
- `notifTypeColor.project`: `#0891B2` → `#0099C2`
- Notification + user menu dropdown shadows → `rgba(142,67,240,0.14)`
- Notification header/footer borders → `#DDD4F8`

**Built & deployed ✅** (35.74s)

### Key color instances in SaasSidebar.jsx

| Current | Replace with |
|---|---|
| `rgba(37,99,235,0.08)` | `rgba(142,67,240,0.08)` |
| `rgba(37,99,235,0.06)` | `rgba(142,67,240,0.06)` |
| `color: '#7C3AED'` (SuperAdmin) | `#8E43F0` |
| `bg: '#EDE9FE'` (SuperAdmin) | `#EDE8FC` |
| `color: '#2563EB'` (Developer) | `#6A1FCC` |
| `bg: '#DBEAFE'` (Developer) | `#EDE8FC` |
| Sidebar header gradient (if any) | `--gradient-primary` |

### Key color instances in SaasNavbar.jsx

| Current | Replace with |
|---|---|
| `borderBottom: '1px solid #E2E8F0'` | `#DDD4F8` |
| `#7C3AED` (task notif) | `#8E43F0` |
| `rgba(255,255,255,0.95)` AppBar bg | keep (glassmorphism) |

### Prompt 3 — Navigation
```
Update my React portal navigation to use #8E43F0 purple theme.

In SaasSidebar.jsx:
- Replace rgba(37,99,235,0.08) with rgba(142,67,240,0.08) — active nav bg
- Replace rgba(37,99,235,0.06) with rgba(142,67,240,0.06) — hover nav bg
- roleMeta SuperAdmin: color '#7C3AED' → '#8E43F0', bg '#EDE9FE' → '#EDE8FC'
- roleMeta Developer: color '#2563EB' → '#6A1FCC', bg '#DBEAFE' → '#EDE8FC'
- Sidebar header: Make the logo/brand area have a subtle purple gradient background
  using: linear-gradient(135deg, #8E43F0 0%, #6A1FCC 100%)
- Active nav indicator bar (3px wide box): bgcolor 'primary.main' → stays primary (now purple)

In SaasNavbar.jsx:
- borderBottom '#E2E8F0' → '#DDD4F8'
- notifTypeColor task: '#7C3AED' → '#8E43F0'

In DashboardLayout.jsx:
- bgcolor '#F8FAFC' → '#F7F5FF'

[PASTE each file]
```

---

## Module 4 — `StatsCard.jsx` Component ✅ DONE (11/03/2026)

### What was changed
- `colorMap.primary`: bg `#EFF6FF` → `#EDE8FC`, icon `#2563EB` → `#8E43F0`, border `#BFDBFE` → `#D4B8FA`
- `colorMap.purple`: bg `#FAF5FF` → `#EDE8FC`, icon `#7C3AED` → `#8E43F0`, border `#DDD6FE` → `#D4B8FA`
- `colorMap.info`: icon `#0284C7` → `#0099C2`, bg `#F0F9FF` → `#F0FAFF`
- Card border: `#E2E8F0` → `#DDD4F8`
- Card hover: `boxShadow rgba(15,23,42,0.10)` → `rgba(142,67,240,0.12)` + added `transform: translateY(-2px)`
- Value `Typography h4` color: `#0F172A` → `#1A0A3C`

**Built & deployed ✅** (33.90s)

### Prompt 4 — StatsCard
```
Rewrite my StatsCard.jsx component. Update ALL color instances:
- colorMap.primary: bg #EFF6FF → #EDE8FC, icon #2563EB → #8E43F0, border #BFDBFE → #D4B8FA
- colorMap.info: icon #0284C7 → #0099C2, bg #F0F9FF → #F0FAFF, border #BAE6FD → #BAE6FD
- colorMap.purple: bg #FAF5FF → #EDE8FC, icon #7C3AED → #8E43F0, border #DDD6FE → #D4B8FA
- Card border: 1px solid #E2E8F0 → #DDD4F8
- Card hover boxShadow: rgba(15,23,42,0.10) → rgba(142,67,240,0.12)
  Add transform: translateY(-2px) on hover
- Typography h4 value color: #0F172A → #1A0A3C
[PASTE StatsCard.jsx]
```

---

## Module 5 — `StatusBadge.jsx` & `PageHeader.jsx` ✅ DONE (11/03/2026)

### What was changed
**StatusBadge.jsx:**
- `in_progress`: `#2563EB`/`#EFF6FF`/`#BFDBFE` → `#8E43F0`/`#EDE8FC`/`#D4B8FA`
- `open`: `#7C3AED`/`#FAF5FF`/`#DDD6FE` → `#8E43F0`/`#EDE8FC`/`#D4B8FA`
- `Medium` priority: same blue → purple replacement
- `SuperAdmin` role: `#7C3AED` → `#8E43F0`
- `Developer` role: `#2563EB` → `#6A1FCC`
- `inactive`, `paused`, `Low`: `#64748B`/`#F1F5F9`/`#CBD5E1` → `#5C4A8A`/`#EDE8FC`/`#D4B8FA`
- Fallback default: `#64748B`/`#F1F5F9` → `#5C4A8A`/`#EDE8FC`
- Kept unchanged: `active`, `completed`, `done` (green), `review`, `High` (amber), `Critical` (red), `Client` (teal)

**PageHeader.jsx:**
- Page title color: `#0F172A` → `#1A0A3C`

**Built & deployed ✅** (57.78s)

### Prompt 5 — Shared Components
```
Update these shared components for the #8E43F0 purple theme.
For each file, replace ONLY these specific color values:
- #E2E8F0 → #DDD4F8 (borders)
- #F8FAFC → #F7F5FF (backgrounds)
- #F1F5F9 → #EDE8FC (light surface tints)
- #2563EB → #8E43F0 (primary blue → purple)
- #1D4ED8 → #6A1FCC (primary dark)
- rgba(37,99,235,...) → rgba(142,67,240,...) (primary rgba)
Keep all semantic colors unchanged (success green, warning amber, error red).
[PASTE StatusBadge.jsx, PageHeader.jsx]
```

---

## Module 6 — SaaS Pages ✅ DONE (11/03/2026)

### 13 files updated via PowerShell bulk replace
`Dashboard.jsx`, `Projects.jsx`, `Tasks.jsx`, `Clients.jsx`, `Reports.jsx`,
`ProjectCreate.jsx`, `ProjectEdit.jsx`, `ProjectDetail.jsx`,
`TaskCreate.jsx`, `TaskEdit.jsx`, `TaskDetail.jsx`,
`ClientCreate.jsx`, `ClientEdit.jsx` + `Notifications.jsx`

### Replacements applied to all files
| Find | Replaced with |
|---|---|
| `#2563EB`, `#7C3AED` | `#8E43F0` |
| `#1D4ED8` | `#6A1FCC` |
| `#3B82F6` | `#A96BF5` |
| `#38BDF8`, `#0891B2` | `#0099C2` |
| `#E2E8F0` | `#DDD4F8` |
| `#F8FAFC` | `#F7F5FF` |
| `#F1F5F9` | `#EDE8FC` |
| `#EFF6FF`, `#DBEAFE` | `#EDE8FC` |
| `#BFDBFE` | `#D4B8FA` |
| `#0F172A` | `#1A0A3C` |
| `#64748B`, `#475569` | `#5C4A8A` |
| `#94A3B8` | `#9B89C4` |
| `#CBD5E1` | `#D4B8FA` |
| `rgba(37,99,235,...)` | `rgba(142,67,240,...)` |
| `rgba(15,23,42,...)` | `rgba(142,67,240,...)` |

**Built & deployed ✅** (47.14s)

### Prompt 6A — Dashboard + Reports
```
Update Dashboard.jsx and Reports.jsx for the #8E43F0 purple color system.
Apply these replacements throughout both files:
  #2563EB → #8E43F0   (primary blue → purple)
  #1D4ED8 → #6A1FCC   (primary dark)
  #38BDF8 → #0099C2   (secondary cyan, keep as cyan)
  #7C3AED → #8E43F0   (purple-ish → primary purple)
  #E2E8F0 → #DDD4F8   (borders)
  #F8FAFC → #F7F5FF   (bg)
  #F1F5F9 → #EDE8FC   (surface tint)
  rgba(37,99,235,...) → rgba(142,67,240,...)
  rgba(15,23,42,...) → rgba(142,67,240,...)
  DBEAFE → EDE8FC, EFF6FF → EDE8FC, BFDBFE → D4B8FA
  #0F172A → #1A0A3C   (text primary)
  #64748B / #475569 → #5C4A8A (text secondary)
Do NOT change semantic colors: success green, warning amber, error red.
[PASTE Dashboard.jsx] [PASTE Reports.jsx]
```

### Prompt 6B — Projects, Tasks, Clients (batch)
```
Apply the same color replacement to these files:
[PASTE Projects.jsx, Tasks.jsx, Clients.jsx, ProjectCreate.jsx, ProjectEdit.jsx,
 ProjectDetail.jsx, TaskCreate.jsx, TaskEdit.jsx, TaskDetail.jsx,
 ClientCreate.jsx, ClientEdit.jsx, ClientDetail.jsx, Developers.jsx, Users.jsx]
Replacements:
  #2563EB → #8E43F0  |  #1D4ED8 → #6A1FCC  |  #7C3AED → #8E43F0
  #E2E8F0 → #DDD4F8  |  #F8FAFC → #F7F5FF  |  #F1F5F9 → #EDE8FC
  rgba(37,99,235,...) → rgba(142,67,240,...)
  rgba(15,23,42,...) shadow → rgba(142,67,240,...)
  #DBEAFE → #EDE8FC  |  #EFF6FF → #EDE8FC  |  #BFDBFE → #D4B8FA
  #0F172A → #1A0A3C  |  #64748B → #5C4A8A  |  #475569 → #5C4A8A
```

---

## Module 7 — Settings, Profile, Notifications ✅ DONE (11/03/2026)

### Files updated
`Settings.jsx`, `Profile.jsx` (Notifications.jsx already done in Module 6)

### What was changed
Same full replacement table as Module 6 applied.
Additional: `#EDE9FE` → `#EDE8FC`, `#334155` → `#32235A`

**Built & deployed ✅** (49.10s — combined with Modules 8 & 9)

---

## Module 8 — WP Sites Pages ✅ DONE (11/03/2026)

### Files updated
`Sites.jsx`, `SiteDetail.jsx`, `EditSite.jsx`

### Key replacements in SiteDetail.jsx
- Progress bar bgcolor: `#2563EB` → `#8E43F0`
- Toggle selected: `#EFF6FF`/`#BFDBFE`/`#2563EB` → `#EDE8FC`/`#D4B8FA`/`#8E43F0`
- Plugin chip: `#DBEAFE`/`#1D4ED8` → `#EDE8FC`/`#6A1FCC`
- Unknown status: `#64748B`/`#F1F5F9` → `#5C4A8A`/`#EDE8FC`
- Cell bg: `#F8FAFC` → `#F7F5FF`

**Built & deployed ✅**

---

## Module 9 — Auth Pages ✅ DONE (11/03/2026)

### Files updated
`auth/Login.jsx`, `auth/Signup.jsx`, `auth/ForgotPassword.jsx`, `auth/ResetPassword.jsx`

### What was changed
Full replacement table applied to all auth pages.

**Built & deployed ✅**

---

## Execution Order ✅ ALL COMPLETE

```
DONE ✅  Step 1   theme/index.js          — Foundation
DONE ✅  Step 2   index.css               — :root vars + body + scrollbar
DONE ✅  Step 3   SaasSidebar.jsx         — Nav active + role badges + gradient logo
DONE ✅  Step 4   SaasNavbar.jsx          — Top bar border + notif colors
DONE ✅  Step 5   DashboardLayout.jsx     — Page bg
DONE ✅  Step 6   StatsCard.jsx           — colorMap overhaul
DONE ✅  Step 7   StatusBadge.jsx         — Status/priority/role badge colors
DONE ✅  Step 8   PageHeader.jsx          — Heading color
DONE ✅  Step 9   Dashboard + Reports     — Charts + cards
DONE ✅  Step 10  Projects/Tasks/Clients  — All SaaS pages (13 files)
DONE ✅  Step 11  Settings + Profile      — Account pages
DONE ✅  Step 12  Sites + SiteDetail      — WP ops pages
DONE ✅  Step 13  Auth pages              — Login/Signup/ForgotPassword/ResetPassword
```
### Prompt 7 — Account Pages
```
Apply the purple color system to Settings.jsx, Profile.jsx, Notifications.jsx.
Replacements (same as Module 6). Additionally:
- Profile avatar upload dropzone border: any #2563EB → #8E43F0
- Profile avatar placeholder background: #EFF6FF → #EDE8FC
- Any 'primary.light' usage for avatar icon → inherits from theme automatically
[PASTE each file]
```

---

## Module 8 — WP Sites Pages (Sites.jsx, SiteDetail.jsx)

### Files
`src/pages/Sites.jsx`, `src/pages/SiteDetail.jsx`, `src/pages/EditSite.jsx`

### SiteDetail.jsx — additional specific changes

| Current | Replace with | Context |
|---|---|---|
| `#2563EB` | `#8E43F0` | Active count, progress bar, buttons |
| `#DBEAFE` | `#EDE8FC` | Plugin chip bg |
| `#1D4ED8` | `#6A1FCC` | Plugin chip text |
| `#EFF6FF` | `#EDE8FC` | Selected toggle bg |
| `#BFDBFE` | `#D4B8FA` | Selected toggle border |
| `#64748B` | `#5C4A8A` | Unknown status color |
| `#F1F5F9` | `#EDE8FC` | Unknown status bg |
| `#F8FAFC` | `#F7F5FF` | Cell backgrounds |

### Prompt 8 — WP Sites
```
Update Sites.jsx and SiteDetail.jsx to use the #8E43F0 purple color system.

In SiteDetail.jsx specifically:
- Progress bars: bgcolor: '#2563EB' → #8E43F0
- Toggle selected: bgcolor '#EFF6FF' → '#EDE8FC', borderColor '#BFDBFE' → '#D4B8FA', color '#2563EB' → '#8E43F0'
- Plugin chip: bgcolor '#DBEAFE' → '#EDE8FC', color '#1D4ED8' → '#6A1FCC'
- SEO Internal links color: '#2563EB' → '#8E43F0'
- Button bgcolor '#2563EB' → use variant="contained" (inherits from theme)
- ToggleButton '.Mui-selected': color '#2563EB' → '#8E43F0'

Apply full replacement table to both files.
[PASTE Sites.jsx] [PASTE SiteDetail.jsx]
```

---

## Module 9 — Auth Pages (Login, Signup, ForgotPassword, ResetPassword)

### Files
`src/pages/saas/auth/Login.jsx`, `Signup.jsx`, `ForgotPassword.jsx`, `ResetPassword.jsx`

### What to add
Auth pages should showcase the purple brand identity with a **gradient hero panel**.

### Prompt 9 — Auth Pages
```
Update my React auth pages (Login.jsx, Signup.jsx, ForgotPassword.jsx, ResetPassword.jsx)
to use the #8E43F0 purple color system.

Apply standard replacements:
  #2563EB → #8E43F0  |  #1D4ED8 → #6A1FCC
  #E2E8F0 → #DDD4F8  |  #F8FAFC → #F7F5FF
  rgba(37,99,235,...) → rgba(142,67,240,...)

Also enhance the auth layout split panel (if present):
- Left hero panel background: linear-gradient(135deg, #4A0D99 0%, #8E43F0 100%)
- Logo/title text on hero: #FFFFFF
- Feature bullet icons: color rgba(255,255,255,0.9)
- Right form panel: background #FFFFFF
- Form card border: 1px solid #DDD4F8

[PASTE each auth file]
```

---

## Execution Order

```
Step 1 ⭐  theme/index.js          — Foundation, affects all MUI components
Step 2 ⭐  index.css               — Global body + CSS variables + scrollbar
Step 3     SaasSidebar.jsx         — Nav active states + role badges
Step 4     SaasNavbar.jsx          — Top bar border + notification colors
Step 5     DashboardLayout.jsx     — Page bg color (1 line)
Step 6     StatsCard.jsx           — colorMap overhaul
Step 7     StatusBadge.jsx         — Border/bg spot replacements
Step 8     PageHeader.jsx          — Border/bg spot replacements
Step 9     Dashboard.jsx           — Charts + card colors
Step 10    Reports.jsx             — Chart fill colors
Step 11    Projects/Tasks/Clients  — Batch (same replacements)
Step 12    Settings/Profile/Notif  — Account pages
Step 13    Sites.jsx               — WP sites list
Step 14    SiteDetail.jsx          — WP site detail (most instances)
Step 15    Auth pages              — Login/Signup hero panel update
Step 16    Build + Deploy          — npm run build → docker cp → nginx reload
```

---

## Build & Deploy

After all changes:

```powershell
# Build
$env:NODE_OPTIONS="--max-old-space-size=4096"
npm run build

# Deploy to nginx container  
docker cp "d:\test-website\ai-agent\ai-agent-wp\dist\." ai_agent_frontend:/usr/share/nginx/html/
docker exec ai_agent_frontend nginx -s reload
```

---

## Quick Regex Find & Replace (VS Code)

Open **Search (Ctrl+Shift+H)** with regex enabled, scope to `src/`:

| Find | Replace |
|---|---|
| `#2563EB` | `#8E43F0` |
| `#1D4ED8` | `#6A1FCC` |
| `#3B82F6` | `#A96BF5` |
| `rgba\(37,\s*99,\s*235,` | `rgba(142, 67, 240,` |
| `rgba\(15,\s*23,\s*42,` | `rgba(142, 67, 240,` |
| `#E2E8F0` | `#DDD4F8` |
| `#F8FAFC` | `#F7F5FF` |
| `#F1F5F9` | `#EDE8FC` |
| `#0F172A` | `#1A0A3C` |
| `#64748B` | `#5C4A8A` |
| `#475569` | `#5C4A8A` |
| `#94A3B8` | `#9B89C4` |
| `#CBD5E1` | `#D4B8FA` |
| `#EFF6FF` | `#EDE8FC` |
| `#BFDBFE` | `#D4B8FA` |
| `#DBEAFE` | `#EDE8FC` |
| `#7C3AED` | `#8E43F0` |

> [!NOTE]
> Run Steps 1-2 first, **build**, and visually check before proceeding. The theme file alone fixes ~60% of all colors since MUI components inherit `color="primary"` from it.

> [!IMPORTANT]
> Do NOT replace semantic colors: `#22C55E` (success green), `#F59E0B` / `#D97706` (warning amber), `#EF4444` / `#DC2626` (error red) — these stay unchanged.
