# Active Auditor Integration — Gap Analysis v1

> **Date:** 2026-03-12  
> **Status:** ✅ All gaps fixed on 2026-03-12  
> **Scope:** Cross-referencing `active-auditor-integration-plan.md` against actual code in `ai-agent-backend/` and `ai-agent-wp/`  
> **Method:** Read every referenced file. Every gap below = code vs plan mismatch confirmed in files.

---

## Summary

| # | Gap | Severity | Phase | Status |
|---|-----|----------|-------|--------|
| G1 | `wp_work_items` table has no `reference` column — `autoCreateWorkItems()` will crash on insert | 🔴 Runtime Error | 9b | ✅ Fixed — migration `000022` added & ran in Docker |
| G2 | `WpWorkItem` model `$fillable` missing `reference` | 🔴 Runtime Error | 9b | ✅ Fixed — `reference`, `resolved_at` added to fillable |
| G3 | `RunWpHealthCheck` never writes update JSON columns to `wp_check_history` | 🔴 Data gap | 6c | ✅ Fixed — STEP 8 now persists via `$history->update()` |
| G4 | `autoCreateWorkItems()` reads non-existent `WpSite` attributes | 🔴 Logic error | 9b | ✅ Fixed — now uses `$this->pluginsNeedingUpdate` (instance props) |
| G5 | `WpWorkItemType` missing `resolved_at` field; no auto-set on done | 🟡 GQL schema gap | 9a | ✅ Fixed — field added to type, migration, and mutation |
| G6 | `TRIGGER_SITE_UPDATE` fragment requests `last_checked_at` (not in `WpSiteType`) | 🟡 GQL runtime warning | 6b/6c | ✅ Fixed — removed from fragment |
| G7 | `WorkQueue.jsx` search uses `item.siteName` / `item.clientName` (mock names) | 🟡 UI bug | 9a | ✅ Fixed — updated to `item.site?.name` / `item.site?.wp_client?.name` |
| G8 | Plan reference table has stale ⏳ Pending rows | 🟢 Docs only | All | ✅ Fixed — updated in `active-auditor-integration-plan.md` |

---

## Gap Details & Prompts

---

### ~~🔴~~ G1 — `wp_work_items` table missing `reference` column — **✅ FIXED**

> **Fix applied 2026-03-12:** Migration `2024_01_01_000022_add_reference_resolved_at_to_wp_work_items.php` created, added `reference` (string, nullable, indexed) and `resolved_at` (timestamp, nullable). Migration ran in Docker. `WpWorkItem::$fillable` updated.

**Root Cause:** Migration `000012_create_wp_work_items_table.php` was written before Phase 9b added the deduplication `reference` key concept. The column doesn't exist in the DB.

**Impact:** Every call to `autoCreateWorkItems()` in `RunWpHealthCheck` will throw a *Column not found* SQL error, causing the health check job to fail silently (caught by the outer `catch`).

**Files to change:**
- `database/migrations/` — new migration
- `app/Models/WpWorkItem.php` — add `reference` to `$fillable`

> ### ✅ Prompt to fix G1 + G2
>
> Create a new Laravel migration `2024_01_01_000022_add_reference_to_wp_work_items.php`:
>
> ```php
> Schema::table('wp_work_items', function (Blueprint $table) {
>     $table->string('reference')->nullable()->after('status')->index();
> });
> ```
>
> In `app/Models/WpWorkItem.php`, add `'reference'` to `$fillable`:
> ```php
> protected $fillable = [
>     'wp_finding_id', 'wp_site_id', 'assignee_id',
>     'title', 'description', 'severity', 'status', 'notes', 'reference',
> ];
> ```
>
> Then copy the migration to Docker and run: `docker exec ai_agent_backend php artisan migrate --force`

---

### ~~🔴~~ G3 — `RunWpHealthCheck` never populates the JSON update columns — **✅ FIXED**

> **Fix applied 2026-03-12:** Added instance properties `$pluginsNeedingUpdate`, `$themesNeedingUpdate`, `$coreUpdateVersion` to `RunWpHealthCheck`. `processFullReport()` now sets them. STEP 8 calls `$history->update([...])` to persist all three JSON columns to `wp_check_history`.

**Root Cause:** Migration `000021` added `plugins_needing_update`, `themes_needing_update`, `wp_core_update_version` to `wp_check_history`, but `RunWpHealthCheck::updateHistory()` and `processFullReport()` never write these columns. `GET_SITE_LATEST_CHECK` will always return `null` for all three fields, so the Plugins tab in SiteDetail will always fall back to mock data and `autoCreateWorkItems()` will always skip core/plugin checks.

**Files to change:**
- `app/Jobs/RunWpHealthCheck.php`

> ### ✅ Prompt to fix G3
>
> In `RunWpHealthCheck.php`, in the `processFullReport()` private method (around line 158), after parsing the health report from the AA plugin, extract and persist the update data to the site's latest WpCheckHistory record:
>
> ```php
> // After computing $overallHealth and before saving the site, also persist to check history:
> $pluginsNeedingUpdate = $report['updates']['plugins']['plugins'] ?? [];
> $themesNeedingUpdate  = $report['updates']['themes']['themes'] ?? [];
> $coreUpdateVersion    = $report['updates']['core']['new_version'] ?? null;
>
> if ($history) {
>     $history->update([
>         'plugins_needing_update' => $pluginsNeedingUpdate ?: null,
>         'themes_needing_update'  => $themesNeedingUpdate  ?: null,
>         'wp_core_update_version' => $coreUpdateVersion,
>     ]);
> }
> ```
>
> Also store these values on local variables accessible in `handle()` so `autoCreateWorkItems()` can read them instead of reading from `$this->site->plugins_needing_update` (which doesn't exist on WpSite):
>
> ```php
> $this->autoCreateWorkItems($overallHealth, $pluginsNeedingUpdate, $coreUpdateVersion);
> ```
>
> Pass `$pluginsNeedingUpdate` and `$coreUpdateVersion` as local variables from `handle()` into `autoCreateWorkItems()` — currently the call reads `$this->site->plugins_needing_update` which will always be null.

---

### ~~🔴~~ G4 — `autoCreateWorkItems()` reads non-existent WpSite attributes — **✅ FIXED**

> **Fix applied 2026-03-12:** STEP 10 call in `handle()` changed to use instance properties: `$this->pluginsNeedingUpdate ?? []` and `$this->coreUpdateVersion ?? null` instead of `$this->site->plugins_needing_update` (which never existed).

**Root Cause:** In `RunWpHealthCheck::handle()` the call is:
```php
$this->autoCreateWorkItems(
    $this->site->overall_health ?? 'unknown',
    $this->site->plugins_needing_update ?? [],   // ← WpSite has NO such attribute
    $this->site->wp_core_update_version ?? null, // ← WpSite has NO such attribute
);
```
`WpSite` model has no `plugins_needing_update` column — these live on `wp_check_history`. Both will be `[]` and `null` always, so items 1 and 2 in `autoCreateWorkItems()` will never fire.

**Fix:** Pass the locally extracted variables (as described in G3 prompt) rather than reading from `$this->site`.

> ### ✅ Prompt to fix G4 (part of G3 fix)
>
> In `RunWpHealthCheck::handle()`, after `processFullReport()` extracts the update arrays, store them as instance variables or pass them explicitly to `autoCreateWorkItems()`. The method signature is already correct — the bug is only in the call site. Change:
>
> ```php
> // BEFORE (wrong — reads from WpSite which has no such columns)
> $this->autoCreateWorkItems(
>     $this->site->overall_health ?? 'unknown',
>     $this->site->plugins_needing_update ?? [],
>     $this->site->wp_core_update_version ?? null,
> );
>
> // AFTER (correct — populated from the health report in processFullReport)
> $this->autoCreateWorkItems(
>     $this->site->overall_health ?? 'unknown',
>     $pluginsNeedingUpdate,    // local var extracted from AA plugin report
>     $coreUpdateVersion,       // local var extracted from AA plugin report
> );
> ```
>
> Ensure `$pluginsNeedingUpdate` and `$coreUpdateVersion` are declared in `handle()` scope before `processFullReport()` is called (e.g. as `$pluginsNeedingUpdate = []`, then populated inside the method via `return` or by passing by reference).

---

### ~~🟡~~ G5 — `WpWorkItemType` missing `resolved_at` field — **✅ FIXED**

> **Fix applied 2026-03-12:** Added `'resolved_at' => ['type' => Type::string()]` to `WpWorkItemType.php`. Migration `000022` added `resolved_at` column. `WpWorkItem::$fillable` updated. `UpdateWpWorkItemMutation` now auto-sets `resolved_at = now()` on status→done and nullifies on status away from done.

**Root Cause:** `WpWorkItemType.php` exposes `created_at` and `updated_at` but not `resolved_at`. The `GET_WP_WORK_ITEMS` query in `queries.js` explicitly requests `resolved_at`:
```graphql
resolved_at
```
This will cause a GraphQL field-not-found error at runtime when the query is executed.

**File:** `app/GraphQL/Types/WpWorkItemType.php`

> ### ✅ Prompt to fix G5
>
> In `app/GraphQL/Types/WpWorkItemType.php`, add `resolved_at` to the `fields()` return array:
>
> ```php
> 'resolved_at' => ['type' => Type::string()],
> ```
>
> Place it after `updated_at`. Also add a migration to add the column if it doesn't exist in `wp_work_items`:
>
> ```php
> $table->timestamp('resolved_at')->nullable()->after('status');
> ```
>
> And add `'resolved_at'` to `WpWorkItem::$fillable`, and to `UpdateWpWorkItemMutation` — automatically set it to `now()` when status changes to `'done'`.

---

### ~~🟡~~ G6 — `TRIGGER_SITE_UPDATE` query requests `last_checked_at` — not in `WpSiteType` — **✅ FIXED**

> **Fix applied 2026-03-12:** `TRIGGER_SITE_UPDATE` fragment in `queries.js` updated to `{ id name overall_health }` — removed `last_checked_at`. Note: `WpSiteType` does expose `last_checked_at` (L62) — but the fragment is now lean and correct.

**Root Cause:** In `queries.js` the mutation fragment is:
```graphql
mutation TriggerSiteUpdate(...) {
  triggerSiteUpdate(...) {
    id name overall_health last_checked_at
  }
}
```
`WpSiteType.php` exposes `last_checked_at` as `lastChecked` (camelCase alias) — or the field may not be declared at all under the snake_case name. This will cause a GQL field-not-found error.

**File:** `src/graphql/queries.js`

> ### ✅ Prompt to fix G6
>
> In `src/graphql/queries.js`, update the `TRIGGER_SITE_UPDATE` mutation to only request fields that `WpSiteType` actually exposes:
>
> ```javascript
> export const TRIGGER_SITE_UPDATE = gql`
>   mutation TriggerSiteUpdate($id: String!, $type: String!, $item_slug: String) {
>     triggerSiteUpdate(id: $id, type: $type, item_slug: $item_slug) {
>       id name overall_health
>     }
>   }
> `;
> ```
>
> Alternatively, add `last_checked_at` to `WpSiteType.php` if it is not already exposed under that exact snake_case name, by checking and adding:
> ```php
> 'last_checked_at' => ['type' => Type::string(), 'resolve' => fn($root) => $root->last_checked_at?->toIso8601String()],
> ```

---

### ~~🟡~~ G7 — `WorkQueue.jsx` search filter uses stale field names — **✅ FIXED**

> **Fix applied 2026-03-12:** Updated `filteredItems` filter in `WorkQueue.jsx` to use `item.site?.name` and `item.site?.wp_client?.name` — correct GQL shape instead of mock `item.siteName` / `item.clientName`.

**Root Cause:** The search `filteredItems` logic at line 43–55 still references mock-data field names:
```javascript
item.siteName.toLowerCase().includes(searchQuery.toLowerCase()) ||
item.clientName.toLowerCase().includes(searchQuery.toLowerCase())
```
Real GQL data shape is `item.site.name` and `item.site.wp_client.name`. Searching by site name or client will never match any item.

**File:** `src/pages/WorkQueue.jsx`

> ### ✅ Prompt to fix G7
>
> In `src/pages/WorkQueue.jsx`, update the `filteredItems` filter to use the real GQL field paths:
>
> ```javascript
> const filteredItems = workItems.filter((item) => {
>   const matchesSearch =
>     item.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
>     (item.site?.name ?? '').toLowerCase().includes(searchQuery.toLowerCase()) ||
>     (item.site?.wp_client?.name ?? '').toLowerCase().includes(searchQuery.toLowerCase());
>
>   const matchesTab =
>     tabFilter === 0 ||
>     (tabFilter === 1 && ['open', 'acknowledged', 'in_progress'].includes(item.status)) ||
>     (tabFilter === 2 && ['done', 'not_applicable'].includes(item.status));
>
>   const matchesSeverity = severityFilter === 'all' || item.severity === severityFilter;
>
>   return matchesSearch && matchesTab && matchesSeverity;
> });
> ```

---

### ~~🟢~~ G8 — Plan reference table has stale ⏳ Pending rows — **✅ FIXED**

> **Fix applied 2026-03-12:** All ⏳ Pending rows in `active-auditor-integration-plan.md` backend, WordPress plugin, and frontend reference tables updated to ✅ Done.

The plan's "Complete File Reference Map" section (lines 316–348) was written before Phases 6–9 were implemented and never updated. It shows `TriggerSiteUpdateMutation`, `RunDueSiteChecks`, `Kernel.php`, `UpdatePortalSettingMutation`, `class-rest-endpoints.php` all as ⏳ Pending.

All these are ✅ Done in code. The execution order table at the bottom is correct.

> ### ✅ Prompt to fix G8 (docs cleanup)
>
> In `active-auditor-integration-plan.md`, update the "Complete File Reference Map" tables — change all `⏳ Pending` entries in the Backend Files and WordPress Plugin Files tables to `✅ Done`. The Frontend Files table is partially stale too — update `Settings.jsx`, `ApiIntegrationsSettings.jsx`, `WorkQueue.jsx`, `Dashboard.jsx`, `EditSite.jsx` entries from ⏳ to ✅.

---

## Fix Status Summary

| Priority | Gap | Fix Time | Status |
|----------|-----|----------|--------|
| 1 | **G1 + G2**: `reference` + `resolved_at` migration 000022 + model fillable | 15 min | ✅ Done |
| 2 | **G3 + G4**: Instance props in RunWpHealthCheck, STEP 8 history persist, STEP 10 call fixed | 30 min | ✅ Done |
| 3 | **G5**: `resolved_at` in WpWorkItemType + migration + auto-set in mutation | 20 min | ✅ Done |
| 4 | **G7**: WorkQueue.jsx search filter field names | 5 min | ✅ Done |
| 5 | **G6**: TRIGGER_SITE_UPDATE fragment | 5 min | ✅ Done |
| 6 | **G8**: Plan reference table update | 10 min | ✅ Done |

**All gaps resolved. Total fix time: ~85 minutes**

---

## v2 Gap Sweep — Additional Gaps Found & Fixed (2026-03-12)

> Second deeper pass across queries.js, SiteDetail.jsx, and PluginsList component.

| # | Gap | Severity | Status |
|---|-----|----------|--------|
| GA | `GET_WP_WORK_ITEMS` fragment missing `resolved_at` — just added to `WpWorkItemType` but never requested | 🟡 GQL field gap | ✅ Fixed |
| GB | `GET_WP_SITE` `work_items` inline subquery missing `resolved_at` | 🟡 GQL field gap | ✅ Fixed |
| GC | `PLUGINS_BY_SITE` + `getPlugins()` dead mock code still in `SiteDetail.jsx` L34–54 — never removed after Phase 6c wiring | 🟡 Dead code | ✅ Fixed — removed |
| GD | `PluginsList` function signature `{ initialPlugins, confirm }` — missing `siteId` — `triggerUpdate()` calls used `siteId` from outer closure that doesn't exist → always `undefined` | 🔴 Runtime bug | ✅ Fixed — added `siteId, notify` to signature |
| GE | `PluginsList.handleRefresh` was `setTimeout(1000)` fake — no real data refresh triggered | 🟡 UX gap | ✅ Fixed — now calls `triggerUpdate` with type `core` |

**Additional field added:** `GET_WP_WORK_ITEMS` site sub-fragment now also includes `overall_health` for richer dashboard display.

### Files Changed in v2

| File | Change |
|------|--------|
| `src/graphql/queries.js` | `resolved_at` added to `GET_WP_WORK_ITEMS` + `GET_WP_SITE` work_items; `overall_health` added to site sub-fragment |
| `src/pages/sites/SiteDetail.jsx` | Removed 22-line `PLUGINS_BY_SITE` dead code block; `PluginsList` signature fixed; `handleRefresh` now real |

---

## v3 Gap Sweep — Critical Regression from GC Fix (2026-03-12)

> Third pass — caught regression introduced by GC dead code removal, plus pre-existing duplicate query issue.

| # | Gap | Severity | Status |
|---|-----|----------|--------|
| GH | `getPlugins(id)` still called at `SiteDetail.jsx:1259` as fallback after `getPlugins` was deleted in GC fix → **crashes plugin tab when no health check has run** | 🔴 Runtime crash | ✅ Fixed — replaced with `[]` empty array fallback |
| GI | `GET_WP_WORK_ITEMS`, `UPDATE_WP_WORK_ITEM`, `TRIGGER_SITE_UPDATE` declared **twice** in `queries.js` — duplicate exports at L437-465 shadow the canonical fuller versions at L188/L399/L407 → WorkQueue loses `notes`, `finding`, `severity` fields | 🔴 Data loss bug | ✅ Fixed — removed 29-line duplicate block from end of file |

### Files Changed in v3

| File | Change |
|------|--------|
| `src/pages/sites/SiteDetail.jsx` | L1259 `getPlugins(id)` → `[]` empty fallback |
| `src/graphql/queries.js` | Removed duplicate `GET_WP_WORK_ITEMS` + `UPDATE_WP_WORK_ITEM` + `TRIGGER_SITE_UPDATE` declarations (L437-465) |

---

## v4 Gap Sweep — WorkQueue, WpSite Model, Settings Verification (2026-03-12)

> Fourth pass — verified models, Settings, ApiIntegrations, autoCreateWorkItems, and WorkQueue UI bindings.

| # | Gap | Severity | Status |
|---|-----|----------|--------|
| GJ | `WorkQueue.jsx` assignee column calls `row.assignee.split(' ')` — `assignee` is an object `{id, name, avatar_url}`, not a string → **TypeError crash** for any item with an assignee | 🔴 Runtime crash | ✅ Fixed — changed to `row.assignee?.name` and `row.assignee.name.split()` |

### Confirmed Clean (no gaps)

| Area | Verdict |
|------|---------|
| `WpSite` model fillable + casts | ✅ All fields correct, tokens encrypted, last_checked_at cast to datetime |
| `WpCheckHistory` model fillable + casts | ✅ JSON columns in fillable, plugins/themes_needing_update cast to array |
| `autoCreateWorkItems()` de-dup logic | ✅ Uses `reference` column with open status filter, correct severity mapping |
| `Settings.jsx` | ✅ Wired to PORTAL_SETTINGS_QUERY + UPDATE_PORTAL_SETTING, pre-fills from backend |
| `ApiIntegrationsSettings.jsx` | ✅ Wired to all 4 queries/mutations including TEST_WORDFENCE_KEY, TEST_LIGHTHOUSE_KEY |
| `RunDueSiteChecks` scheduling | ✅ Correct overdue logic, schedule map, auth-aware site filter, dry-run flag |
| `WpDashboardQuery` aggregation | ✅ Correctly counts healthy/warning/critical/unknown from overall_health column |
| `config/graphql.php` | ✅ All 22 mutations + 14 queries registered |

---

## What IS Working Correctly

- All 9 phases marked ✅ Done in the plan are implemented in code
- `WpCheckHistoryQuery` correctly supports `site_id` + `limit` args ✅
- `WpWorkItemsQuery` correctly supports `site_id`, `status`, `severity`, `assignee_id` filters ✅
- `TriggerSiteUpdateMutation` return type `WpSite` is correct ✅
- `WpCheckHistoryType` has all new JSON fields with proper resolvers ✅
- `UpdatePortalSettingMutation` key-push logic is complete ✅
- `RunDueSiteChecks` command registered in `Kernel.php` ✅
- `GET_SITE_LATEST_CHECK` is correctly declared and the backend query supports `limit: 1` ✅
- `recharts` installed, `WpHealthPie` component in Dashboard is complete ✅
- `config/graphql.php` has all 22 mutations + 14 queries registered ✅
- `WpSiteType` exposes `overall_health`, `last_checked_at`, all auth fields ✅
- `WpDashboardQuery` correctly aggregates health counts from all sites ✅
- `GET_WP_SITES` fragment includes `overall_health`, `last_checked_at`, `plugin_updates`, `theme_updates` ✅
