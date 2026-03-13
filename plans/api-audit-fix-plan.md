# API Integration Audit & Fix Plan

> **Scope**: `ai-agent-wp` frontend — GraphQL queries/mutations coverage audit.
> **Rule**: No existing queries/mutations changed. All fixes add new definitions or update wiring only.

---

## Current Status Summary

| Category | Count | Status |
|---|---|---|
| Queries fully wired | 17 | ✅ |
| Mutations fully wired | 18 | ✅ |
| Missing mutations | 5 | ❌ |
| Missing queries | 1 | ❌ |
| Pages using workarounds | 2 | ⚠️ |
| Mock data instances | 3 | 🟡 |

---

## Phase 1A — Developer CRUD Mutations 🔴 CRITICAL

**Problem**: `CREATE_DEVELOPER`, `UPDATE_DEVELOPER`, `DELETE_DEVELOPER` not defined in `mutations.js`.
Pages `DeveloperCreate.jsx`, `DeveloperEdit.jsx`, `Developers.jsx` have no working save/delete.

**Missing mutations**:
```graphql
mutation CreateDeveloper($name:String!,$email:String!,$password:String!,$role:String,$company:String,$bio:String) {
  createDeveloper(name:$name,email:$email,password:$password,role:$role,company:$company,bio:$bio) {
    id name email role status company bio
  }
}
mutation UpdateDeveloper($id:String!,$name:String,$email:String,$role:String,$status:String,$company:String,$bio:String) {
  updateDeveloper(id:$id,name:$name,email:$email,role:$role,status:$status,company:$company,bio:$bio) {
    id name email role status company bio
  }
}
mutation DeleteDeveloper($id:String!) {
  deleteDeveloper(id:$id)
}
```

**Steps**:
1. Add the 3 mutations above to `src/graphql/mutations.js`
2. Wire `CREATE_DEVELOPER` in `DeveloperCreate.jsx`
3. Wire `UPDATE_DEVELOPER` in `DeveloperEdit.jsx`
4. Wire `DELETE_DEVELOPER` in `Developers.jsx` delete handler

### 🤖 Prompt 1A
```
Fix DeveloperCreate.jsx, DeveloperEdit.jsx, Developers.jsx — API wiring only:
1. Add CREATE_DEVELOPER, UPDATE_DEVELOPER, DELETE_DEVELOPER to src/graphql/mutations.js:
   - CreateDeveloper($name, $email, $password, $role, $company, $bio)
   - UpdateDeveloper($id, $name, $email, $role, $status, $company, $bio)
   - DeleteDeveloper($id)
   Each returns: id name email role status company bio
2. In DeveloperCreate.jsx: import CREATE_DEVELOPER, use useMutation, call on form submit.
   On success: navigate('/developers') + show success snackbar.
3. In DeveloperEdit.jsx: import UPDATE_DEVELOPER, use useMutation, call on form submit.
   On success: navigate back + show success snackbar.
4. In Developers.jsx: import DELETE_DEVELOPER, use useMutation in the delete handler.
   On success: refetch GET_DEVELOPERS.
Do NOT change any UI, form layout, routing, or auth logic. Only wire the mutations.
[PASTE DeveloperCreate.jsx, DeveloperEdit.jsx, Developers.jsx]
```

---

## Phase 1B — Delete Client Mutation 🔴 CRITICAL

**Problem**: `DELETE_CLIENT` not in `mutations.js`. Delete button in `Clients.jsx` has no working handler.

**Missing mutation**:
```graphql
mutation DeleteClient($id: String!) {
  deleteClient(id: $id)
}
```

**Steps**:
1. Add `DELETE_CLIENT` to `src/graphql/mutations.js`
2. Wire it in `Clients.jsx` delete confirmation handler
3. On success: refetch `GET_CLIENTS`, show snackbar

### 🤖 Prompt 1B
```
Fix Clients.jsx — API wiring only:
1. Add DELETE_CLIENT mutation to src/graphql/mutations.js:
   mutation DeleteClient($id: String!) { deleteClient(id: $id) }
2. In Clients.jsx: import DELETE_CLIENT, add useMutation hook.
3. In the delete handler (inside the confirmation dialog confirm callback):
   call deleteClient({ variables: { id: selectedClient.id } })
4. On onCompleted: close dialog, refetch GET_CLIENTS, enqueueSnackbar('Client deleted', { variant: 'success' })
5. On onError: show error snackbar.
Do NOT change any UI, layout, filter logic, table columns, or routing.
[PASTE Clients.jsx]
```

---

## Phase 1C — Delete User Mutation 🔴 CRITICAL

**Problem**: `DELETE_USER` not in `mutations.js`. Delete button in `Users.jsx` has no working handler.

**Missing mutation**:
```graphql
mutation DeleteUser($id: String!) {
  deleteUser(id: $id)
}
```

**Steps**:
1. Add `DELETE_USER` to `src/graphql/mutations.js`
2. Wire it in `Users.jsx` delete handler
3. On success: refetch `GET_USERS`, show snackbar

### 🤖 Prompt 1C
```
Fix Users.jsx — API wiring only:
1. Add DELETE_USER mutation to src/graphql/mutations.js:
   mutation DeleteUser($id: String!) { deleteUser(id: $id) }
2. In Users.jsx: import DELETE_USER, add useMutation hook.
3. In the delete confirmation handler: call deleteUser({ variables: { id: selectedUser.id } })
4. On onCompleted: refetch GET_USERS, close dialog, show success snackbar.
5. On onError: show error snackbar.
Do NOT change any UI, layout, filter, table columns, or routing.
[PASTE Users.jsx]
```

---

## Phase 2A — GET_DEVELOPER Single Query 🟠 MEDIUM

**Problem**: `DeveloperDetail.jsx` fetches ALL developers then `find()`s by id client-side. Slow, wasteful.

**Missing query**:
```graphql
query Developer($id: String!) {
  developer(id: $id) {
    id name email role status company bio avatar_url
    tasks { id title status priority due_date project { id name } }
    projects { id name status progress due_date }
  }
}
```

**Steps**:
1. Add `GET_DEVELOPER` to `src/graphql/queries.js`
2. Replace `useQuery(GET_DEVELOPERS)` + `.find()` with `useQuery(GET_DEVELOPER, { variables: { id } })`
3. Update field references: `data?.developer` instead of `data?.developers?.find(...)`

### 🤖 Prompt 2A
```
Fix DeveloperDetail.jsx — query efficiency improvement only:
1. Add GET_DEVELOPER to src/graphql/queries.js:
   query Developer($id: String!) { developer(id:$id) { id name email role status company bio avatar_url
     tasks { id title status priority due_date project { id name } }
     projects { id name status progress due_date }
   }}
2. In DeveloperDetail.jsx: replace import of GET_DEVELOPERS with GET_DEVELOPER.
3. Replace: useQuery(GET_DEVELOPERS) with useQuery(GET_DEVELOPER, { variables: { id }, skip: !id })
4. Replace: const dev = data?.developers?.find(d => d.id === id) with: const dev = data?.developer
5. All other field references remain the same — the shape is identical.
Do NOT change any UI, layout, tabs, or routing.
[PASTE DeveloperDetail.jsx]
```

---

## Phase 2B — TaskDetail Use GET_TASK (Single) 🟠 MEDIUM

**Problem**: `TaskDetail.jsx` imports `GET_TASKS` (list) and renders `data?.tasks?.[0]`. Fragile — loads all tasks.
`GET_TASK` already exists in `queries.js` ✅ — zero backend work needed.

**Steps**:
1. In `TaskDetail.jsx` replace `GET_TASKS` import with `GET_TASK`
2. Replace `useQuery(GET_TASKS)` → `useQuery(GET_TASK, { variables: { id } })`
3. Replace `data?.tasks?.[0]` → `data?.task`

### 🤖 Prompt 2B
```
Fix TaskDetail.jsx — replace list query with single query, UI unchanged:
1. In TaskDetail.jsx replace:
   import { GET_TASKS } from '../../graphql/queries';
   const { data, loading } = useQuery(GET_TASKS);
   const task = data?.tasks?.[0];
   with:
   import { GET_TASK } from '../../graphql/queries';
   const { data, loading } = useQuery(GET_TASK, { variables: { id }, skip: !id });
   const task = data?.task;
2. GET_TASK query fields: id title description status priority due_date completed_at sort_order
   project { id name }   assignee { id name avatar_url }   comments { id content created_at user { id name avatar_url } }
   These are the same fields already rendered — just change the data accessor.
3. Keep all useMutation hooks (UPDATE_TASK, ADD_TASK_COMMENT), status handlers, comment handlers unchanged.
Do NOT change any UI, layout, styling, or mobile responsiveness.
[PASTE TaskDetail.jsx]
```

---

## Phase 3 — Notifications Mark-All-Read 🟠 MEDIUM

**Problem**: `Notifications.jsx` calls `markRead({ variables: {} })` without an `id`, but `MARK_NOTIFICATION_READ` has `$id: String!` (required). This throws a GraphQL validation error.

**Option A — Add backend mark-all mutation (preferred)**:
```graphql
mutation MarkAllNotificationsRead {
  markAllNotificationsRead { count }
}
```

**Option B — Frontend loop (no backend change needed)**:
```js
const unreadItems = notifications.filter(n => !n.read_at);
unreadItems.forEach(n => markRead({ variables: { id: n.id } }));
```

**Steps**:
1. Confirm backend: does `markAllNotificationsRead` resolver exist?
2. If YES → use Option A: add `MARK_ALL_NOTIFICATIONS_READ` to `mutations.js`, wire "Mark all" button
3. If NO → use Option B: replace the broken `markRead({variables:{}})` with the loop above

### 🤖 Prompt 3 (Option A — backend exists)
```
Fix Notifications.jsx mark-all-read — API wiring only:
1. Add MARK_ALL_NOTIFICATIONS_READ to src/graphql/mutations.js:
   mutation MarkAllNotificationsRead { markAllNotificationsRead { count } }
2. In Notifications.jsx: import MARK_ALL_NOTIFICATIONS_READ, add second useMutation hook: [markAll, { loading: markingAll }]
3. Replace the current broken markRead({variables:{}}) call with markAll().
4. On onCompleted: refetch GET_NOTIFICATIONS, show 'All notifications marked as read' snackbar.
Do NOT change layout, filters, notification card UI, or any other logic.
[PASTE Notifications.jsx]
```

### 🤖 Prompt 3 (Option B — no backend change)
```
Fix Notifications.jsx mark-all-read — frontend loop only:
1. In Notifications.jsx, find the "Mark all read" button handler.
2. Replace: markRead({ variables: {} })
   with: notifications.filter(n => !n.read_at).forEach(n => markRead({ variables: { id: n.id } }))
3. After the loop: call refetch().
No new mutations or imports needed.
Do NOT change layout, filters, notification card UI, or any other logic.
[PASTE Notifications.jsx]
```

---

## Phase 4A — GoogleServicesCard Real Data 🟡 LOW

**Problem**: Sidebar `GoogleServicesCard` uses static `GOOGLE_SERVICES` hardcoded array.
`GET_SITE_GOOGLE_SERVICES` query exists in `queries.js` ✅ — backend may already support it.

**Steps**:
1. Pass `siteId` prop into `GoogleServicesCard`
2. Add `useQuery(GET_SITE_GOOGLE_SERVICES, { variables: { site_id: siteId }, skip: !siteId })`
3. Map `has_ga4`, `has_gtm`, `has_google_ads`, `has_recaptcha` to the chip list
4. Remove static `GOOGLE_SERVICES` constant

### 🤖 Prompt 4A
```
Fix SiteDetail.jsx GoogleServicesCard — replace static mock with real API data:
1. In SiteDetail.jsx GoogleServicesCard function: add siteId prop.
2. Import GET_SITE_GOOGLE_SERVICES from graphql/queries.
3. Add: const { data: gsData } = useQuery(GET_SITE_GOOGLE_SERVICES, { variables: { site_id: siteId }, skip: !siteId })
4. const gs = gsData?.getSiteGoogleServices;
5. Replace the static GOOGLE_SERVICES array render with:
   [
     { label: 'GA4', detected: gs?.has_ga4 ?? false },
     { label: 'GTM', detected: gs?.has_gtm ?? false },
     { label: 'Google Ads', detected: gs?.has_google_ads ?? false },
     { label: 'reCAPTCHA', detected: gs?.has_recaptcha ?? false },
   ]
6. Remove the const GOOGLE_SERVICES = [...] static array.
7. Where GoogleServicesCard is rendered in SiteRightPanel: pass siteId={site.id}
Do NOT change the card UI, chip styling, or any other sidebar sections.
[PASTE SiteDetail.jsx relevant section]
```

---

## Phase 4B — Security Issues Real Data 🟡 LOW

**Problem**: `SecurityTab` uses `mockSecurityIssues` filtered array. Real data already fetched via `GET_WP_SITE` → `site.security_issues[]`.

**Steps**:
1. In `SecurityTab` props: receive `issues` from parent instead of filtering mock
2. In `SiteDetail` main component: pass `issues={site?.security_issues ?? []}` to `<SecurityTab>`
3. Remove `mockSecurityIssues` array

### 🤖 Prompt 4B
```
Fix SiteDetail.jsx SecurityTab — replace mockSecurityIssues with real site.security_issues:
1. Find: const issues = mockSecurityIssues.filter(s => s.siteId === siteId)
   Replace with: receive issues as a prop — SecurityTab({ siteId, issues = [] })
2. Where SecurityTab is rendered: pass issues={site?.security_issues ?? []}
   (site object is already fetched via GET_WP_SITE which includes security_issues[])
3. The security_issues shape from API: { id type title description severity detected_at resolved_at }
   Map these fields to the existing card render — title→title, description→description, severity→severity.
4. Remove the entire const mockSecurityIssues = [...] array from the file.
Do NOT change the SecurityTab card layout, severity colors, or chip styling.
[PASTE SiteDetail.jsx SecurityTab section]
```

---

## Phase 4C — Lighthouse Sidebar Mini-Card Real Data 🟡 LOW

**Problem**: `LighthouseCard()` mini-card in right sidebar uses `LIGHTHOUSE_DATA` static constants.
Real Lighthouse data is fetched for the full `LighthouseTab` — the sidebar should show the same scores.

**Steps**:
1. Pass `siteId` prop to `LighthouseCard`
2. Add `useQuery(GET_SITE_LIGHTHOUSE, { variables: { site_id: siteId }, skip: !siteId })`
3. Replace `LIGHTHOUSE_DATA[view]` with real `lh.performance`, `lh.accessibility`, `lh.best_practices`, `lh.seo_score`
4. Remove `LIGHTHOUSE_DATA` static constant

### 🤖 Prompt 4C
```
Fix SiteDetail.jsx LighthouseCard sidebar mini-card — replace static data with real API:
1. Add siteId prop to LighthouseCard({ siteId }).
2. Import GET_SITE_LIGHTHOUSE from graphql/queries.
3. Add: const { data: lhData } = useQuery(GET_SITE_LIGHTHOUSE, { variables: { site_id: siteId }, skip: !siteId })
4. const lh = lhData?.getSiteLighthouse;
5. Replace the LIGHTHOUSE_DATA[view] static scores array render with:
   [
     { label: 'Performance', score: lh?.performance ?? 0 },
     { label: 'Accessibility', score: lh?.accessibility ?? 0 },
     { label: 'Best Practices', score: lh?.best_practices ?? 0 },
     { label: 'SEO', score: lh?.seo_score ?? 0 },
   ]
6. Remove the guest/authenticated toggle (ToggleButtonGroup) — no longer needed with real data.
   Or keep it and show same real scores for both views.
7. Remove const LIGHTHOUSE_DATA = {...} static constant.
8. Where LighthouseCard is rendered in SiteRightPanel: pass siteId={site.id}
Do NOT change the score grid layout, colors, or scoreColor() function.
[PASTE SiteDetail.jsx LighthouseCard section]
```

---

## Phase 5 — Avatar Upload Mutation 🟡 LOW

**Problem**: `ProfileImageUpload` component has file picker UI but `UPLOAD_AVATAR` mutation doesn't exist. File selection does nothing.

**Steps**:
1. Add `UPLOAD_AVATAR` to `mutations.js`
2. Install `apollo-upload-client` if not present
3. Wire in `Profile.jsx` — pass mutation to `ProfileImageUpload`
4. On success: refetch `GET_ME` to update displayed avatar

### 🤖 Prompt 5
```
Fix Profile.jsx avatar upload — wire UPLOAD_AVATAR mutation:
1. Add UPLOAD_AVATAR mutation to src/graphql/mutations.js:
   mutation UploadAvatar($file: Upload!) {
     uploadAvatar(file: $file) { id avatar_url }
   }
2. In Profile.jsx: import UPLOAD_AVATAR, add useMutation hook:
   const [uploadAvatar, { loading: uploading }] = useMutation(UPLOAD_AVATAR, {
     onCompleted: () => { refetch(); enqueueSnackbar('Avatar updated', { variant: 'success' }); },
     onError: (e) => enqueueSnackbar(e.message || 'Upload failed', { variant: 'error' }),
   });
3. Pass handleAvatarChange to ProfileImageUpload:
   const handleAvatarChange = (file) => uploadAvatar({ variables: { file } });
   <ProfileImageUpload onChange={handleAvatarChange} uploading={uploading} />
4. In ProfileImageUpload.jsx: call props.onChange(file) in the file input onChange handler.
5. Check if apollo-upload-client is installed: if not, add createUploadLink to ApolloClient setup.
Do NOT change the avatar UI, crop logic (if any), or any other profile form fields.
[PASTE Profile.jsx, ProfileImageUpload.jsx]
```

---

## Fix Order (Recommended)

```
Phase 1A  →  Developer CRUD mutations        🔴 (unblocks Developers feature entirely)
Phase 1B  →  Delete Client                   🔴 (unblocks Clients delete)
Phase 1C  →  Delete User                     🔴 (unblocks Users delete)
Phase 2B  →  TaskDetail GET_TASK single      🟠 (quick win — query already defined)
Phase 3   →  Notifications mark-all-read     🟠 (confirm backend first)
Phase 2A  →  GET_DEVELOPER single query      🟠 (perf improvement)
Phase 4B  →  Security issues real data       🟡 (data already fetched in GET_WP_SITE)
Phase 4C  →  Lighthouse sidebar real data    🟡 (data already fetched in GET_SITE_LIGHTHOUSE)
Phase 4A  →  GoogleServices real data        🟡 (confirm backend getSiteGoogleServices)
Phase 5   →  Avatar upload                   🟡 (needs backend multipart + apollo-upload-client)
```

---

## Files Checklist

| Phase | Frontend Files | Backend Resolver Needed |
|---|---|---|
| 1A | `mutations.js`, `DeveloperCreate.jsx`, `DeveloperEdit.jsx`, `Developers.jsx` | `createDeveloper`, `updateDeveloper`, `deleteDeveloper` |
| 1B | `mutations.js`, `Clients.jsx` | `deleteClient` |
| 1C | `mutations.js`, `Users.jsx` | `deleteUser` |
| 2A | `queries.js`, `DeveloperDetail.jsx` | `developer(id)` resolver |
| 2B | `TaskDetail.jsx` only | None ✅ |
| 3  | `mutations.js`, `Notifications.jsx` | `markAllNotificationsRead` (or none for loop) |
| 4A | `SiteDetail.jsx` only | `getSiteGoogleServices` ✅ exists |
| 4B | `SiteDetail.jsx` only | `security_issues` in `wpSite` ✅ exists |
| 4C | `SiteDetail.jsx` only | `getSiteLighthouse` ✅ exists |
| 5  | `mutations.js`, `Profile.jsx`, `ProfileImageUpload.jsx` | `uploadAvatar` + multipart |
