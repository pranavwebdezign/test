# Plan: Redesign "Add New Site" — Match EditSite.jsx UI

## Overview

The current "Add New Site" is a Dialog/Modal in `Sites.jsx`. The user wants it replaced with a **dedicated page** that uses the same `SectionCard` pattern, shared helper components (`SelectField`), and same form field layout as `EditSite.jsx`.

Additionally the backend `createWpSite` mutation needs extra fields so the create flow can also set `wp_admin_url`, `auth_method`, `auth_username`, `auth_token`, and `aa_token` at creation time.

---

## Current State Analysis

### Frontend — `Sites.jsx` (dialog, lines 586–631)
- Uses `<Dialog>` with flat `<Stack>` of fields
- Custom one-off `textField()` / `selectField()` helpers (local only)
- Calls `CREATE_WP_SITE` mutation with 7 fields: `wp_client_id`, `name`, `url`, `hosting_environment`, `notes`, `check_schedule`, `priority`
- No authentication fields at creation time
- No `wp_admin_url` field

### Frontend — `EditSite.jsx` (page, 533 lines)
- `SectionCard` wrapper component with icon + title + divider pattern
- `SelectField` helper component
- Three sections: **Site Information**, **Monitoring Settings**, **Site Authentication**
- Auth handled via separate mutations: `UPDATE_WP_SITE_AUTH`, `VERIFY_WP_SITE_TOKEN`, `REMOVE_WP_SITE_AUTH`, `UPDATE_WP_SITE_AA_TOKEN`
- `sanitizeUrl()` helper to strip trailing slashes
- `isDirty` state to enable/disable save button

### Backend — `CreateWpSiteMutation.php`
- Accepts: `wp_client_id`, `name`, `url`, `hosting_environment`, `notes`, `check_schedule`, `priority`
- Auto-sets `wp_admin_url = url + /wp-admin`
- **Missing**: `auth_method`, `auth_username`, `auth_token`, `aa_token`, explicit `wp_admin_url`

### GraphQL Mutation — `mutations.js`
- `CREATE_WP_SITE` only returns: `id name url overall_health wp_client { id name }`

---

## Changes Required

### 1. Backend — `CreateWpSiteMutation.php`
**File:** `d:\test-website\ai-agent\ai-agent-backend\app\GraphQL\Mutations\CreateWpSiteMutation.php`

Add optional args:
- `wp_admin_url` — allow manual override (default: `url + /wp-admin`)
- `auth_method` — `app_password | jwt | basic`
- `auth_username` — WP username
- `auth_token` — hashed and stored
- `aa_token` — Active Auditor plugin token

In `resolve()`:
- Store `auth_token` as bcrypt hash (match existing `UpdateWpSiteAuthMutation` pattern)
- Store `aa_token` directly (match `UpdateWpSiteAaTokenMutation` pattern)
- Set `is_authenticated = true` when auth fields provided

**Prompt for this step:**
> "In `CreateWpSiteMutation.php`, add optional args: `wp_admin_url` (String), `auth_method` (String), `auth_username` (String), `auth_token` (String), `aa_token` (String). In `resolve()`, if `auth_token` is provided: hash it with bcrypt and set `auth_token`, `auth_username`, `auth_method`, `is_authenticated = true` on the model — matching exactly how `UpdateWpSiteAuthMutation.php` stores auth. If `aa_token` is provided: store it matching `UpdateWpSiteAaTokenMutation.php`. For `wp_admin_url`: use arg value if provided, else default to `url + /wp-admin`."

---

### 2. Frontend — `mutations.js`
**File:** `d:\test-website\ai-agent\ai-agent-wp\src\graphql\mutations.js`

Update `CREATE_WP_SITE` mutation:
- Add all new args: `$wp_admin_url: String, $auth_method: String, $auth_username: String, $auth_token: String, $aa_token: String`
- Return more fields to match what `EditSite.jsx` uses: `auth_method auth_username auth_token_hint is_authenticated aa_token_hint aa_plugin_active`

**Prompt for this step:**
> "In `mutations.js`, update `CREATE_WP_SITE` to add optional variables `$wp_admin_url: String, $auth_method: String, $auth_username: String, $auth_token: String, $aa_token: String` and pass them in the mutation body. Update the return fields to include `wp_admin_url auth_method auth_username auth_token_hint is_authenticated aa_token_hint aa_plugin_active hosting_environment check_schedule priority notes`."

---

### 3. Frontend — New Page: `AddSite.jsx`
**File:** `d:\test-website\ai-agent\ai-agent-wp\src\pages\sites\AddSite.jsx` *(NEW)*

Create a full-page form matching `EditSite.jsx` structure exactly:

**Structure:**
- Header: `← Sites` back button + `"Add New Site"` title + `[Cancel] [Add Site]` action buttons (same layout as EditSite header)
- **Section 1 — Site Information** (`SectionCard` with `Language` icon):
  - Site Name (required)
  - Site URL (required) — with `sanitizeUrl()`
  - WP Admin URL (optional — defaults to url+/wp-admin if left blank)
  - WP Client dropdown
  - Hosting Environment dropdown
  - Notes (multiline)
- **Section 2 — Monitoring Settings** (`SectionCard` with `SettingsIcon`):
  - Check Schedule dropdown
  - Priority dropdown
- **Section 3 — Authentication** (`SectionCard` with `SecurityIcon`):
  - Info alert: "You can set up authentication now or later from Site Settings"
  - Auth Method select
  - WP Username
  - App Password / Token (password field with toggle)
  - AA Plugin Token accordion (same as EditSite)

**Logic:**
- Single `createWpSite` mutation call on submit — passes all fields including optional auth
- On success: `notify('Site created', 'success')` then `navigate('/sites/' + newId)`
- No `isDirty` needed (everything is new)
- Reuse `SectionCard` and `SelectField` components — **extract them to a shared file** OR copy from `EditSite.jsx` (see note below)

**Shared components decision:**
Extract `SectionCard` and `SelectField` from `EditSite.jsx` into:  
`d:\test-website\ai-agent\ai-agent-wp\src\components\sites\SectionCard.jsx`  
`d:\test-website\ai-agent\ai-agent-wp\src\components\sites\SelectField.jsx`  
Then update `EditSite.jsx` imports accordingly.

**Prompt for this step:**
> "Create `src/pages/sites/AddSite.jsx`. It should be a full-page form (no Dialog) using `AppLayout` — identical layout and component structure to `EditSite.jsx`. Extract `SectionCard` and `SelectField` from `EditSite.jsx` into `src/components/sites/SectionCard.jsx` and `SelectField.jsx`, update `EditSite.jsx` to import from there. `AddSite.jsx` imports these shared components. Use the same three sections: Site Information (`Language` icon), Monitoring Settings (`SettingsIcon`), Site Authentication (`SecurityIcon`). On submit call `CREATE_WP_SITE` mutation with all fields including optional auth. On success navigate to `/sites/{newId}`. The auth section should show an info-only alert if no auth is entered at creation time — auth can always be added later in edit."

---

### 4. Frontend — Routing: `App.jsx` (or equivalent router file)
**File:** find via `grep_search 'EditSite'` in `src`

Add route:
```jsx
<Route path="/sites/new" element={<AddSite />} />
```
(before the dynamic `:id` routes to avoid conflict)

**Prompt for this step:**
> "In the router file, add a route `path='/sites/new'` → `<AddSite />`. Place it BEFORE `/sites/:id` and `/sites/:id/edit` to avoid path collision. Import `AddSite` from `./pages/sites/AddSite`."

---

### 5. Frontend — `Sites.jsx`
**File:** `d:\test-website\ai-agent\ai-agent-wp\src\pages\sites\Sites.jsx`

- **Remove** the Dialog/modal code (lines 586–631)
- **Remove** `isModalOpen`, `editingSite`, `formValues`, `formErrors`, `handleAdd`, `handleSave`, `textField()`, `selectField()`, `validateForm()` state/functions that only served the modal
- **Remove** `CREATE_WP_SITE` import and `createWpSite` mutation (moved to `AddSite.jsx`)
- **Change** `"Add Site"` button `onClick` to `navigate('/sites/new')` instead of opening modal
- Keep `handleEdit` → still navigates to `/sites/:id/edit`

**Prompt for this step:**
> "In `Sites.jsx`, remove the Add/Edit Dialog (the `<Dialog>` block and all associated state: `isModalOpen`, `formValues`, `formErrors`, `editingSite`, `handleSave`, `validateForm`, `textField`, `selectField`). Remove the `CREATE_WP_SITE` import and `createWpSite` mutation. Change the `'Add Site'` button `onClick` from `handleAdd` to `() => navigate('/sites/new')`. Keep all other site listing/filtering/deletion logic unchanged."

---

## Verification Plan

### Manual Test (no automated tests exist for this flow)

1. **Run dev server:**
   ```bash
   cd d:\test-website\ai-agent\ai-agent-wp
   npm run dev
   ```

2. **Test Add Site page:**
   - Navigate to `/sites` → click **Add Site**
   - Confirm it navigates to `/sites/new` (not opening a dialog)
   - Confirm page matches EditSite.jsx layout (SectionCard sections, same field names)
   - Fill only required fields (Name, URL, Client, Hosting) → click **Add Site**
   - Confirm site created, redirects to `/sites/{newId}`
   - Confirm new site appears in `/sites` list

3. **Test with auth fields:**
   - Go to `/sites/new`, fill required fields + auth credentials
   - After creation, go to `/sites/{newId}/edit`
   - Confirm auth shows as populated (username visible, token hint shown)

4. **Test EditSite still works:**
   - Go to any existing site → click Edit
   - Confirm `EditSite.jsx` still renders correctly with shared `SectionCard`/`SelectField` imports

5. **Test Sites list still works:**
   - Navigate to `/sites` — confirm no Dialog appears, no JS errors in console
   - Confirm "Add Site" button navigates to `/sites/new`

---

## File Change Summary

| File | Action | Description |
|------|--------|-------------|
| `CreateWpSiteMutation.php` | MODIFY | Add `wp_admin_url`, `auth_method`, `auth_username`, `auth_token`, `aa_token` args |
| `mutations.js` | MODIFY | Update `CREATE_WP_SITE` with new vars + return fields |
| `src/components/sites/SectionCard.jsx` | NEW | Extract from EditSite.jsx |
| `src/components/sites/SelectField.jsx` | NEW | Extract from EditSite.jsx |
| `src/pages/sites/AddSite.jsx` | NEW | Full-page Add Site form matching EditSite layout |
| `EditSite.jsx` | MODIFY | Update imports to use shared SectionCard/SelectField |
| `Sites.jsx` | MODIFY | Remove dialog + modal state, change button to navigate |
| Router file (`App.jsx` or similar) | MODIFY | Add `/sites/new` route |
