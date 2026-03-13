# AI Agent Platform — Project Plan v2
> **Audit date:** 11/03/2026 | **Stack:** Laravel 11 + React/Vite + MUI + Apollo + Docker
> **Base URL:** `http://localhost:3000` (frontend) · `http://localhost:8000/graphql` (API)
> **Previous phases 1–12:** Completed — see `project-plan.md`

---

## ✅ What Is Actually Done (verified against code)

| Area | Status | Notes |
|------|--------|-------|
| Auth — Login / Logout | ✅ Live | Apollo, Sanctum, localStorage `auth_token` |
| Auth — Register | ✅ Live | `RegisterMutation.php` + `Signup.jsx` wired |
| Auth — Forgot/Reset Password | ✅ Live | `ForgotPasswordMutation.php`, `ResetPasswordMutation.php`, URL token |
| Dashboard | ✅ Live | Real `GET_DASHBOARD_STATS`, `GET_PROJECTS`, `GET_TASKS` |
| Projects list | ✅ Live | Dialogs removed — Add → `/projects/create`, Edit → `/projects/:id/edit` |
| ProjectCreate / ProjectEdit | ✅ Live | Dedicated full-page forms wired to `CREATE_PROJECT` / `UPDATE_PROJECT` |
| ProjectDetail | ✅ Live | Read-only view; Edit button correctly navigates to `/projects/:id/edit` |
| Tasks list | ✅ Live | Dialogs removed — Add → `/tasks/create`, Edit → `/tasks/:id/edit`, title → `/tasks/:id` |
| TaskCreate / TaskEdit | ✅ Live | Dedicated full-page forms wired to `CREATE_TASK` / `UPDATE_TASK` |
| TaskDetail | ✅ Live | Full detail page with status pipeline, description, sidebar metadata |
| Clients list + Create/Edit/Detail | ✅ Live | Real pages, dedicated routes |
| Users list + Create/Edit | ✅ Live | Real pages, dedicated routes |
| Developers list + Create/Edit/Detail | ✅ Live | Real pages, dedicated routes |
| Profile | ✅ Live | `GET_ME`, `UPDATE_PROFILE`, `UPDATE_PASSWORD`; avatar upload uses correct `auth_token` key |
| Avatar Upload | ✅ Live | REST `POST /api/avatar` via `AvatarController` |
| Reports | ✅ Live | Real aggregated data from 4 queries |
| Notifications (bell + page) | ✅ Live | 60s poll, mark read, type chips, search |
| Settings | ✅ Live | `GET_ME`, `UPDATE_PROFILE`, `UPDATE_PASSWORD` wired |
| WP Sites / SiteDetail / EditSite | ✅ Live | Existing real GraphQL integration |
| WP WorkQueue / Findings / History / Security | ✅ Live | Existing integration |

---

## ✅ Gap 1 — Projects & Tasks: No-Dialog Policy ✅ FIXED (11/03/2026)
**Was: High Priority**

### What Was Done
- Removed all `<Dialog>` from `Projects.jsx` and `Tasks.jsx`
- Created `ProjectCreate.jsx` — full-page form (name, client dropdown, developers chip selector, budget, due_date, status)
- Created `ProjectEdit.jsx` — pre-filled from `GET_PROJECT`, calls `UPDATE_PROJECT`, loading skeleton
- Created `TaskCreate.jsx` — full-page form (title, description, project, assignee, priority, status, due_date)
- Created `TaskEdit.jsx` — pre-filled from `GET_TASKS` cache by ID, calls `UPDATE_TASK`
- Registered all 4 routes in `App.jsx`: `/projects/create`, `/projects/:id/edit`, `/tasks/create`, `/tasks/:id/edit`
- `Projects.jsx`: Add → `navigate('/projects/create')`, Edit icon → `navigate('/projects/:id/edit')`
- `Tasks.jsx`: Add → `navigate('/tasks/create')`, Edit icon → `navigate('/tasks/:id/edit')`, title click → `navigate('/tasks/:id')`
- Built and deployed ✅

---

## ✅ Gap 2 — ProjectDetail: Edit Button ✅ FIXED (11/03/2026)
**Was: High Priority | 1-line fix**

`ProjectDetail.jsx` line 97: `navigate('/projects')` → fixed to `navigate('/projects/' + id + '/edit')`

---

## ✅ Gap 3 — No TaskDetail Page ✅ FIXED (11/03/2026)
**Was: Medium Priority**

- Created `TaskDetail.jsx` — description card, clickable status pipeline chips (inline `UPDATE_TASK`), comment input (UI ready, backend hookup pending), sticky sidebar with priority/status/assignee/project/due-date
- Route `/tasks/:id` registered in `App.jsx`

---

## ✅ Gap 4 — Avatar Token Key Mismatch ✅ FIXED (11/03/2026)
**Was: Medium Priority | 5-min fix**

`Profile.jsx` `handleAvatarFile`: changed `localStorage.getItem('sanctum_token')` → `localStorage.getItem('auth_token')` to match `AuthContext.TOKEN_KEY`.

---

## ✅ Gap 5 — Dashboard: Recent Activity Date Format ✅ FIXED (11/03/2026)
**Was: Medium Priority | Audit + minor enhancement**

### Audit Result
Dashboard had **no bare date strings displayed** — `due_date` was only used inside `buildActivityData()` for chart month-bucketing (via `new Date()`, never rendered to screen). The checklist concern was a false positive.

### Enhancement Applied
Since the Recent Projects and Recent Tasks cards showed no dates at all, added `fmtDate()` due date display to both cards:
- `Dashboard.jsx` — imported `fmtDate` from `../../utils/dates`
- **Recent Projects** row: `{p.client?.name} · Due {fmtDate(p.due_date)}` (only shown when `due_date` exists)
- **Recent Tasks** row: `{t.project?.name} · Due {fmtDate(t.due_date)}` (only shown when `due_date` exists)
- Built and deployed ✅

---

## ✅ Gap 6 — Settings: Notification Prefs & Timezone Persistence ✅ FIXED (11/03/2026)
**Was: Medium Priority | Option 2 implemented (proper DB column + mutation)**

### What Was Done
**Backend:**
- Migration `2026_03_11_000001_add_preferences_to_users_table.php` — adds nullable `user_prefs` JSON column to `users` table (column named `user_prefs` to avoid conflict with existing `preferences()` HasOne relationship method)
- `UpdatePreferencesMutation.php` — accepts 8 args (timezone, language, theme, email_notif, push_notif, task_updates, project_updates, weekly_report), non-destructively merges into `user_prefs` JSON column
- `UserType.php` — `user_prefs` String field exposed in GraphQL
- `config/graphql.php` — `updatePreferences` mutation registered
- `User.php` — `user_prefs` added to `$fillable` + `'array'` cast
- All files copied to container; migration ran successfully ✅

**Frontend:**
- `GET_ME` query updated to include `user_prefs` field
- `UPDATE_PREFERENCES` mutation added to `mutations.js`
- `Settings.jsx` — `useEffect` now parses `me.user_prefs` JSON on load and restores timezone, language, theme, and all 5 notification toggles
- `handleSave()` now calls both `updateProfile` and `updatePreferences` simultaneously
- Save Changes button spinner covers both mutations
- Built and deployed ✅

---

## ✅ Gap 7 — Sites `/sites/create` Route ✅ FIXED (11/03/2026)
**Was: Medium Priority**

### What Was Done
- `App.jsx` — added `<Route path="/sites/create" element={<Navigate to="/sites?create=1" replace />} />` before `sites/:id`
- `Sites.jsx` — added `useSearchParams` + `useEffect`: when `?create=1` param is present the Add Site dialog opens automatically and the param is cleared from the URL
- Result: any navigation to `/sites/create` now opens the Sites page with the Add Site dialog pre-opened
- Built and deployed ✅

## ✅ Gap 8 — Reports Date Range Filter ✅ FIXED (11/03/2026)
**Was: Medium Priority | Client-side filtering approach (no backend changes needed)**

### What Was Done
- `Reports.jsx` rewritten with date range toolbar: **preset buttons** (All time, 1m, 3m, 6m, 1y) + **Custom from/to** date inputs
- `inRange()` helper filters `projects`, `tasks`, `clients` arrays client-side against `due_date`/`joined_at`
- Stat cards update dynamically: completed projects count, tasks done count, total revenue all filter by range
- Filtered count summary shown when a range is active
- Built and deployed ✅

---

## ✅ Gap 9 — Task Comments ✅ FIXED (11/03/2026)

### What Was Done
**Backend:**
- `AddTaskCommentMutation.php` rewritten — now actually creates a `TaskComment` record (task_id, user_id, content) and returns the task with `comments.user` eager-loaded
- `ADD_TASK_COMMENT` mutation arg fixed: `$content` → `$comment` (matching backend)

**Frontend:**
- `GET_TASKS` query updated to include `completed_at` + `comments { id content created_at user { id name avatar_url } }`
- `TaskDetail.jsx` — full comment thread rendered: avatar + author name + timestamp + pre-wrap content, sorted oldest-first
- Submit button wired to `ADD_TASK_COMMENT` with `refetchQueries: [GET_TASKS]`, Ctrl+Enter shortcut
- Built and deployed ✅

---

## ✅ Gap 10 — Auth Token Handling ✅ VERIFIED (11/03/2026)

### Verification Result
`apolloClient.js` already correctly handles both failure modes:
- `networkError.statusCode === 401` → clears tokens, redirects to `/login`
- `graphQLErrors[].message.includes('unauthenticated')` → clears tokens, redirects to `/login`
- Link chain: `from([errorLink, authLink, httpLink])` — covers ALL operations (queries, mutations, subscriptions)
- No race condition risk: `window.location.href` assignment is idempotent — multiple 401s result in same redirect
- **No code changes needed.**

---

## ✅ Gap 11 — Password Reset Email URL ✅ FIXED (11/03/2026)

### What Was Done
- `FRONTEND_URL=http://localhost:3000` added to backend container `.env` via `docker exec`
- `User.php` — `sendPasswordResetNotification()` override added: calls `ResetPassword::createUrlUsing()` to build URL from `FRONTEND_URL` env var, then notifies once
- Password reset email links now point to `http://localhost:3000/reset-password?token=...&email=...`
- Built and deployed ✅

---

## 🟢 Gap 12 — WP Sites Bulk Check (Deferred)
**Priority: Low | Accepted as-is for now**

### Current State
`Sites.jsx` already calls `runSiteHealthCheck` per site when "Check All" is clicked. The implementation is sequential but functional for small site counts. A proper queued/batched approach would require a dedicated Laravel queue worker and SSE/WebSocket progress updates.

### Deferred Until
- More than 10 WP sites are active, or
- User explicitly requests progress tracking

## 📋 Priority Execution Order

```
DONE ✅  Gap 1  — ProjectCreate/Edit + TaskCreate/Edit pages (no-dialog compliance)
DONE ✅  Gap 2  — ProjectDetail Edit button fix
DONE ✅  Gap 3  — TaskDetail page created
DONE ✅  Gap 4  — Avatar token key fixed
DONE ✅  Gap 5  — Dashboard date format confirmed + fmtDate() added to Recent cards
DONE ✅  Gap 6  — Settings preferences persisted via user_prefs JSON column + updatePreferences mutation
DONE ✅  Gap 7  — /sites/create route added (auto-opens Add Site dialog)
DONE ✅  Gap 8  — Reports date range filter (preset buttons + custom from/to)
DONE ✅  Gap 9  — Task comments: AddTaskCommentMutation fixed, real thread in TaskDetail.jsx
DONE ✅  Gap 10 — Auth 401 handling verified: apolloClient.js handles networkError 401 + unauthenticated graphQLErrors
DONE ✅  Gap 11 — FRONTEND_URL set in .env; User.php sendPasswordResetNotification overridden
ACCEPT   Gap 12 — WP Sites bulk check: Sites.jsx already calls runSiteHealthCheck per site; queue-based batching deferred
```

---

## 📁 New Files Required

| File | Purpose |
|------|---------|
| `src/pages/saas/ProjectCreate.jsx` | Create project full-page form |
| `src/pages/saas/ProjectEdit.jsx` | Edit project full-page form |
| `src/pages/saas/TaskCreate.jsx` | Create task full-page form |
| `src/pages/saas/TaskEdit.jsx` | Edit task full-page form |
| `src/pages/saas/TaskDetail.jsx` | Task detail page with comments |
| `src/pages/saas/SiteCreate.jsx` *(optional)* | Create new WP site |

## 🔧 Backend Changes Required

| Change | Purpose |
|--------|---------|
| `User.php` — override `sendPasswordResetNotification` | Use FRONTEND_URL for reset link |
| `TasksQuery.php` — add `date_from`/`date_to` filter args | Reports date range |
| `ProjectsQuery.php` — add `date_from`/`date_to` filter args | Reports date range |
| New `updatePreferences` mutation (optional) | Settings persistence |

---

*Generated by codebase audit — 11/03/2026*
