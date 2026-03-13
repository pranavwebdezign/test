# AI Agent Platform — Full Integration Project Plan

> **Last updated:** 2026-03-10 (Phases 1–3 complete)  
> **Stack:** Laravel 11 GraphQL backend · React/Vite + MUI frontend · MySQL + Redis · Docker  
> **Base URL:** `http://localhost:3000` (frontend) · `http://localhost:8000/graphql` (API)

---

## 🏗️ UX Architecture — No Dialog Policy (applies to ALL phases)

> **Decision:** All `<Dialog>` popups have been replaced with dedicated pages.

| Action | Old | New |
|--------|-----|-----|
| Create entity | `<Dialog>` popup | Navigate to `/entity/create` (full page) |
| Edit entity | `<Dialog>` popup | Navigate to `/entity/:id/edit` (full page) |
| Edit section on detail page | N/A | Per-section `<Edit>` icon → inline edit mode |

**Every Create/Edit page must have:**
- `PageHeader` with breadcrumbs
- **Back** / **Cancel** button and **Save Changes** button with loading spinner
- Form fields grouped in `<Card>` sections
- Toast notification on success/error

**Detail pages** (ProjectDetail, ClientDetail, DeveloperDetail):
- Each card section has an `<Edit>` icon → activates inline edit mode
- Save applies the mutation; Cancel reverts

**New routes to add to App.jsx:**
```
/projects/create, /projects/:id/edit
/tasks/create,    /tasks/:id/edit
/clients/create,  /clients/:id/edit
/developers/create, /developers/:id/edit
/users/create,    /users/:id/edit
```

---

## 📅 UX Standard — Date Format (applies to ALL phases & modules)

> **Rule:** Every date displayed in the UI **must** use `DD/MM/YYYY` format.

**How to apply:**
- Use the `fmtDate()` utility from `src/utils/dates.js` for **every** date value rendered to the screen
- This includes: `due_date`, `joined_at`, `created_at`, `started_at`, `completed_at`, `last_login_at`
- Applied across: Tasks, Projects, Clients, Users, Developers, Reports, Notifications

**Checklist — verified pages:**
| Page | Date fields | Uses fmtDate? |
|------|------------|--------------|
| Tasks.jsx | due_date | ✅ |
| Projects.jsx | due_date | ✅ |
| ProjectDetail.jsx | due_date, started_at | ✅ |
| Clients.jsx | joined_at | ✅ |
| ClientDetail.jsx | joined_at, project due_date | ✅ |
| Users.jsx | created_at, last_login_at | ✅ |
| DeveloperDetail.jsx | task due_date | ✅ |
| Dashboard.jsx | dates in recent activity | ⬜ check |
| Reports.jsx | all date ranges | ⬜ check |

---

## Overview

The platform has two sides:

| Side | Status |
|------|--------|
| **Auth** (Login/Logout) | ✅ Fully integrated |
| **WP Ops** (Sites, SiteDetail, WorkQueue, Findings, History, Security) | ✅ Fully integrated (real GraphQL) |
| **SaaS Dashboard** | ✅ Phase 1 complete — real GraphQL, skeletons, error states |
| **Projects + ProjectDetail** | ✅ Phase 2 complete — real GraphQL, CRUD mutations, DD/MM/YYYY dates, toasts |
| **ProjectCreate / ProjectEdit** | ✅ Phase 13 complete — dedicated full-page forms, no Dialog |
| **Tasks** | ✅ Phase 3 complete — real GraphQL, inline status, toasts |
| **TaskCreate / TaskEdit / TaskDetail** | ✅ Phase 13 complete — dedicated full-page forms + detail view |
| **Clients (SaaS)** | ✅ Phase 4 complete — real API wiring, dedicated Create/Edit/Detail pages |
| **Users** | ✅ Phase 5 complete — real API wiring, dedicated Create/Edit pages |
| **Developers** | ✅ Phase 6 complete — real API wiring, dedicated Create/Edit/Detail pages |
| **Profile** | ✅ Phase 7 complete — real API wiring, avatar upload fixed |
| **Reports** | ✅ Phase 8 complete — real aggregated data from 4 queries |
| **Notifications** | ✅ Phase 9 complete — real-time poll, mark read bell + page |
| **Register / ForgotPassword / ResetPassword** | ✅ Phase 10 complete — full backend mutations wired |
| **Avatar Upload** | ✅ Phase 11 complete — REST endpoint, correct auth token key |
| **Settings (SaaS)** | ✅ Phase 12 complete — fully wired to real mutations |

---

## Phase 1 — SaaS Dashboard Integration ✅ COMPLETE

**Effort:** ~2h | **Priority:** 🔴 High | **Status:** ✅ Done

### What was done
- Replaced all `mockData` in `Dashboard.jsx` with `useQuery(GET_DASHBOARD_STATS, GET_PROJECTS, GET_TASKS)`
- Added MUI Skeleton loading states and error alerts
- Fixed `ProjectsQuery.php` (removed bad `Auth::guard` call, fixed `scopeSearch`)
- Verified live data in browser

---

> **Next prompt:**
> ```
> Wire up the SaaS Dashboard page (src/pages/saas/Dashboard.jsx) to the real GraphQL API.
> Replace all mockData imports (mockProjects, mockTasks, mockClients, projectActivityData,
> revenueData) with Apollo useQuery calls using GET_DASHBOARD_STATS, GET_PROJECTS, and
> GET_TASKS from src/graphql/queries.js. Add loading skeletons (MUI Skeleton) and error
> states. Keep all existing chart components and layout intact.
> ```

---

## Phase 2 — Projects Page Integration ✅ COMPLETE

**Effort:** ~3h | **Priority:** 🔴 High | **Status:** ✅ Done

### What was done
- Rewrote `Projects.jsx` with `useQuery(GET_PROJECTS/GET_CLIENTS/GET_DEVELOPERS)` + all 3 mutations
- Rewrote `ProjectDetail.jsx` with `useQuery(GET_PROJECT)` by URL param ID
- Real UUID `client_id` passed to mutations (not name string)
- Created `SanctumOptional` middleware so `Auth::user()` works in all resolvers
- Fixed null-guard on `CreateProjectMutation`, `UpdatePasswordMutation`, `UpdateProfileMutation`
- Added `notistack` toasts (success/error/info) on all mutations
- Added `src/utils/dates.js` `fmtDate()` — all dates display as **DD/MM/YYYY**
- Backend Docker image permanently rebuilt

---

> **Next prompt:**
> ```
> Wire up the Projects page (src/pages/saas/Projects.jsx) and ProjectDetail page
> (src/pages/saas/ProjectDetail.jsx) to the GraphQL API. Replace all mockData with
> Apollo useQuery(GET_PROJECTS) and useQuery(GET_PROJECT). Connect the create, edit,
> and delete dialogs to useMutation(CREATE_PROJECT), useMutation(UPDATE_PROJECT),
> and useMutation(DELETE_PROJECT). The client dropdown in the dialog should use
> useQuery(GET_CLIENTS) to fetch real clients. After mutations, refetch the projects
> list. Add proper loading and error states throughout.
> ```

---

## Phase 3 — Tasks Page Integration ✅ COMPLETE

**Effort:** ~3h | **Priority:** 🔴 High

### What was done ✅
- Replaced `mockTasks`, `mockProjects`, `mockDevelopers` with real Apollo hooks
- `useQuery(GET_TASKS)` — **10 real tasks** confirmed from DB
- **Inline status `<Select>`** calls `useMutation(UPDATE_TASK)` immediately on change
- `GET_PROJECTS` and `GET_DEVELOPERS` for dropdowns (real data)
- `DELETE_TASK` with confirm dialog
- Toast notifications (success/error/info) + DD/MM/YYYY dates + loading skeletons
- Built and deployed to nginx container

---

## Phase 4 — Clients Page Integration ✅ COMPLETE

**Effort:** ~2h | **Priority:** 🟡 Medium | **Status:** ✅ Done

### What was done
- **Backend:** `ClientQuery.php` already existed + `client` registered in `graphql.php` ✅
- Added `projects` relation to `ClientType.php` (backend hot-patched)
- Added `GET_CLIENT` query to `queries.js` (includes projects list)
- Rewrote `Clients.jsx` — real `GET_CLIENTS`, no dialog, Add button → `/clients/create`, edit icon → `/clients/:id/edit`
- Created `ClientCreate.jsx` — full-page form, Back/Cancel/Save, calls `CREATE_CLIENT`
- Created `ClientEdit.jsx` — loads by ID, pre-fills form, calls `UPDATE_CLIENT`
- Rewrote `ClientDetail.jsx` — real `GET_CLIENT` with projects, per-section inline edit icons, clickable project rows
- Registered `/clients/create` and `/clients/:id/edit` routes in `App.jsx`
- Built and deployed (**7 real clients** confirmed via API)

---


## Phase 5 — Users Page Integration ✅ COMPLETE

**Effort:** ~2h | **Priority:** 🟡 Medium | **Status:** ✅ Done

### What was done
- **Backend:** Created `CreateUserMutation.php` — SuperAdmin only, `Hash::make`, email uniqueness check
- **Backend:** Created `UpdateUserMutation.php` — update name/email/role/status/company/phone by ID
- Registered `createUser` and `updateUser` in `config/graphql.php` (hot-patched to container)
- Added `CREATE_USER` and `UPDATE_USER` to `mutations.js`
- Rewrote `Users.jsx` — real `GET_USERS`, no dialog, inline Switch → `UPDATE_USER`, Add → `/users/create`, Edit → `/users/:id/edit`
- Created `UserCreate.jsx` — full-page form with password show/hide toggle, role dropdown, Back/Cancel/Save
- Created `UserEdit.jsx` — pre-fills from `GET_USERS` cache, all fields editable, Save → `UPDATE_USER`
- Registered `/users/create` and `/users/:id/edit` routes in `App.jsx`
- Built and deployed (**7 real users** confirmed via API)

---

## Phase 6 — Developers Page Integration ✅ COMPLETE

**Effort:** ~2h | **Priority:** 🟡 Medium

### What needs doing
Replace `mockDevelopers` with `GET_DEVELOPERS` query. DeveloperDetail page uses mock.

### Frontend changes
- Rewrote `Developers.jsx` — real `GET_DEVELOPERS`, no dialog, Add → `/developers/create`, Edit → `/developers/:id/edit`, skills from `bio` field
- Rewrote `DeveloperDetail.jsx` — real `GET_DEVELOPERS` + `GET_TASKS(assignee_id)`, profiles, skills chips, task stats, project list, task list with `fmtDate`
- Created `DeveloperCreate.jsx` — reuses `CREATE_USER` with `role=Developer`, password show/hide
- Created `DeveloperEdit.jsx` — loads from `GET_DEVELOPERS`, updates via `UPDATE_USER`
- Registered `/developers/create` and `/developers/:id/edit` routes in `App.jsx`
- Built and deployed (**7 real users in developers role** confirmed)

---

## Phase 7 — Profile Page Integration ✅ COMPLETE

**Effort:** ~2h | **Priority:** 🟡 Medium | **Status:** ✅ Done

### What was done
- Added `UPDATE_PROFILE` and `UPDATE_PASSWORD` to `mutations.js` with correct backend args
- **Bug fixed:** Removed duplicate `LOGIN`/`LOGOUT` exports from `mutations.js` (they live in `queries.js`) — this had been causing a Rollup duplicate-export build error
- Rewrote `Profile.jsx` — `useQuery(GET_ME)` populates form on load
- Save button calls `UPDATE_PROFILE(name, email, phone)` with toast on success/error
- Added **Change Password** card — `UPDATE_PASSWORD(current_password, new_password)` with show/hide fields and client-side validation (min 8 chars, confirm match)
- Reset button reverts form to last saved state from `GET_ME`
- Built and deployed ✅

---


## Phase 8 — Reports Page Integration ✅ COMPLETE

**Effort:** ~3h | **Priority:** 🟡 Medium | **Status:** ✅ Done

### What was done
- **Backend:** Added `completed_projects` (Int) and `done_tasks` (Int) to `DashboardStatsType.php` and `DashboardStatsQuery.php` (hot-patched to container)
- Updated `GET_DASHBOARD_STATS` in `queries.js` to include `completed_projects` and `done_tasks`
- Rewrote `Reports.jsx` — **zero mock data**:
  - `GET_DASHBOARD_STATS` → 4 stat cards (Total Revenue, Active Clients, Completed Projects, Tasks Done)
  - `GET_PROJECTS` → Project Status pie chart (grouped by status)
  - `GET_TASKS` → Task Priority Breakdown (Critical/High/Medium/Low bars)
  - `GET_CLIENTS` → Total revenue calculated client-side + client activity bar chart
- Loading skeletons across all charts and stat cards
- All dates use `DD/MM/YYYY` format ✅
- Built and deployed ✅

---


## Phase 9 — Notifications Integration ✅ COMPLETE

**Status:** ✅ Done
- Backend: `NotificationsQuery.php` + `NotificationType.php` created and registered
- `GET_NOTIFICATIONS` added to `queries.js` with 60s poll
- `SaasNavbar.jsx` — real bell popover: unread badge, mark-one/mark-all, View all link
- `Notifications.jsx` — full-page list with All/Unread/Read toggle, type chips, search, timeAgo
- Route `/notifications` + sidebar link added
- Built and deployed ✅

---

## Phase 10 — Auth Backend ✅ COMPLETE

**Status:** ✅ Done
- `RegisterMutation.php` — validates confirm, email uniqueness, creates user (role=Client), returns AuthPayload
- `ForgotPasswordMutation.php` — Laravel Password::sendResetLink() — always returns true (security)
- `ResetPasswordMutation.php` — Password::reset() with descriptive error messages for invalid/expired tokens
- All 3 registered in `graphql.php` (both default + public schemas)
- `FORGOT_PASSWORD` + `RESET_PASSWORD` added to `mutations.js`
- `ForgotPassword.jsx` + `ResetPassword.jsx` wired with real Apollo mutations
- `ResetPassword.jsx` reads `?token=&email=` from URL params (Laravel reset link format)
- Built and deployed ✅

---

## Phase 11 — Avatar Upload ✅ COMPLETE

**Status:** ✅ Done
- `AvatarController.php` — REST endpoint, validates 2MB JPG/PNG, stores in `storage/public/avatars/`, returns `avatar_url`
- `POST /api/avatar` route added to `api.php` (auth:sanctum)
- `ProfileImageUpload.jsx` extended with `onFileSelected(file)` prop
- `Profile.jsx` — `handleAvatarFile()` POSTs raw File to `/api/avatar` with Bearer token, instant dataURL preview
- Loading overlay during upload, success/error toast
- Built and deployed ✅

---

## Phase 12 — Settings Full Integration ✅ COMPLETE

**Status:** ✅ Done
- `Settings.jsx` fully rewritten — `GET_ME` populates all profile fields on load (name, email, company, phone, bio)
- Save Changes → `UPDATE_PROFILE(name, email, phone)` with loading spinner + toast
- Security section → real `UPDATE_PASSWORD` mutation with show/hide fields
- Preferences/Notifications sections remain local state (no backend needed)
- Built and deployed ✅

---

## Phase 13 — Post-Audit Gap Fixes ✅ COMPLETE

**Status:** ✅ Done | **Audit date:** 11/03/2026 — see full analysis in `project-plan_v2.md`

### What was done

**Gap 1 — No-Dialog Policy enforcement (Projects & Tasks)**
- Removed all `<Dialog>` popups from `Projects.jsx` and `Tasks.jsx`
- Created `ProjectCreate.jsx` — full-page form (name, client dropdown, developers chip selector, budget, due_date, status)
- Created `ProjectEdit.jsx` — pre-filled from `GET_PROJECT`, calls `UPDATE_PROJECT`, loading skeleton
- Created `TaskCreate.jsx` — full-page form (title, description, project, assignee, priority, status, due_date)
- Created `TaskEdit.jsx` — pre-filled from `GET_TASKS` cache, calls `UPDATE_TASK`
- Registered all new routes in `App.jsx`: `/projects/create`, `/projects/:id/edit`, `/tasks/create`, `/tasks/:id/edit`

**Gap 2 — ProjectDetail Edit button**
- Fixed `onClick={() => navigate('/projects')}` → `navigate('/projects/${id}/edit')`

**Gap 3 — TaskDetail page created**
- Created `TaskDetail.jsx` — description card, clickable status pipeline chips (inline `UPDATE_TASK`), comment input (UI ready), sticky sidebar with all task metadata
- Route `/tasks/:id` registered in `App.jsx`; task title in Tasks list now links to detail

**Gap 4 — Avatar upload token key mismatch**
- Fixed `Profile.jsx`: `localStorage.getItem('sanctum_token')` → `localStorage.getItem('auth_token')` (matches `AuthContext.TOKEN_KEY`)

- Built and deployed ✅

---

> mutations. Show loading spinners on test buttons and display success/error
> toast messages. Also ensure PORTAL_SETTINGS_QUERY is called on page load to
> populate all form fields with current values.
> ```

---

## Summary Table

| Phase | Page/Feature | FE Work | BE Work | Priority |
|-------|-------------|---------|---------|----------|
| 1 | Dashboard | Replace mock | None | 🔴 High |
| 2 | Projects + ProjectDetail | Replace mock + mutations | None | 🔴 High |
| 3 | Tasks | Replace mock + mutations | None | 🔴 High |
| 4 | Clients + ClientDetail | Replace mock + mutations | Add `client(id)` query | 🟡 Med |
| 5 | Users | Replace mock + mutations | Add create/update/status mutations | 🟡 Med |
| 6 | Developers + DeveloperDetail | Replace mock | None | 🟡 Med |
| 7 | Profile | Replace mock + mutations | None | 🟡 Med |
| 8 | Reports | Replace mock + queries | Extend dashboardStats | 🟡 Med |
| 9 | Notifications | Add query + bell wiring | Add notifications query | 🟢 Low |
| 10 | Register / ForgotPassword | Wire mutations | 3 new mutations | 🟢 Low |
| 11 | Avatar Upload | Wire file upload | Upload mutation + storage | 🟢 Low |
| 12 | Settings (SaaS) | Audit + wire test buttons | None | 🟢 Low |

---

## Backend Files to Create (Summary)

| File | Purpose |
|------|---------|
| `app/GraphQL/Queries/ClientQuery.php` | Single client by ID |
| `app/GraphQL/Mutations/CreateUserMutation.php` | Admin creates user |
| `app/GraphQL/Mutations/UpdateUserMutation.php` | Admin updates any user |
| `app/GraphQL/Queries/NotificationsQuery.php` | User's notifications list |
| `app/GraphQL/Mutations/RegisterMutation.php` | Public registration |
| `app/GraphQL/Mutations/ForgotPasswordMutation.php` | Send reset email |
| `app/GraphQL/Mutations/ResetPasswordMutation.php` | Reset password with token |
| `app/GraphQL/Mutations/UploadAvatarMutation.php` | Avatar file upload |

All created files must also be **registered in `config/graphql.php`** under the appropriate `queries` or `mutations` array.

---

## Recommended Execution Order

```
Phase 1 → Phase 2 → Phase 3   (Core SaaS — 1 week)
Phase 4 → Phase 5 → Phase 6   (People & Management — 1 week)
Phase 7 → Phase 8              (Profile & Reports — 3 days)
Phase 9 → Phase 10 → Phase 11 → Phase 12  (Polish — 1 week)
```

**Total estimated effort:** ~3 weeks (1 developer)

---

> **Next prompt (after all phases complete — final cleanup & production readiness):**
> ```
> The AI Agent platform integration is now complete. Perform the following final
> cleanup and production-readiness tasks:
>
> 1. DELETE src/data/saasData.js — all mock data is now replaced by real GraphQL.
>    Fix any remaining import errors.
>
> 2. Add a global Apollo error boundary — create src/components/saas/ApolloError.jsx
>    that intercepts 401 Unauthorized responses and calls logout() from AuthContext
>    to redirect to /login automatically.
>
> 3. Add Apollo InMemoryCache type policies for all paginated/list queries so that
>    refetchQueries works correctly (especially for GET_PROJECTS, GET_TASKS,
>    GET_CLIENTS, GET_USERS). Update src/lib/apolloClient.js.
>
> 4. Add a global loading indicator (MUI LinearProgress at the top of
>    DashboardLayout) that activates whenever any Apollo query or mutation is
>    in-flight, using Apollo's useApolloClient or a custom loading context.
>
> 5. Rebuild and redeploy the Docker containers:
>    docker compose up --build -d
>    Then test the full login → dashboard → CRUD flow end-to-end.
> ```
