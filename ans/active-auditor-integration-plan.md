# Active Auditor × AI Agent Portal — Complete Integration Plan (v3)

> **Status:** Phase 0 Complete — Phases 1–9 Pending  
> **Last Updated:** 2026-03-12

---

## What Already Exists (Do NOT Re-build)

| Asset | Location | Status |
|---|---|---|
| `aa_token`, `aa_plugin_active`, `aa_plugin_version`, `aa_token_hint`, `aa_token_verified_at` columns | `wp_sites` DB (migration 000014) | ✅ Done |
| `auth_token`, `auth_username`, `auth_method`, `auth_token_hint`, `token_verified_at` columns | `wp_sites` DB (migration 000010) | ✅ Done |
| Both tokens encrypted via Laravel `'encrypted'` cast in `WpSite.php` | Model | ✅ Done |
| `updateWpSiteAaToken` mutation — saves AA token + verifies via `/v1/health` | `UpdateWpSiteAaTokenMutation.php` | ✅ Done |
| `verifyWpSiteAaToken` mutation — re-pings `/v1/status`, updates `aa_plugin_active/version` | `VerifyWpSiteAaTokenMutation.php` | ✅ Done |
| `updateWpSiteAuth` mutation — saves WP App Password (auth_method, username, token) | `UpdateWpSiteAuthMutation.php` | ✅ Done |
| `verifyWpSiteToken` mutation — tests WP App Password via `GET /wp-json/wp/v2/users/me` with Basic auth, checks `administrator` role | `VerifyWpSiteTokenMutation.php` | ✅ Done (uses Guzzle) |
| `removeWpSiteAuth` mutation — clears auth fields | `RemoveWpSiteAuthMutation.php` | ✅ Done |
| `refreshSiteData` mutation — dispatches `RunWpHealthCheck` job with `data_type` param | `RefreshSiteDataMutation.php` | ✅ Done |
| `runSiteHealthCheck` mutation — creates history record + dispatches job | `RunSiteHealthCheckMutation.php` | ✅ Done |
| `AaPluginCard` component — replaced by `SiteConnectionPanel` | `SiteDetail.jsx` | ✅ Done (Phase 0b) |
| `portal_settings` table + `updatePortalSetting` / `testWordfenceKey` / `testLighthouseKey` mutations | Backend | ✅ Done |
| `ApiIntegrationsSettings.jsx` — Wordfence + Lighthouse key management, fully wired | Frontend | ✅ Done |
| `wp_lighthouse_scores`, `wp_seo_scores`, `wp_google_services` tables | DB migrations 000016-000018 | ✅ Done |
| `has_maps`, `has_fonts`, `has_search_console` columns on `wp_google_services` | DB migration 000020 | ✅ Done (Phase 0) |
| `LighthouseTab`, `SeoTab` — fully wired to Apollo | `SiteDetail.jsx` | ✅ Done |

---

## Composer Dependencies (Backend)

| Package | Purpose | Installed? | Install Command |
|---|---|---|---|
| `guzzlehttp/guzzle` | HTTP client — used in `VerifyWpSiteTokenMutation` for WP App Password verification | ✅ Yes (Laravel includes it) | — |
| `illuminate/http` | `Http::` facade for AA REST API calls | ✅ Yes (built-in) | — |
| `illuminate/console` | Artisan commands for scheduler | ✅ Yes (built-in) | — |
| `spatie/laravel-permission` | Role-based access control (`SuperAdmin`, `Developer` roles already used) | Check `composer.json` | `composer require spatie/laravel-permission` |
| `laravel/sanctum` | API token auth (portal login — already used) | ✅ Yes | — |
| **No additional Composer packages needed** | All HTTP calls use `Http::` or `GuzzleHttp\Client` already present | — | — |

> **Note:** `VerifyWpSiteTokenMutation` already uses `new \GuzzleHttp\Client()` directly. All other mutations use `Illuminate\Support\Facades\Http`. Both are available without new packages.

---

## NPM Dependencies (Frontend)

| Package | Purpose | Installed? | Install Command |
|---|---|---|---|
| `@apollo/client` | GraphQL client | ✅ Yes | — |
| `@mui/material` + `@mui/icons-material` | UI components | ✅ Yes | — |
| `react-hook-form` + `@hookform/resolvers` | Form validation | ✅ Yes | — |
| `zod` | Schema validation | ✅ Yes | — |
| `recharts` | Charts for health score trends | ✅ Yes | — |
| `date-fns` | Date formatting | ✅ Yes | — |
| `notistack` | Toast notifications | ✅ Yes | — |
| `cronstrue` | Show human-readable schedule label (e.g. "Every Hour" from schedule key) | ❌ No | `npm install cronstrue` |
| `@mui/x-date-pickers` + `dayjs` | Date picker for "next scheduled check" display (optional) | ❌ No (optional) | `npm install @mui/x-date-pickers dayjs` |

---

## Frontend Gap Analysis (Code Audit Findings)

### 🔴 Critical — Broken or Mock Data Still Active

| File | Problem | Phase | Status |
|---|---|---|---|
| `WorkQueue.jsx` line 12, 26 | Uses `mockWorkItems` array — status changes not persisted | Phase 9a | ⏳ Pending |
| `SiteDetail.jsx` `PluginsList` line 727 | `simulateUpdate()` uses `setTimeout` — not a real API call | Phase 6c | ⏳ Pending |
| `SiteDetail.jsx` `ActiveThemeCard` line 664 | Hardcoded `useState({name:'Astra'...})` + `setTimeout` mock update | Phase 6c | ⏳ Pending |
| `SiteDetail.jsx` `PLUGINS_BY_SITE` line 30-48 | Hardcoded `s1/default` plugin arrays — not from API | Phase 6a | ⏳ Pending |
| `SiteDetail.jsx` `SecurityTab` line 834 | Receives `issues` prop but `site.security_issues` not returned by `GET_WP_SITE` | Phase 9a | ⏳ Pending |

### 🟡 Partially Wired — UI Exists but Not Persisted / Actions Missing

| File | Problem | Phase | Status |
|---|---|---|---|
| `Settings.jsx` lines 54-176 | All 4 settings sections save to local state only. `handleSave` calls `notify()` with no mutation. | Phase 3 | ⏳ Pending |
| `ApiIntegrationsSettings.jsx` line 405 | `<ActiveAuditorSection aaStats={null} />` — stats hardcoded null | Phase 3 | ⏳ Pending |
| `EditSite.jsx` auth section | Auth section rebuilt — verify/remove/AA token accordion added | Phase 0c | ✅ Done |
| `SiteDetail.jsx` right sidebar | "Run Health Check" button now calls real `RUN_SITE_HEALTH_CHECK` mutation | Phase 4b | ✅ Done |
| `SiteDetail.jsx` right sidebar | `check_schedule` shown but not editable inline | Phase 4b | ⏳ Pending |
| `GoogleServicesCard` | Now shows all 7 services including Maps, Fonts, Search Console | Phase 8 | ✅ Done |

### 🟢 Mutations in `queries.js`

| Mutation | Backend Mutation | Status |
|---|---|---|
| `UPDATE_WP_SITE_AUTH` | `updateWpSiteAuth` | ✅ Added (Phase 0a) |
| `VERIFY_WP_SITE_TOKEN` | `verifyWpSiteToken` | ✅ Added (Phase 0a) |
| `REMOVE_WP_SITE_AUTH` | `removeWpSiteAuth` | ✅ Added (Phase 0a) |
| `REFRESH_SITE_DATA` | `refreshSiteData` | ✅ Already in queries.js |
| `UPDATE_WP_SITE_AA_TOKEN` | `updateWpSiteAaToken` | ✅ Already exported |
| `VERIFY_WP_SITE_AA_TOKEN` | `verifyWpSiteAaToken` | ✅ Already exported |
| `RUN_SITE_HEALTH_CHECK` | `runSiteHealthCheck` | ✅ Added (Phase 0a) |
| `TRIGGER_SITE_UPDATE` | `triggerSiteUpdate` | ✅ Added (Phase 0a) |
| `UPDATE_WP_WORK_ITEM` | `updateWpWorkItem` | ✅ Added (Phase 0a) |

---

## ✅ Phase 0 — Site Integration: Complete Auth Connection — **DONE**

> **Status: 100% Complete**  
> Both authentication paths are now fully wired in the frontend.

---

### ✅ Phase 0a — Add Missing Mutations to queries.js — **DONE**

**Completed:** Added `UPDATE_WP_SITE_AUTH`, `VERIFY_WP_SITE_TOKEN`, `REMOVE_WP_SITE_AUTH`, `RUN_SITE_HEALTH_CHECK`, `TRIGGER_SITE_UPDATE`, `UPDATE_WP_WORK_ITEM` to `src/graphql/queries.js`.

Updated `GET_SITE_GOOGLE_SERVICES` to include `has_maps`, `has_fonts`, `has_search_console`.

`GET_WP_SITE` already included all auth fields.

---

### ✅ Phase 0b — New `SiteConnectionPanel` in SiteDetail Right Sidebar — **DONE**

**Completed in:** `src/pages/sites/SiteDetail.jsx`

- Replaced `AaPluginCard` with unified `SiteConnectionPanel` with two tabs
- **AA Plugin tab** (`AaTokenSubPanel`): token hint display, re-verify, replace token — wired to `UPDATE_WP_SITE_AA_TOKEN` + `VERIFY_WP_SITE_AA_TOKEN`
- **WP Admin tab** (`WpAuthSubPanel`): auth method dropdown, username, password, verification banner — wired to `UPDATE_WP_SITE_AUTH`, `VERIFY_WP_SITE_TOKEN`, `REMOVE_WP_SITE_AUTH` with confirm dialog
- Header shows 3-state status chip: Plugin Connected / WP Auth / Not Connected
- **Run Health Check** now calls real `RUN_SITE_HEALTH_CHECK` mutation (no more setTimeout mock)

---

### ✅ Phase 0c — Update EditSite.jsx Auth Section — **DONE**

**Completed in:** `src/pages/sites/EditSite.jsx`

- Auth credentials now saved via dedicated `UPDATE_WP_SITE_AUTH` (not bundled with `UPDATE_WP_SITE`)
- Added inline **Save Auth**, **Verify**, **Remove** buttons
- Added verification status banner (green/amber)
- Added **AA Token Accordion** (collapsed) with "Verify & Save" button → `UPDATE_WP_SITE_AA_TOKEN`
- `handleSubmit` no longer includes auth/token fields

---

### ✅ Phase 0d — Connection Status Badge on Sites Grid Cards — **DONE**

**Completed in:** `src/pages/sites/Sites.jsx`

- Replaced simple "AA" chip with 3-state badge: 🟢 Plugin / 🟡 WP Auth / 🔴 No Auth
- **Connect / Verify shortcut button** on cards without AA plugin active
- **List view** gains a dedicated "Connection" column with same 3-state display

---

### ✅ Phase 0e — Backend `WpSiteType` Auth Fields Verified — **DONE**

All required fields already declared in `WpSiteType.php`:
`auth_method`, `auth_username`, `auth_token_hint`, `masked_token`, `token_verified_at`, `is_authenticated`, `aa_plugin_active`, `aa_plugin_version`, `aa_token_hint`, `aa_token_verified_at`, `is_aa_configured`

No changes needed.

**Backend cleanup also done:**
- `UpdateWpSiteMutation.php` — removed `auth_method`, `auth_username`, `auth_token` args (auth only via dedicated mutations)
- `RunWpHealthCheck.php` — Google Services expanded to track 7 services
- `GoogleServicesType.php` — added `has_maps`, `has_fonts`, `has_search_console` fields
- `WpGoogleServices.php` — added new columns to fillable + casts
- Migration `000020_add_extended_google_services_columns.php` — **ran successfully ✅**

---

## ✅ Phase 1 — Fix URL Duplication Bug — **DONE**

**Completed in:** `src/pages/sites/EditSite.jsx`, `src/pages/sites/Sites.jsx`

Added `sanitizeUrl()` helper (strips trailing slash + collapses doubled origins like `https://x.com/https://x.com → https://x.com`) and applied it:
1. `EditSite.jsx` pre-fill `useEffect` — sanitizes URL loaded from DB before displaying in field
2. `EditSite.jsx` `handleSubmit` — sanitizes before sending to `UPDATE_WP_SITE` mutation
3. `Sites.jsx` `handleSave` — sanitizes URL before sending to `CREATE_WP_SITE` mutation

Backend `UpdateWpSiteMutation.php` and `CreateWpSiteMutation.php` already do `rtrim($url, '/')` — fully covered at all layers.

---

## ✅ Phase 2 — `RunWpHealthCheck` Job — **DONE (Already implemented)**

The job was already fully implemented (545 lines) with all 9 steps:
1. AA plugin detection via `/wp-json/active-auditor/v1/status`
2. Full health report from AA plugin + token verification
3. Wordfence Intelligence API scan (uses `portal_settings` key)
4. Google PageSpeed / Lighthouse API + plugin fallback
5. SEO Analysis via plugin endpoint
6. Google Services Detection (now tracks 7 services ✅)
7. Compute overall health from open findings
8. Update `WpCheckHistory` record
9. Notify `SuperAdmin`/`Developer` of new P0 findings

---

## ✅ Phase 3 — Settings Page: Wire All Sections — **DONE**

**Completed in:** `src/pages/Settings.jsx`, `src/components/settings/ApiIntegrationsSettings.jsx`

- **`Settings.jsx`** rewired: `useQuery(PORTAL_SETTINGS_QUERY)` loads settings on mount, `useEffect` pre-fills local state from the API response, and `handleSave` now batch-calls `UPDATE_PORTAL_SETTING` for each wired key: `default_check_schedule`, `alert_p0_email`, `alert_p1_email`, `slack_webhook_url`. Save button shows `CircularProgress` while mutations are in flight.
- **`ApiIntegrationsSettings.jsx`**: Now accepts `aaStats` as a prop (was hardcoded `null`). `ActiveAuditorSection` now shows real totals from `GET_WP_SITES` (total sites, active AA plugin count).
- All portal settings GQL declarations (`PORTAL_SETTINGS_QUERY`, `UPDATE_PORTAL_SETTING`, `TEST_WORDFENCE_KEY`, `TEST_LIGHTHOUSE_KEY`) were already in `queries.js` and registered in `config/graphql.php`.

---

## ✅ Phase 4 — Check Schedule Backend + Frontend — **DONE**

### ✅ 4a Backend Scheduler

**Created:** `app/Console/Commands/RunDueSiteChecks.php` + `app/Console/Kernel.php`

- Command `wp:run-due-checks` queries all sites with `aa_plugin_active = true` OR stored WP credentials
- For each site, computes overdue state from `last_checked_at` + `check_schedule` enum
- Dispatches `RunWpHealthCheck::dispatch($site)->onQueue('health-checks')`
- Supports `--dry-run` flag for safe inspection
- Kernel registers the command: `->everyFiveMinutes()->withoutOverlapping(10)->runInBackground()`
- Output logged to `storage/logs/scheduler.log`

### ✅ 4b Frontend Schedule Picker

**Completed in:** `src/pages/sites/SiteDetail.jsx`

- New `ScheduleCard` component added to right sidebar (last card, below GoogleServicesCard)
- Inline `<Select>` bound to `site.check_schedule`, fires `UPDATE_WP_SITE` immediately on change
- Shows `CircularProgress` during save, no submit button needed
- Warning tip displayed when site has no auth (is not connected)
- Shows "Last checked X ago" timestamp
- `UPDATE_WP_SITE` mutation now imported in SiteDetail.jsx

---

## ✅ Phase 5 — Authentication Dual-Path in Job — **DONE**

> Already implemented in `RunWpHealthCheck.php` as part of Phase 2. The job uses `$useAA` (AA plugin token) and `$useWP` (WP App Password with Basic auth) flags to select the correct data-fetch path for each site.

---

## ✅ Phase 6 — Plugin & Theme Updates from Portal — **DONE**

### ✅ 6a — Active Auditor Plugin REST Endpoints Added
**File:** `wp-content/plugins/active-auditor/includes/class-rest-endpoints.php`
- 5 new POST routes registered: `/update-plugin`, `/update-theme`, `/update-core`, `/configure-wordfence-key`, `/configure-lighthouse-key`
- Corresponding static callbacks: `Plugin_Upgrader`, `Theme_Upgrader`, `Core_Upgrader` with `WP_Ajax_Upgrader_Skin`
- Key storage callbacks use `update_option('aa_wordfence_api_key', ...)` / `update_option('aa_lighthouse_api_key', ...)`

### ✅ 6b — Backend `TriggerSiteUpdateMutation.php`
**File:** `app/GraphQL/Mutations/TriggerSiteUpdateMutation.php`
- Args: `id: String!`, `type: String!` (plugin/theme/core), `item_slug: String`
- Calls appropriate AA REST POST endpoint with site's AA token
- On success dispatches `RunWpHealthCheck::dispatch($site)` to re-scan
- Registered in `config/graphql.php` ✅

### ✅ 6c — Frontend Plugin/Theme Tables Wired
**Files:** `src/pages/sites/SiteDetail.jsx`, `app/Models/WpCheckHistory.php`, `app/GraphQL/Types/WpCheckHistoryType.php`
- New migration `000021_add_update_json_to_check_history.php` — adds `plugins_needing_update JSON`, `themes_needing_update JSON`, `wp_core_update_version` columns — ran in Docker ✅
- `WpCheckHistory` model: fillable + JSON casts updated
- `WpCheckHistoryType`: `data_type`, `health_score`, `plugins_needing_update`, `themes_needing_update`, `wp_core_update_version` fields added
- `GET_SITE_LATEST_CHECK` GQL query added to `queries.js`
- `SiteDetail.jsx`: `getPlugins(id)` mock replaced with real `GET_SITE_LATEST_CHECK` data; `simulateUpdate()` replaced with `TRIGGER_SITE_UPDATE` mutation; `ActiveThemeCard` now receives real theme data from latest check

---

## ✅ Phase 7 — Push API Keys to Plugin — **DONE**

**Completed in:** `app/GraphQL/Mutations/Settings/UpdatePortalSettingMutation.php`

After `$setting->save()`, when `key` is `wordfence_api_key` or `lighthouse_api_key`:
- Queries `WpSite::where('aa_plugin_active', true)->whereNotNull('aa_token')->get()`
- For each site, POSTs `{ token, api_key }` to `/wp-json/active-auditor/v1/configure-wordfence-key` or `/configure-lighthouse-key`
- Failures are **non-fatal** — logged as warnings, the portal setting is saved regardless

## ✅ Phase 8 — Google Services: Add Missing Services — **DONE**

**Completed:** `GoogleServicesCard` in `SiteDetail.jsx` now shows all 7 services:
GA4, GTM, Google Ads, reCAPTCHA, Google Maps, Google Fonts, Search Console

Backend: `GoogleServicesType.php`, `WpGoogleServices.php` model, and DB migration `000020` all updated.

---

## ✅ Phase 9 — Work Queue + Dashboard Integration — **DONE**

### ✅ 9a — Wire WorkQueue.jsx
**File:** `src/pages/WorkQueue.jsx`
- Removed `mockWorkItems` import + local `useState` store
- Added `useQuery(GET_WP_WORK_ITEMS)` — fetches all work items live
- `handleStatusChange` wired to `useMutation(UPDATE_WP_WORK_ITEM)` — fires immediately on select change + `refetch()`
- Task cell now shows **clickable site name link** → `/sites/{site_id}` via `useNavigate`
- Fixed all field references to real GQL shape: `row.site.name`, `row.created_at`, `row.site.wp_client.name`

### ✅ 9b — Auto-Create Work Items in Job
**File:** `app/Jobs/RunWpHealthCheck.php`
- New `autoCreateWorkItems($overallHealth, $pluginsNeedingUpdate, $coreUpdateVersion)` private method called as **Step 10** after `notifyNewCriticalFindings()`
- Dedupes via `reference` key — only creates if no open/acknowledged/in_progress item exists for same key
- Creates items for:
  1. WordPress core update available → P1
  2. >3 plugin updates pending → P2
  3. Overall health = `critical` → P0
  4. Wordfence P0 open findings → P0

### ✅ 9c — Dashboard WP Health Row
**File:** `src/pages/Dashboard.jsx`
- `recharts` installed: `npm install recharts` ✅
- New `WpHealthPie` component: donut chart (healthy/warning/critical/unknown) using live `GET_WP_SITES` data
- Placed between stat cards and the Needs Attention / Work Queue grid
- Legend shows colored dot + count + label per health bucket

### Supporting Changes
- `queries.js`: Added `GET_WP_WORK_ITEMS`, `UPDATE_WP_WORK_ITEM`, `TRIGGER_SITE_UPDATE` declarations
- `RunWpHealthCheck.php` copied to Docker container ✅

---

## Complete File Reference Map

### Backend Files
| File | Phase | Action | Status |
|---|---|---|---|
| `app/GraphQL/Types/WpSiteType.php` | 0e | Verify all auth fields exposed | ✅ Done |
| `app/GraphQL/Types/GoogleServicesType.php` | 8 | Added has_maps, has_fonts, has_search_console | ✅ Done |
| `app/GraphQL/Mutations/UpdateWpSiteMutation.php` | 0e | Removed auth args | ✅ Done |
| `app/GraphQL/Mutations/TriggerSiteUpdateMutation.php` | 6b | **NEW** | ✅ Done |
| `app/Jobs/RunWpHealthCheck.php` | 2, 5, 8 | Already implemented — expanded Google Services | ✅ Done |
| `app/Models/WpGoogleServices.php` | 8 | Added 3 new columns to fillable/casts | ✅ Done |
| `app/Console/Commands/RunDueSiteChecks.php` | 4a | **NEW** | ✅ Done |
| `app/Console/Kernel.php` | 4a | Register scheduler | ✅ Done |
| `app/GraphQL/Mutations/UpdatePortalSettingMutation.php` | 7 | Push keys to sites | ✅ Done |
| `config/graphql.php` | 6b | Register `triggerSiteUpdate` | ✅ Done |
| `database/migrations/000020_add_extended_google_services_columns.php` | 8 | **NEW — Ran ✅** | ✅ Done |
| `database/migrations/202X_add_update_json_to_check_history.php` | 6c | **NEW** | ✅ Done |

### WordPress Plugin Files
| File | Phase | Action | Status |
|---|---|---|---|
| `includes/class-rest-endpoints.php` | 6a, 7 | Add 5 new POST endpoints | ✅ Done |

### Frontend Files
| File | Phase | Action | Status |
|---|---|---|---|
| `src/graphql/queries.js` | 0a, 6b | Added 6 missing mutations + expanded GET_SITE_GOOGLE_SERVICES | ✅ Done |
| `src/pages/sites/SiteDetail.jsx` | 0b, 4b, 6c, 8 | SiteConnectionPanel, real health check mutation, 7 Google Services | ✅ Done |
| `src/pages/sites/EditSite.jsx` | 0c, 1 | Auth section with verify + AA token accordion | ✅ Done |
| `src/pages/sites/Sites.jsx` | 0d | 3-state connection badge on site cards | ✅ Done |
| `src/pages/Settings.jsx` | 3 | Wire all 4 sections to GraphQL | ✅ Done |
| `src/components/settings/ApiIntegrationsSettings.jsx` | 3 | Wire AA stats | ✅ Done |
| `src/pages/WorkQueue.jsx` | 9a | Replace mock data, wire GQL, fix search | ✅ Done |
| `src/pages/Dashboard.jsx` | 9c | WP health summary PieChart row | ✅ Done |

---

## Execution Order & Progress

| Phase | Summary | Time Est. | Status |
|---|---|---|---|
| **0a** | Add missing mutations to queries.js | 30min | ✅ Done |
| **0b** | New `SiteConnectionPanel` in SiteDetail | 2hr | ✅ Done |
| **0c** | UpdateSite auth section + verify + AA accordion | 1hr | ✅ Done |
| **0d** | Connection badge on Sites grid cards | 30min | ✅ Done |
| **0e** | Verify WpSiteType fields + backend cleanup | 20min | ✅ Done |
| **1** | URL duplication fix | 30min | ✅ Done |
| **2** | RunWpHealthCheck full implementation | 3hr | ✅ Done (was pre-built) |
| **3** | Wire Settings.jsx + ApiIntegrationsSettings to GraphQL | 1hr | ✅ Done |
| **4a** | Backend scheduler command | 1hr | ✅ Done |
| **4b** | Frontend schedule picker inline | 1hr | ✅ Done |
| **6a** | Plugin update REST endpoints in Active Auditor plugin | 2hr | ✅ Done |
| **6b+6c** | TriggerSiteUpdate mutation + wire frontend tables | 2hr | ✅ Done |
| **7** | Push API keys to sites on save | 30min | ✅ Done |
| **8** | Google Services 7-service view | 30min | ✅ Done |
| **9** | Work Queue + Work Items auto-creation + Dashboard | 2hr | ✅ Done |

**Completed: ~7hr of ~18hr total**

**Install before Phase 4b:**
```bash
cd d:\test-website\ai-agent\ai-agent-wp
npm install cronstrue
```

**No new Composer packages required** — Guzzle and Http facade both already available.
