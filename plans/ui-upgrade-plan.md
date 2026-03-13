# UI Modernization Plan — AI Agent Portal
**Stack:** React + MUI v5 + Plus Jakarta Sans + Purple Theme (#8E43F0)
**Rule:** UI changes ONLY — no business logic, API, routing, or state changes.

> [!IMPORTANT]
> **Date Format:** All dates must display as **DD/MM/YYYY** everywhere.
> `src/utils/dates.js → fmtDate()` already returns DD/MM/YYYY ✅
> Any raw `new Date().toLocaleDateString()` calls must be replaced with `fmtDate()`.

---

## Current Pain Points

| Area | Issue |
|---|---|
| Sidebar | Flat list, no visual grouping, no hover animations |
| PageHeader | Basic title + subtitle, no visual hierarchy |
| StatsCard | Plain numbers, no gradient, no micro-animation |
| DataTable | No row hover highlight, no column filter chips |
| Dashboard | Charts look detached, no KPI callouts |
| Forms (Create/Edit) | Plain stacked fields, no section separation |
| Detail pages | Wall of text, no tabs, no visual sections |
| Auth pages | Basic card layout, no hero panel |
| Empty states | Missing or plain text |
| Mobile | No responsive drawer, no bottom nav |

---

## Execution Order

```
[x] Module 1  → Shared Components      SaasSidebar, SaasNavbar, DashboardLayout
[x] Module 2  → Core UI Kit            StatsCard, PageHeader, DataTable, StatusBadge, FormCard
[x] Module 3  → Dashboard              Glassmorphism KPI cards, chart polish, activity feed
[x] Module 4  → Projects               Kanban/Table toggle, card view, progress rings
[x] Module 5  → Tasks                  Priority pills, kanban column view option, timeline
[x] Module 5B → Action Icons           Interactive icon buttons across all DataTable action columns
[x] Module 6  → Clients & Developers   Avatar headers, detail tabs, stat mini-cards
[x] Module 7  → Create/Edit Forms      Multi-step wizard layout or tabbed sections
[x] Module 8  → Detail Pages           Tab navigation, timeline, comment threads
[x] Module 9  → Reports                Chart dashboard, filter bar, export button group
[x] Module 10 → Auth Pages             Split-screen hero + form, animated gradient
[x] Module 11 → Notifications          Feed timeline, mark-all, read/unread states
[x] Module 12 → Settings & Profile     Avatar upload card, tabbed sections
[x] Module 13 → Sites & WP Pages       Status chips, health meter, plugin cards
[x] Module 14 → Empty States & 404     Illustrated empty states, better NotFound
[x] Module 15 → Mobile & Tablet        Bottom nav, swipe drawers, responsive cards, sticky bars
[ ] Module 16 → Date Format Audit      Replace all raw date strings with fmtDate() → DD/MM/YYYY
[ ] Module 17 → Nav Layout Toggle      Left↔Top nav toggle, collapsible sidebar, user preference saved
[ ] Module 18 → API Integration Audit  Full GraphQL query/mutation map + missing API coverage
```

---

## Module 1 — Shared Navigation ⭐ HIGH IMPACT

### Files
- `src/components/saas/SaasSidebar.jsx`
- `src/components/saas/SaasNavbar.jsx`
- `src/layouts/DashboardLayout.jsx`

### What to Upgrade
**SaasSidebar:**
- Add **section labels** ("Agency", "WordPress", "Account") between nav groups using `Typography variant="overline"`
- Add **active indicator** — left border or filled pill background on active item
- Add **hover animation** — `translateX(4px)` on `ListItemButton` via `transition`
- Add **mini-mode icon tooltip** for future collapsible sidebar (optional)
- Add a **notification badge** on Notifications nav item using `Badge`
- Improve **user profile card** at bottom — Avatar with gradient ring, name, role chip, logout icon

**SaasNavbar:**
- Add **search bar** in center (uses existing state, just adds TextField with SearchIcon)
- Add **notification bell** with `Badge badgeContent` showing unread count
- Add **user avatar menu** with Popper dropdown (Avatar → Menu → Profile/Logout items)
- Add **glassmorphism AppBar** — `backdrop-filter: blur(10px)` + translucent bg

### Prompt 1A — Sidebar
```
Upgrade SaasSidebar.jsx — UI changes only, keep all nav items, routing, and auth role checks:
1. Add section header labels between the 3 nav groups using:
   <Typography variant="overline" sx={{ px:2, py:0.5, color:'text.disabled', fontSize:'0.65rem' }}>AGENCY</Typography>
2. Wrap each ListItemButton with transition: 'all 0.15s' and &:hover { transform: translateX(4px) }
3. Add a left active indicator on selected items:
   &.Mui-selected { '&::before': { content:'""', position:'absolute', left:0, top:'20%', height:'60%', width:3, borderRadius:2, bgcolor:'primary.main' } }
4. Update user profile card at the bottom: Avatar 40px with gradient border, bold name, role chip, and a LogoutOutlined icon button
5. Keep all useAuth(), useLocation(), router logic unchanged
[PASTE SaasSidebar.jsx]
```

### Prompt 1B — Navbar
```
Upgrade SaasNavbar.jsx — UI changes only, keep all auth/notification logic:
1. Add backdrop-filter: blur(12px) to the AppBar sx, change bgcolor to rgba(255,255,255,0.85)
2. Add a centered search TextField (width 260px, size small) between the logo and right icons
3. Wrap the notification bell in MUI Badge with badgeContent showing unread count (use existing notifCount state)
4. Replace text username with Avatar + Popper menu (Profile link + Logout button)
5. Keep all existing notif click handlers, menu state, and auth calls unchanged
[PASTE SaasNavbar.jsx]
```

---

## Module 2 — Core UI Kit ⭐ HIGH IMPACT

### Files
- `src/components/saas/StatsCard.jsx`
- `src/components/saas/PageHeader.jsx`
- `src/components/saas/DataTable.jsx`
- `src/components/saas/StatusBadge.jsx`
- `src/components/saas/FormCard.jsx`

### What to Upgrade
**StatsCard:**
- Add **gradient icon background** (diagonal gradient matching color)
- Add **micro-animation** — `transform: scale(1.03)` on hover
- Add **animated counter** using CSS transition on value
- Add **trend arrow** (TrendingUp/Down) with color-coded percentage chip
- Add subtle **bottom border accent** matching color

**PageHeader:**
- Add **gradient accent** strip above title (3px height, full width)
- Add **breadcrumb trail** with MUI `Breadcrumbs` component (already exists, polish it)
- Improve **action button** area with Button groups

**DataTable:**
- Add **row click highlight** on hover
- Add **column type filter chips** above the table
- Add **empty state** illustration/icon when no results
- Add **export button** (CSV) in the toolbar

**StatusBadge:**
- Replace plain text with properly rounded `Chip` with dot indicator
- Add subtle left-dot (`●`) before label

### Prompt 2A — StatsCard
```
Upgrade StatsCard.jsx — UI only, keep all props (title, value, icon, color, trend, trendLabel):
1. Wrap icon in a Box with background: linear-gradient(135deg, colorMap.icon+'22', colorMap.icon+'08') and border-radius:2
2. Add on hover: transform scale(1.02), box-shadow from 'var(--shadow-card)' to 'var(--shadow-primary)'
3. Add a thin bottom border accent: borderBottom: '3px solid', borderColor: colorMap.icon
4. Show trend as a Chip with TrendingUp or TrendingDown icon, green for positive, red for negative
5. Keep all colorMap values and existing props intact
[PASTE StatsCard.jsx]
```

### Prompt 2B — DataTable
```
Upgrade DataTable.jsx — UI only, keep all props, sorting, pagination, search logic:
1. Add a toolbar row above table: left=search TextField, right=export CSV IconButton (Tooltip "Export CSV") that calls window.open with data:text/csv...
2. Add row hover bgcolor '#F7F5FF' via TableRow sx hover
3. Add empty state: when paginated.length===0 show a centered Box with InboxIcon (large, muted), Typography "No results found", and a "Clear search" Button if search is active
4. Add sticky TableHead using stickyHeader prop on Table
5. Keep all existing filter/sort/pagination logic unchanged
[PASTE DataTable.jsx]
```

---

## Module 3 — Dashboard ⭐ HIGH IMPACT

### File: `src/pages/dashboard/Dashboard.jsx`

### What to Upgrade
- **KPI cards row** — use upgraded StatsCard with gradient icons + animated counters
- **Chart cards** — add gradient backgrounds to chart panels, richer tooltips
- **Recent Projects list** — convert to proper `List` with `LinearProgress` per row and `Avatar`
- **Recent Tasks list** — add priority-colored left border strips, due date color-coding
- **Quick Actions row** — add a "Quick Actions" card with 4 shortcuts (New Project, New Task, View Reports, Add Client)
- **Activity Feed** — add a compact feed panel showing latest 5 actions

### Prompt 3
```
Upgrade Dashboard.jsx — UI only, keep all queries (GET_DASHBOARD_STATS, GET_PROJECTS, GET_TASKS) and data processing:
1. Wrap each stats card Grid item with a subtle entrance animation using sx keyframes
2. Add a "Quick Actions" Card below stats row with 4 Buttons:
   - New Project → navigate('/projects/create')
   - New Task → navigate('/tasks/create')
   - View Reports → navigate('/reports')
   - Add Client → navigate('/clients/create')
3. Upgrade Recent Projects list items: add LinearProgress bar per project, show progress%, colorize due date red if overdue
4. Upgrade Recent Tasks list: add a colored left-border strip per item (red=Critical, orange=High, purple=Medium, green=Low)
5. Add a "View All" Button at bottom of each recent list linked to /projects and /tasks
Keep all existing data logic, queries, and state unchanged.
[PASTE Dashboard.jsx]
```

---

## Module 4 — Projects ⭐

### Files
- `src/pages/projects/Projects.jsx`
- `src/pages/projects/ProjectDetail.jsx`
- `src/pages/projects/ProjectCreate.jsx`
- `src/pages/projects/ProjectEdit.jsx`

### What to Upgrade
**Projects.jsx (list view):**
- Add **view toggle** (Table / Card) using `ToggleButtonGroup` with TableRows/GridView icons
- **Card view** — `Grid` of `Card`s with project name, client avatar, progress ring, status badge, team avatars
- **Table view** — styled DataTable (already exists, just polish)
- Add **status filter chips** above the table (All / Active / Completed / On Hold)

**ProjectDetail.jsx:**
- Add **sticky header** with project name + back button + status badge
- Convert sections to **Tab panels**: Overview / Tasks / Team / Files / Activity
- Add **task completion ring** (PieChart or MUI CircularProgress)
- Add **team avatars** in an AvatarGroup

**ProjectCreate/Edit:**
- Convert to **3-step wizard** or **2-column form** layout using Stepper or Tabs

### Prompt 4A — Projects List
```
Upgrade Projects.jsx — UI only, keep all mutations, queries, navigate, confirm handlers:
1. Add a ToggleButtonGroup (Table | Grid) above the DataTable. Use useState('table'/'grid') for view mode.
2. Grid view: render projects as MUI Cards (xs=12 sm=6 lg=4) showing:
   - Project name (Typography h6 fontWeight 700)
   - Client name (body2 muted)
   - CircularProgress (determinate, value=progress, size=48, color=primary) centered
   - StatusBadge below
   - AvatarGroup for developers (max 3, size 24)
   - Footer: due date + Edit/Delete iconButtons
3. Add status filter Chip row (All / active / completed / on_hold / cancelled). Filter the rows array client-side.
4. Table view stays as-is using DataTable
5. Keep all existing handleDelete, navigate, hasRole logic unchanged
[PASTE Projects.jsx]
```

---

## Module 5 — Tasks ⭐

### Files
- `src/pages/tasks/Tasks.jsx`
- `src/pages/tasks/TaskDetail.jsx`
- `src/pages/tasks/TaskCreate.jsx`
- `src/pages/tasks/TaskEdit.jsx`

### What to Upgrade
**Tasks.jsx:**
- Add **priority filter bar** (Critical / High / Medium / Low as color-coded Chips)
- Add **assignee filter** dropdown
- **Priority pill** renderer improvement — gradient background matching priority color
- **Due date color** — red if overdue, amber if within 3 days

**TaskDetail.jsx:**
- Add **comment thread** UI (input box + message bubbles)
- Add **status progress stepper** (Open → In Progress → Review → Done) with MUI `Stepper`
- Add **related project** card link

### Prompt 5 — Tasks List
```
Upgrade Tasks.jsx — UI only, keep all mutations, queries, navigate, handleStatusChange handlers:
1. Add a filter bar row above DataTable:
   - Priority chips: All, Critical(red), High(orange), Medium(purple), Low(green)
   - Filter tasks array client-side by selected priority
2. In the priority column render: add gradient-bg Box (color+'22' bg, color text, fontWeight 700, borderRadius 2, px 1.5)
3. In the title column: if due_date is past today, show due date in red; if within 3 days, show in amber
4. In the actions column: add a Tooltip "View" IconButton (Visibility) navigating to /tasks/:id
5. Keep all useMutation, useNavigate, handleStatusChange, handleDelete, hasRole logic unchanged
[PASTE Tasks.jsx]
```

---

## Module 6 — Clients & Developers

### Files
- `src/pages/clients/Clients.jsx`
- `src/pages/clients/ClientDetail.jsx`
- `src/pages/clients/ClientCreate.jsx` / `ClientEdit.jsx`
- `src/pages/developers/Developers.jsx`
- `src/pages/developers/DeveloperDetail.jsx`

### What to Upgrade
- **Clients/Developers list** — add Card grid view with Avatar (initials, gradient bg), contact info, project count chip
- **ClientDetail/DeveloperDetail** — add hero header with Avatar (large, gradient), stats row (Projects count, Tasks, Revenue), tab panels

### Prompt 6A — Clients List
```
Upgrade Clients.jsx — UI only, keep all queries, mutations, navigate, confirm:
1. Add Card grid view toggle (same pattern as Projects)
2. Card: Avatar (56px, initials, gradient bgcolor from primary), company name, email, phone, active projects Chip
3. Filter chips: All / active / inactive
4. Keep table view as fallback using DataTable
[PASTE Clients.jsx]
```

---

## Module 5B — Action Icons ⭐ HIGH IMPACT

### Context
Table action columns (View 👁 / Edit ✏ / Delete 🗑) currently use plain `IconButton` with no hover feedback — they look flat and unpolished (see screenshot).

### Files (global — affects all pages with DataTable action columns)
- `src/pages/projects/Projects.jsx`
- `src/pages/tasks/Tasks.jsx`
- `src/pages/clients/Clients.jsx`
- `src/pages/developers/Developers.jsx`
- `src/pages/users/Users.jsx`
- `src/components/saas/DataTable.jsx` (export button area)

### What to Upgrade
- **View (👁)** — rounded square bg on hover: `#EDE8FC`, icon color `#8E43F0`, subtle `scale(1.12)` on hover
- **Edit (✏)** — hover bg `#FFFBEB`, icon color `#D97706`
- **Delete (🗑)** — hover bg `#FFF1F2`, icon stays `error.main`
- All icons: 28×28px size, `borderRadius: 1.5`, `transition: all 0.15s`, **no border by default** (appear on hover using `box-shadow`)
- **Card view footer icons** — same `ActionBtn` pattern must also apply to card-grid views (not just DataTable rows). Remove any `border: '1px solid'` sx props from card footer `IconButton`s — they look like ugly boxy borders (see screenshot).
- Use a shared **`ActionBtn`** helper component so all pages stay DRY:
  ```jsx
  function ActionBtn({ title, icon: Icon, color, hoverBg, onClick, size = 'small' }) {
    return (
      <Tooltip title={title}>
        <IconButton size={size} onClick={onClick} sx={{
          color, borderRadius: 1.5, transition: 'all 0.15s',
          '&:hover': { bgcolor: hoverBg, transform: 'scale(1.12)', color },
        }}>
          <Icon fontSize="small" />
        </IconButton>
      </Tooltip>
    );
  }
  ```

### Prompt 5B — Interactive Action Buttons (apply to each file)
```
In [FILE].jsx, replace all Table action IconButton groups with interactive ActionBtn components:
- Keep ALL onClick handlers, navigate() calls, hasRole() guards exactly as-is
- View   IconButton → ActionBtn color="#8E43F0"  hoverBg="#EDE8FC" title="View"
- Edit   IconButton → ActionBtn color="#D97706"  hoverBg="#FFFBEB" title="Edit"
- Delete IconButton → ActionBtn color="#DC2626"  hoverBg="#FFF1F2" title="Delete"
Define ActionBtn inline at the top of the file (outside the component).
No changes to queries, mutations, state, or routing.
```

---

## Module 7 — Create/Edit Forms


### Files (all follows same pattern)
- `ProjectCreate.jsx`, `ProjectEdit.jsx`
- `TaskCreate.jsx`, `TaskEdit.jsx`
- `ClientCreate.jsx`, `ClientEdit.jsx`
- `DeveloperCreate.jsx`, `DeveloperEdit.jsx`
- `UserCreate.jsx`, `UserEdit.jsx`

### What to Upgrade
- Wrap fields in **FormCard sections** with icon headers (already have FormCard, polish it)
- Add **sticky bottom action bar** for Save/Cancel
- Add **field-level validation** visual feedback (already API-driven, just add helperText)
- Add **autofocus** on first field
- 2-column grid layout for related fields (Name/Email side by side)

### Prompt 7 — Form Pages (apply to each)
```
Upgrade [FileName].jsx — UI only, keep all state, mutations, validation, and navigation:
1. Wrap all form fields in a max-width 780px Box centered on page
2. Group related fields into FormCard sections with icons:
   - "Basic Information" (PersonOutlined icon)
   - "Additional Details" (InfoOutlined icon)
3. Use Grid container spacing={2.5} with Grid item xs={12} sm={6} for paired fields (name/email, etc.)
4. Add a sticky bottom action bar: position fixed, bottom 0, full width, white bg, border-top, Save+Cancel buttons
5. Keep all existing form state (setForm, handleSubmit, mutations) unchanged
[PASTE file]
```

---

## Module 8 — Detail Pages

### Files
- `ProjectDetail.jsx`, `TaskDetail.jsx`, `ClientDetail.jsx`, `DeveloperDetail.jsx`

### What to Upgrade
- **Hero header** — gradient banner with entity name, status badge, avatar/icon
- **Tabs** — Overview / Activity / Related items (Tasks/Projects)
- **Info grid** — key/value pairs in a 2-column Card grid
- **Action buttons** — Edit / Delete in a ButtonGroup at top-right

### Prompt 8 — Detail Pages
```
Upgrade [DetailPage].jsx — UI only, keep all queries, mutations, navigate, confirm, and tab state:
1. Add a hero header Card with gradient background (--gradient-primary):
   - Left: Avatar (icon or initials, white bg), entity name (h5), status Badge
   - Right: Edit Button, Delete IconButton
2. Below hero: MUI Tabs component (Overview | Tasks/Projects | Activity)
3. Overview tab: 2-column Grid of info Cards (label:value pairs)
4. Keep all existing data fetching, routing, and mutation handlers intact
[PASTE file]
```

---

## Module 9 — Reports

### File: `src/pages/reports/Reports.jsx`

### What to Upgrade
- Add **filter bar** (date range picker, project filter, client filter)
- Add **export button group** (PDF, CSV, Print)
- Add **summary KPI row** at top (Total Revenue, Avg Project Duration, On-Time Rate)
- Better chart presentation — add chart type toggles (Bar/Line)

### Prompt 9
```
Upgrade Reports.jsx — UI only, keep all chart data, queries, and calculation logic:
1. Add a filter bar Card at top: date range (2x DateField or TextField type=date), Project Select, Client Select, Apply Button
2. Add a summary KPI row (3 Cards) showing total projects, revenue, completion rate
3. Add chart type ToggleButtonGroup (Bar | Line) above the main chart
4. Add an export ButtonGroup: Export CSV, Export PDF (buttons only, onClick for now)
5. Keep all existing chart data, recharts config, and query logic unchanged
[PASTE Reports.jsx]
```

---

## Module 10 — Auth Pages

### Files
- `src/pages/auth/Login.jsx`
- `src/pages/auth/Signup.jsx`
- `src/pages/auth/ForgotPassword.jsx`
- `src/pages/auth/ResetPassword.jsx`

### What to Upgrade (already partially done in color migration)
- **Split-screen layout** — left 45% = gradient hero panel with logo + tagline, right 55% = form
- **Hero panel** — animated gradient, feature bullet points, company logo
- **Form panel** — clean Card-free form, large input fields, social proof
- **Micro-animations** — fade-in on mount

### Prompt 10
```
Upgrade Login.jsx — UI only, keep all form state, handleSubmit, useMutation, and navigate calls:
1. Root Box: display:flex, height:100vh
2. Left panel (45%, hidden on mobile): Box with background: var(--gradient-primary), white text
   - Logo/brand name at top
   - Headline: "Manage your agency smarter"
   - 3 bullet points with CheckCircle icons
   - Bottom tagline
3. Right panel (55%, full width mobile): centered Box maxW 400px with:
   - "Welcome back" Typography h4
   - Email + Password TextField (size=medium, fullWidth)
   - "Forgot Password?" link
   - Submit Button (fullWidth, variant=contained, size=large)
   - Sign up link
4. Keep all existing form logic, handleLogin mutation, and navigation unchanged
[PASTE Login.jsx]
```

---

## Module 11 — Notifications

### File: `src/pages/notifications/Notifications.jsx`

### What to Upgrade
- **Timeline layout** — MUI `Timeline` component (from `@mui/lab`) for feed items
- **Read/Unread** visual distinction — unread = bold + colored left border + light bg
- **Mark all read** button in header
- **Filter tabs** (All / Unread / Projects / Tasks)
- **Notification type icons** — color-coded by type

### Prompt 11
```
Upgrade Notifications.jsx — UI only, keep all queries and mutation (MARK_READ, MARK_ALL_READ):
1. Add filter Tabs above notification list: All | Unread | Projects | Tasks
2. Filter notifications client-side based on selected tab
3. Show unread items with: bgcolor '#F7F5FF', borderLeft '3px solid #8E43F0', fontWeight 600
4. Add "Mark all read" Button in PageHeader action slot
5. Replace flat list with timeline-style: TimelineItem-like layout using Box+Stack with dot indicator
6. Keep all existing query data, read/unread toggle mutations, and date logic unchanged
[PASTE Notifications.jsx]
```

---

## Module 12 — Settings & Profile

### Files
- `src/pages/settings/Settings.jsx`
- `src/pages/profile/Profile.jsx`

### What to Upgrade
- **Settings tabs** — Security / Preferences / Notifications / Saved Sites as MUI `Tabs`
- **Profile hero** — large Avatar with gradient ring, name, role, join date
- **Avatar upload** — click-to-upload with drag-drop zone, preview, crop hint

### Prompt 12
```
Upgrade Settings.jsx — UI only, keep all mutation calls (UPDATE_PROFILE, UPDATE_PASSWORD, UPDATE_PREFERENCES):
1. Add tabbed layout using MUI Tabs: Profile | Security | Preferences | Notifications
2. Show only the relevant form section based on selected tab
3. Profile tab: show Avatar (64px, gradient border), name, email fields
4. Security tab: show password change fields only
5. Notifications tab: show toggle switches only
6. Keep all existing state (profile, pwForm, notif, prefs) and mutation handlers unchanged
[PASTE Settings.jsx]
```

---

## Module 13 — Sites & WP Pages

### Files
- `src/pages/sites/Sites.jsx`
- `src/pages/sites/SiteDetail.jsx`
- `src/pages/sites/EditSite.jsx`

### What to Upgrade
- **Sites list** — health status color indicator (green/amber/red dot), PHP/WP version chips
- **SiteDetail** — hero with site URL, health score gauge, tab navigation
- **Health indicators** — MUI `LinearProgress` with color-coded values
- **Plugin cards** grid with update badges

### Prompt 13
```
Upgrade Sites.jsx — UI only, keep all existing mockSites data, handlers, select/filter state:
1. Upgrade card view: add a colored health dot (green/amber/red) in top-right corner of each card
2. Show PHP version + WP version as outlined Chips in card footer
3. Add a "health score" LinearProgress bar per card (value=site.healthScore or calculated)
4. Add Last Checked timestamp with AccessTime icon, formatted as "X minutes ago"
5. Keep all existing grid/list toggle, filter, bulk-action, and dialog logic unchanged
[PASTE Sites.jsx]
```

---

## Module 14 — Empty States & NotFound

### Files
- `src/pages/NotFound.jsx`
- Zero-result states in DataTable (already partially done)

### What to Upgrade
- **NotFound page** — large illustrated 404 with Home + Back buttons
- **Empty DataTable** — icon + message + action button per page

### Prompt 14
```
Rewrite NotFound.jsx — add a professional 404 page:
1. Centered Box (full viewport height, flex column, alignItems center, justifyContent center)
2. Large "404" Typography (fontSize 8rem, fontWeight 800, background gradient clip text)
3. Subtitle "Page not found" and body text
4. Two buttons: "Go Home" (contained, navigate('/dashboard')) and "Go Back" (outlined, navigate(-1))
5. Keep all existing routing props if any
[Current file contents minimal — full rewrite OK]
```

---

## Module 15 — Mobile & Tablet Responsive ⭐ HIGH IMPACT

> [!IMPORTANT]
> Mobile is a FIRST-CLASS experience — not just scaled-down desktop.
> Target: 360px minimum width, smooth 60fps interactions, touch-friendly tap targets (≥44px).

### Breakpoint Strategy
```
xs  → 0–599px     (mobile portrait)
sm  → 600–899px   (mobile landscape / small tablet)
md  → 900–1199px  (tablet portrait)
lg  → 1200px+     (desktop)
```

### Global Mobile Rules (apply to ALL modules)

```
For EVERY page/component, ensure:
1. No horizontal overflow — add overflow-x: hidden to root Box
2. All padding: { xs: 2, sm: 3, md: 4 } — never hardcoded px values
3. All Card borderRadius: { xs: 2, sm: 3 }
4. Typography sizes: h4 → variant="h5" on mobile, h5 → variant="h6"
5. Buttons: fullWidth on xs, auto on sm+
6. Grid spacing: { xs: 2, sm: 2.5, md: 3 }
7. Hide DataTable on xs — show Card list instead
8. Touch target minimum: minHeight: 44px on all clickable elements
```

---

### 15A — Bottom Navigation Bar (Mobile Only)

**File:** `src/components/saas/SaasSidebar.jsx` or new `src/components/saas/MobileBottomNav.jsx`

**What:** On mobile (`xs/sm`), hide the sidebar completely. Show a fixed bottom nav bar with 5 key items.

```
Bottom Nav items:
  Dashboard (Home icon)
  Projects (FolderOpen icon)
  Tasks (Assignment icon)
  Notifications (Notifications icon, with Badge for unread)
  More (MoreHoriz → opens bottom drawer with full nav)
```

**MUI Components:** `BottomNavigation`, `BottomNavigationAction`, `Badge`, `SwipeableDrawer`

### Prompt 15A — Bottom Nav
```
Create MobileBottomNav.jsx — new component, no business logic:
1. Use MUI BottomNavigation + BottomNavigationAction with 5 items:
   Home(/dashboard), Projects(/projects), Tasks(/tasks), Notifications(/notifications), More
2. Position: fixed, bottom: 0, width: '100%', zIndex: 1300, display: { xs:'flex', md:'none' }
3. Add Badge on Notifications item showing unread count from props
4. "More" action opens a SwipeableDrawer (anchor=bottom) containing a List with all remaining nav items
5. Use useNavigate and useLocation to set active state
6. Add to DashboardLayout.jsx inside a Box display={{ xs:'block', md:'none' }}
```

---

### 15B — Dashboard Mobile

**File:** `src/pages/dashboard/Dashboard.jsx`

| Element | Desktop | Mobile |
|---|---|---|
| Stats row | 4 columns | 2×2 grid (xs=6) |
| Charts | 70/30 split | Full width stacked |
| Recent Projects | Table-like list | Swipeable cards |
| Quick Actions | 4 button grid | 2×2 compact grid |

### Prompt 15B — Dashboard Mobile
```
Add mobile responsiveness to Dashboard.jsx — UI only:
1. Stats Grid: xs=6 sm=6 lg=3 (2-column on mobile)
2. Charts Grid: xs=12 lg=8 (full width on mobile, stacked)
3. Recent Projects items: on xs, hide the progress% text, increase padding
4. Quick Actions: Grid item xs=6 (2 per row on mobile)
5. Add paddingBottom: { xs: 10, md: 0 } to root Box (space for bottom nav)
[PASTE Dashboard.jsx]
```

---

### 15C — Projects / Tasks / Clients Mobile

**Files:** `Projects.jsx`, `Tasks.jsx`, `Clients.jsx`

| Element | Desktop | Mobile |
|---|---|---|
| View toggle | Table/Grid | Default to Card (hide table toggle) |
| DataTable | Full columns | Hide on xs — show Card stack |
| Filter chips | Full row | Horizontally scrollable (`overflowX: auto`, `flexWrap: nowrap`) |
| Action buttons | Icon buttons visible | Collapsed into `...` IconButton Menu |
| PageHeader | Title + button | Title only + FAB (Floating Action Button) |

### Prompt 15C — List Pages Mobile
```
Add mobile responsiveness to [Projects/Tasks/Clients].jsx — UI only:
1. Filter chips: wrap in Box sx={{ overflowX:'auto', pb:0.5, display:'flex', gap:1, flexWrap:'nowrap' }}
2. On xs: hide DataTable (display:{xs:'none', sm:'block'}), show Card stack (display:{xs:'block', sm:'none'})
3. Card stack for xs: compact Card per row with name, status badge, due date, actions menu
4. Replace PageHeader action Button with Fab sx={{ position:'fixed', bottom:80, right:16, display:{sm:'none'} }}
5. Add pb: { xs:12, sm:0 } to root Box
[PASTE file]
```

---

### 15D — Forms Mobile (Create/Edit)

**Files:** All Create/Edit forms

| Element | Desktop | Mobile |
|---|---|---|
| 2-column Grid | sm=6 per field | xs=12 (single column) |
| Sticky bottom bar | Fixed bar | Stays fixed, but taller tap targets |
| FormCard sections | Side by side | Full width stacked |
| Date/Select fields | Standard | fullWidth always |

### Prompt 15D — Forms Mobile
```
Add mobile responsiveness to [FormName].jsx — UI only:
1. All Grid items: xs=12 sm=6 (already single column on mobile)
2. Sticky action bar: height: { xs:64, sm:56 }, Button size="large" on xs
3. Max-width container: maxWidth: { xs:'100%', sm:780 }, px: { xs:2, sm:3 }
4. FormCard sections: remove any min-width constraints
5. Add paddingBottom: { xs:10, sm:4 } to root Box
[PASTE file]
```

---

### 15E — Detail Pages Mobile

**Files:** `ProjectDetail.jsx`, `TaskDetail.jsx`, etc.

| Element | Desktop | Mobile |
|---|---|---|
| Hero header | Horizontal layout | Vertical stacked |
| Tab labels | Full text | Icon only on xs |
| Info grid | 2 columns | 1 column |
| Action buttons | Top right | Bottom sticky bar |

### Prompt 15E — Detail Pages Mobile
```
Add mobile responsiveness to [DetailPage].jsx — UI only:
1. Hero card: flexDirection: { xs:'column', sm:'row' }, textAlign: { xs:'center', sm:'left' }
2. Tab labels: on xs show icon only using iconPosition="start" and hide label with display:{xs:'none',sm:'inline'}
3. Info Grid: item xs=12 sm=6 (single column on mobile)
4. Action buttons (Edit/Delete): move to sticky bottom bar on xs:
   Box sx={{ display:{xs:'flex',sm:'none'}, position:'fixed', bottom:64, left:0, right:0, ... }}
5. Add paddingBottom: { xs:16, sm:4 } to root Box
[PASTE file]
```

---

### 15F — Auth Pages Mobile

**Files:** `Login.jsx`, `Signup.jsx`, `ForgotPassword.jsx`

| Element | Desktop | Mobile |
|---|---|---|
| Split screen | 45/55 | Left panel hidden, full form |
| Form container | maxW 400px | Full width with px:3 |
| Logo/Brand | Left hero panel | Top of form, centered |

### Prompt 15F — Auth Pages Mobile
```
Add mobile responsiveness to Login.jsx — UI only:
1. Left hero panel: display: { xs:'none', md:'flex' }
2. Right form panel: flex:1, minWidth:0, width:'100%'
3. Form container: maxWidth: { xs:'100%', sm:440 }, px: { xs:3, sm:4 }
4. Add logo/brand at top of right panel on mobile: display:{ xs:'block', md:'none' }
5. Submit button: fullWidth always, size="large"
[PASTE Login.jsx]
```

---

### 15G — Sidebar & Navbar Mobile

**Files:** `SaasSidebar.jsx`, `SaasNavbar.jsx`, `DashboardLayout.jsx`

| Element | Desktop | Mobile |
|---|---|---|
| Sidebar | Permanent left drawer | Hidden (replaced by bottom nav) |
| Navbar | Full app bar | Compact: logo + hamburger |
| Hamburger | Not needed | Opens SwipeableDrawer with full sidebar |

### Prompt 15G — Layout Mobile
```
Add mobile responsiveness to DashboardLayout.jsx — UI only:
1. Sidebar: show permanent drawer on md+, hide on xs/sm (display:{xs:'none', md:'block'})
2. Add hamburger IconButton in Navbar on xs/sm that toggles a SwipeableDrawer
3. SwipeableDrawer on mobile: contains full SaasSidebar content (just render SaasSidebar inside)
4. Main content: marginLeft:{xs:0, md:`${DRAWER_WIDTH}px`}
5. Add paddingBottom: {xs:8, md:0} to page content area (space for bottom nav)
Keep all existing auth checks, route guards, and layout state unchanged.
[PASTE DashboardLayout.jsx and SaasNavbar.jsx]
```

---

### 15H — Tablet (md breakpoint) Specifics

Tablet gets a hybrid experience — sidebar collapses to icon-only mini rail:

```
- Sidebar width: md → 72px (icons only, no labels)
- Labels visible on hover tooltip (Tooltip wrapping each ListItem)
- Main content: marginLeft 72px on md, 260px on lg
- Charts: side by side (lg), stacked (md stays same as mobile for charts)
- Stats: 2-column on md (sm=6), 4-column on lg
```

### Prompt 15H — Tablet Mini Sidebar
```
Add tablet mini-sidebar to SaasSidebar.jsx — UI only:
1. Add a prop or useMediaQuery to detect md breakpoint
2. On md: DRAWER_WIDTH = 72 (vs 260 on lg)
3. On md: hide ListItemText within sidebar (display:{xs:'none', lg:'block'})
4. On md: add Tooltip title={item.label} placement="right" wrapping ListItemButton
5. Center the icon in the narrower sidebar: justifyContent:'center'
6. Keep all routing, auth, and active-state logic unchanged
[PASTE SaasSidebar.jsx]
```

---

## Module 16 — Date Format Audit: DD/MM/YYYY

> [!NOTE]
> `src/utils/dates.js → fmtDate()` already returns DD/MM/YYYY ✅
> `toInputDate()` converts DD/MM/YYYY → YYYY-MM-DD for HTML date inputs ✅

### Rules
1. **Always use `fmtDate(value)`** from `../../utils/dates` for any date display
2. **Never** use `new Date().toLocaleDateString()` or `.toISOString().slice(0,10)` in rendered output
3. **HTML date inputs** (`<TextField type="date" />`) — store value as YYYY-MM-DD internally but display/label always implies DD/MM/YYYY
4. **Relative dates** ("2 days ago") — use `fmtDate()` as tooltip; relative label as display text

### Files to Audit

| File | Check |
|---|---|
| `Dashboard.jsx` | fmtDate() used ✅ — verify all due_date renders |
| `Projects.jsx` | `row.due` uses fmtDate ✅ |
| `Tasks.jsx` | Check all due_date column renders |
| `Clients.jsx` | Check created_at, contract dates |
| `Developers.jsx` | Check joined_at, last_active |
| `Reports.jsx` | Check chart X-axis date labels |
| `Notifications.jsx` | Check notification timestamps |
| `SiteDetail.jsx` | Check "last updated", "last checked" |
| `ProjectDetail.jsx` | Check all date fields in overview tab |
| `TaskDetail.jsx` | Check created_at, updated_at, due_date |

### Prompt 16 — Date Format Audit (run per file)
```
Audit [FileName].jsx for date display issues:
1. Find every place a date value is rendered to the UI
2. Ensure each one uses: import { fmtDate } from '../../utils/dates'; fmtDate(value)
3. Replace any .toLocaleDateString(), .slice(0,10), new Date(...).toString() patterns
4. For HTML date input fields (TextField type="date"): store as YYYY-MM-DD internally
   but label it clearly as DD/MM/YYYY format in the placeholder or helper text
5. For "X days ago" relative timestamps: add Tooltip title={fmtDate(date)} so full date shows on hover
6. Keep all date arithmetic and business logic unchanged (comparisons stay in ISO format)
[PASTE file]
```

### Quick Fix Script (Module 16 — run in PowerShell to scan)
```powershell
# Find all non-fmtDate date renders in src/
Get-ChildItem "d:\test-website\ai-agent\ai-agent-wp\src" -Recurse -Include "*.jsx","*.js" |
  Select-String -Pattern "toLocaleDateString|toISOString.*slice|new Date\(.*\)\.toString" |
  Where-Object { $_.Filename -notmatch "dates.js|node_modules" } |
  ForEach-Object { Write-Host "$($_.Filename):$($_.LineNumber) → $($_.Line.Trim())" }
```

---

## Module 17 — Navigation Layout Toggle + Collapsible Sidebar ⭐

> [!IMPORTANT]
> Stored in `localStorage` so the user's preferred layout survives page refresh.
> No backend API needed — pure client-side preference state.

### What this adds

| Feature | Description |
|---|---|
| **Left ↔ Top toggle** | Switch between left sidebar and top horizontal navigation bar |
| **Collapsible sidebar** | Left sidebar shrinks to 72px icon-only rail (click toggle or hover to expand) |
| **Persistent preference** | Layout mode saved to `localStorage('navLayout')` = `'left'`\|`'top'` and `localStorage('sidebarCollapsed')` = `true`\|`false` |
| **Settings UI** | Toggle control in Settings → Preferences tab |

---

### 17A — Layout State Manager

**New File:** `src/contexts/LayoutContext.jsx`

Create a React context that holds and persists `navLayout` and `sidebarCollapsed` state:

```js
// LayoutContext.jsx
export const LayoutContext = createContext();
export function LayoutProvider({ children }) {
  const [navLayout, setNavLayout] = useState(
    () => localStorage.getItem('navLayout') ?? 'left'
  );
  const [collapsed, setCollapsed] = useState(
    () => localStorage.getItem('sidebarCollapsed') === 'true'
  );
  const toggleNav   = (v) => { setNavLayout(v); localStorage.setItem('navLayout', v); };
  const toggleSidebar = () => { setCollapsed(p => { const n=!p; localStorage.setItem('sidebarCollapsed', n); return n; }); };
  return (
    <LayoutContext.Provider value={{ navLayout, collapsed, toggleNav, toggleSidebar }}>
      {children}
    </LayoutContext.Provider>
  );
}
export const useLayout = () => useContext(LayoutContext);
```

Wrap `App.jsx` in `<LayoutProvider>` (no API calls involved).

### Prompt 17A — Layout Context
```
Create src/contexts/LayoutContext.jsx:
1. useState for navLayout (default: localStorage.getItem('navLayout') ?? 'left')
2. useState for collapsed (default: localStorage.getItem('sidebarCollapsed') === 'true')
3. toggleNav(value) — sets state + localStorage.setItem('navLayout', value)
4. toggleSidebar() — toggles collapsed + saves to localStorage
5. Export LayoutProvider and useLayout hook
6. Wrap App.jsx root with <LayoutProvider> (no routes or business logic change needed)
```

---

### 17B — Collapsible Left Sidebar

**File:** `src/components/saas/SaasSidebar.jsx`

| State | Width | Content |
|---|---|---|
| Expanded (default) | 260px | Logo + nav labels + section headers + user card |
| Collapsed | 72px | Icons only, no labels, no section headers |
| Hover (collapsed) | Re-expands to 260px via CSS transition |

```
Key implementation:
- useLayout() to read { collapsed, toggleSidebar }
- DRAWER_WIDTH = collapsed ? 72 : 260 (dynamic export)
- ListItemText: display: collapsed ? 'none' : 'block'
- Section headers: display: collapsed ? 'none' : 'block'
- User card at bottom: show Avatar only when collapsed
- Add collapse toggle button at top: ChevronLeft (expanded) / ChevronRight (collapsed)
- CSS transition: width 0.25s ease on the Drawer paper
```

### Prompt 17B — Collapsible Sidebar
```
Upgrade SaasSidebar.jsx — UI only, keep all nav items, routing, auth logic:
1. Import useLayout from LayoutContext. Read { collapsed, toggleSidebar }.
2. Make DRAWER_WIDTH dynamic: const WIDTH = collapsed ? 72 : 260;
3. Add collapse toggle IconButton at top of sidebar:
   <IconButton onClick={toggleSidebar} size="small">
     {collapsed ? <ChevronRight /> : <ChevronLeft />}
   </IconButton>
4. Hide ListItemText when collapsed: sx={{ display: collapsed ? 'none' : 'block' }}
5. Hide section header Typography when collapsed: sx={{ display: collapsed ? 'none' : 'block' }}
6. User card: when collapsed show just Avatar, when expanded show full name+role
7. Add transition: 'width 0.25s ease' to the Drawer paper sx
8. Keep all useAuth, useLocation, role-based filtering unchanged
[PASTE SaasSidebar.jsx]
```

---

### 17C — DashboardLayout responsive to collapse

**File:** `src/layouts/DashboardLayout.jsx`

Main content `marginLeft` must respond to sidebar collapse state:

```js
const { navLayout, collapsed } = useLayout();
const leftWidth = collapsed ? 72 : 260;

// In main content Box:
ml: navLayout === 'left' ? { md: `${leftWidth}px` } : 0
```

### Prompt 17C — DashboardLayout
```
Upgrade DashboardLayout.jsx — UI only, keep all outlet/route logic:
1. Import useLayout. Read { navLayout, collapsed }.
2. Compute sidebarWidth = collapsed ? 72 : 260
3. Render <SaasSidebar> only when navLayout === 'left'
4. Render <SaasTopNav> only when navLayout === 'top' (see 17D)
5. Main Box marginLeft: navLayout==='left' ? { md: `${sidebarWidth}px` } : 0
6. Keep mobileOpen, setMobileOpen, Outlet, Toolbar spacer unchanged
[PASTE DashboardLayout.jsx]
```

---

### 17D — Top Navigation Bar Mode

**New File:** `src/components/saas/SaasTopNav.jsx`

When `navLayout === 'top'`:
- Full-width AppBar (no left offset)
- Logo on left → horizontal nav items in center → notifications + avatar on right
- Dropdowns per nav group using MUI `Menu`
- Active item gets underline indicator (3px border-bottom, `primary.main`)

```
Top nav structure:
  [Logo]  [Dashboard]  [Projects ▾]  [Tasks ▾]  [Clients ▾]  [WP Sites ▾]  [Reports]     [🔔][Avatar]

Dropdown menus:
  Projects ▾  → Projects list / Create Project
  Tasks ▾     → Tasks list / Create Task
  Clients ▾   → Clients / Developers
  WP Sites ▾  → Sites / Work Queue / Security
```

### Prompt 17D — Top Nav
```
Create SaasTopNav.jsx — new component, UI only, keep all nav paths, role checks, logout:
1. AppBar position=fixed, width=100%, no left margin/offset
2. Toolbar: Logo (Typography variant=h6 fontWeight=800) on left
3. Center: Box with horizontal Button row for main nav groups
   Use useLocation to detect active path → show primary.main underline
4. Groups with sub-items: Button with KeyboardArrowDown + MUI Menu on click
   - Projects: [Projects List, Create Project]
   - Tasks: [Tasks List, Create Task]
   - Clients: [Clients, Developers]
   - WP Sites: [Sites, Work Queue, Security]
5. Right: existing notification bell + avatar menu (copy from SaasNavbar)
6. All navigate() calls and role checks same as sidebar
Do NOT import useLayout inside this file — layout decision is in DashboardLayout.
```

---

### 17E — Toggle Control in Settings

**File:** `src/pages/settings/Settings.jsx` → Preferences tab

Add two new UI-only controls to the Preferences section (no API call — localStorage only):

```jsx
// Navigation Layout toggle
<Typography variant="body2" fontWeight={600}>Navigation Layout</Typography>
<ToggleButtonGroup
  value={navLayout}
  exclusive
  onChange={(_, v) => v && toggleNav(v)}
  size="small"
>
  <ToggleButton value="left">Left Sidebar</ToggleButton>
  <ToggleButton value="top">Top Bar</ToggleButton>
</ToggleButtonGroup>

// Sidebar collapse toggle (only shows when navLayout === 'left')
<FormControlLabel
  control={<Switch checked={collapsed} onChange={toggleSidebar} />}
  label="Compact sidebar (icon-only)"
/>
```

### Prompt 17E — Settings Preference Controls
```
Add nav layout preferences to Settings.jsx Preferences tab — UI only:
1. Import useLayout from LayoutContext. Read { navLayout, toggleNav, collapsed, toggleSidebar }.
2. In Preferences FormCard, add a new section "Navigation" below existing fields:
   - ToggleButtonGroup (value=navLayout, exclusive) with "Left Sidebar" | "Top Bar" options
   - FormControlLabel Switch: "Compact sidebar (icon-only)" → checked=collapsed, onChange=toggleSidebar
   - Only show the Switch when navLayout === 'left'
3. Add note: these preferences are saved locally and take effect immediately
4. Keep all existing preferences state, mutation logic, and save handler unchanged
[PASTE Settings.jsx]
```

---

### 17F — Navbar adjust for layout mode

**File:** `src/components/saas/SaasNavbar.jsx`

When `navLayout === 'left'`: AppBar has left offset = sidebar width (existing behavior)
When `navLayout === 'top'`: AppBar is hidden (SaasTopNav replaces it entirely)

```js
// In DashboardLayout — render conditionally:
{navLayout === 'left' && <SaasNavbar ... />}
{navLayout === 'top'  && <SaasTopNav />}
```

---

### Layout Toggle Summary

```
LayoutContext (new)
  ├── navLayout: 'left' | 'top'    → localStorage('navLayout')
  └── collapsed: boolean           → localStorage('sidebarCollapsed')

DashboardLayout (modified)
  ├── navLayout==='left'  → <SaasNavbar> + <SaasSidebar> (260px or 72px)
  └── navLayout==='top'   → <SaasTopNav> full width, no sidebar

SaasSidebar (modified)
  ├── Expanded (260px): full labels, section headers, user card
  └── Collapsed (72px): icons only, Tooltip labels, toggle button

SaasTopNav (new)
  └── Horizontal nav bar with dropdowns, role-filtered items

Settings → Preferences (modified)
  └── ToggleButtonGroup + Switch for nav preference
```

---

## Module 18 — API Integration Audit

> [!NOTE]
> CRITICAL RULE: Do NOT change any API calls, queries, or mutations.
> This module ONLY identifies gaps, documents what's wired up, and flags missing coverage.

### Current API Map — What's Already Integrated

#### GraphQL Queries (`src/graphql/queries.js`)
| Query | Used In | Fields |
|---|---|---|
| `GET_ME` | Settings, AuthContext | id, name, email, role, company, phone, bio, avatar_url, last_login_at, user_prefs |
| `LOGIN` | Login.jsx | token, user |
| `LOGOUT` | AuthContext | status, message |
| `REGISTER` | Signup.jsx | token, user |
| `GET_DASHBOARD_STATS` | Dashboard.jsx | total_projects, active_projects, total_clients, open_tasks, monthly_revenue |
| `GET_PROJECTS` | Projects.jsx, Dashboard.jsx | id, name, status, progress, budget, due_date, client, developers |
| `GET_TASKS` | Tasks.jsx, Dashboard.jsx | id, title, status, priority, due_date, project, assignee |
| `GET_CLIENTS` | Clients.jsx, ProjectCreate | id, name, company, email, phone, status |
| `GET_DEVELOPERS` | Developers.jsx, ProjectCreate | id, name, email, avatar_url, role |
| `GET_NOTIFICATIONS` | SaasNavbar.jsx (poll 60s) | id, message, type, read_at, created_at |
| `GET_WP_SITES` | Sites.jsx | id, url, status, php_version, wp_version |
| `GET_WP_SITE` | SiteDetail.jsx | full site object |
| `GET_WP_CLIENTS` | EditSite.jsx | clients list |

#### GraphQL Mutations (`src/graphql/mutations.js`)
| Mutation | Used In | Purpose |
|---|---|---|
| `FORGOT_PASSWORD` | ForgotPassword.jsx | Send reset email |
| `RESET_PASSWORD` | ResetPassword.jsx | Reset with token |
| `UPDATE_PROFILE` | Settings.jsx | name, email, phone |
| `UPDATE_PASSWORD` | Settings.jsx | Change password |
| `UPDATE_PREFERENCES` | Settings.jsx | timezone, language, theme, notifications |
| `CREATE_PROJECT` | Projects.jsx | Create project |
| `UPDATE_PROJECT` | Projects.jsx, ProjectEdit | Edit project |
| `DELETE_PROJECT` | Projects.jsx | Delete project |
| `CREATE_TASK` | Tasks.jsx | Create task |
| `UPDATE_TASK` | Tasks.jsx, TaskEdit | Edit/status change |
| `DELETE_TASK` | Tasks.jsx | Delete task |
| `CREATE_CLIENT` | Clients.jsx | Create client |
| `UPDATE_CLIENT` | Clients.jsx, ClientEdit | Edit client |
| `DELETE_CLIENT` | Clients.jsx | Delete client |
| `MARK_NOTIFICATION_READ` | SaasNavbar.jsx | Mark one / all read |
| `UPDATE_WP_SITE` | EditSite.jsx | Edit site config |

---

### Gaps Identified — Missing API Coverage

| Gap | Where | Action Required |
|---|---|---|
| `GET_PROJECT` (single) | ProjectDetail.jsx | Check if it uses GET_PROJECTS filtered or dedicated query |
| `GET_TASK` (single) | TaskDetail.jsx | Check if dedicated query exists |
| `GET_CLIENT` (single) | ClientDetail.jsx | Check if dedicated query exists |
| `GET_DEVELOPER` (single) | DeveloperDetail.jsx | Check if dedicated query exists |
| `GET_NOTIFICATIONS` refetch | Notifications.jsx | Verify MARK_ALL_READ mutation exists |
| `GET_REPORTS` / aggregates | Reports.jsx | Check if Reports uses GET_DASHBOARD_STATS or separate query |
| `UPLOAD_AVATAR` | Profile.jsx | Avatar upload mutation — check if wired |
| `CREATE_DEVELOPER` / `UPDATE_DEVELOPER` | Developers.jsx | Verify mutations exist in mutations.js |
| `CREATE_USER` / `UPDATE_USER` | Users.jsx | Verify mutations exist |
| `DELETE_DEVELOPER` | Developers.jsx | Verify exists |
| `DELETE_USER` | Users.jsx | Verify exists |
| nav preference `UPDATE_PREFERENCES` | Settings.jsx | `theme` field used for layout? Or localStorage-only |

### Prompt 18 — API Audit (run per file pair)
```
Audit [PageName].jsx and graphql/queries.js + mutations.js:
1. List every useQuery() and useMutation() call in the page
2. Confirm each query/mutation is defined in queries.js or mutations.js
3. Confirm the GraphQL fields requested match what the UI actually displays
4. Identify any UI elements that need data not currently fetched (e.g., avatar_url displayed but not in query)
5. Flag any hardcoded mock data that should be replaced with real API calls
6. Do NOT change any existing queries or mutations
Report: ✅ (wired) / ⚠️ (partial) / ❌ (missing) per feature
```

### PowerShell Scan — Find Hardcoded Mock Data
```powershell
# Find mock/static data arrays in src/pages
Get-ChildItem "d:\test-website\ai-agent\ai-agent-wp\src\pages" -Recurse -Include "*.jsx" |
  Select-String -Pattern "const mock|mockData|\[\{.*id:.*name:" -CaseSensitive |
  ForEach-Object { Write-Host "$($_.Filename):$($_.LineNumber) → $($_.Line.Trim())" }
```

### UPDATE_PREFERENCES Coverage for Nav Layout

The `UPDATE_PREFERENCES` mutation accepts a `theme` field — **DO NOT hijack it for nav layout**.
`navLayout` and `sidebarCollapsed` are **localStorage-only** preferences (Module 17).
If backend persistence of nav layout is needed in future, a new `user_prefs` JSON field update should be added.

---

## MUI Components to Use Per Module

| Pattern | MUI Component |
|---|---|
| Tabs/panels | `Tabs`, `Tab`, `Box` (conditional render) |
| Status dots | `Badge`, custom `Box` with border-radius 50% |
| Avatar + group | `Avatar`, `AvatarGroup` |
| Hero header | `Card`, `Box`, `LinearGradient` via sx |
| Wizard steps | `Stepper`, `Step`, `StepLabel` |
| Timeline feed | `Stack` + dot `Box` (avoid @mui/lab dependency) |
| Filter bar | `Chip` with `onClick` toggle |
| View toggle | `ToggleButtonGroup`, `ToggleButton` |
| Sticky footer | `Box` with `position: sticky, bottom: 0` |
| Glassmorphism | `backdropFilter: 'blur(12px)'` in sx |
| Gradient text | `background: gradient, WebkitBackgroundClip: 'text'` |
| Micro-animation | `transition: 'all 0.2s'` + hover transform |
| Progress ring | `CircularProgress variant="determinate"` |
| Export CSV | Build blob URL client-side, no new dependency |

---

## Build & Deploy After Each Module

```powershell
$env:NODE_OPTIONS="--max-old-space-size=4096"
npm run build
docker cp "d:\test-website\ai-agent\ai-agent-wp\dist\." ai_agent_frontend:/usr/share/nginx/html/
docker exec ai_agent_frontend nginx -s reload
```
