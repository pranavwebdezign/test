# AI Agent WP — Project Build Tracker
> **Docs read:** `ai-agent-wp-complete-docs-1.txt` (13 sections, 2717 lines) · `ai-agent-wp-complete-docs.docx` (same spec)
> **Stack:** Laravel 11 · rebing/graphql-laravel · React 18 + MUI v5 · Apollo · Docker · MySQL 8 · Redis 7
> **Demo:** admin@demo.com / dev@demo.com / client@demo.com — password: `password`
> **URLs:** Frontend → http://localhost:3000 · Backend GraphQL → http://localhost:8000/graphql · phpMyAdmin → http://localhost:8082

---

## ✅ COMPLETED

### Docker & Infrastructure
- [x] `docker-compose.yml` — 8 services running: `db`, `redis`, `phpmyadmin`, `backend`, `queue`, `nginx-backend`, `frontend`, `mailpit`, `wordpress`
- [x] Backend `Dockerfile` — PHP 8.3-FPM + Redis + GD + all Laravel 11 extensions
- [x] Frontend `Dockerfile` — Vite build → Nginx static serve
- [x] Created missing Laravel core files: `artisan`, `bootstrap/app.php`, `bootstrap/providers.php`, `routes/web.php`, `routes/api.php`, `routes/console.php`, `public/index.php`, `public/.htaccess`, `app/Providers/AppServiceProvider.php`
- [x] `composer.json` — `laravel/pail` added to `dont-discover` (fixes production boot)
- [x] All containers healthy: backend ✓ queue ✓ nginx-backend ✓ frontend ✓ db ✓ redis ✓

### Database — All 19 Migrations + Seed ✅
- [x] SaaS tables: `users`, `clients`, `projects`, `project_developers`, `tasks`, `task_comments`, `project_files`, `notifications`, `user_preferences`
- [x] WP Ops tables: `wp_clients`, `wp_sites`, `wp_findings`, `wp_work_items`, `wp_check_history`, `wp_security_issues`
- [x] New tables: `wp_lighthouse_scores`, `wp_seo_scores`, `wp_google_services`, `portal_settings`
- [x] AA column migration: `aa_token` (encrypted), `aa_token_hint`, `aa_token_verified_at`, `aa_plugin_version`, `aa_plugin_active`
- [x] `wp_check_history.data_type` column added
- [x] `migrate:fresh --seed` completed successfully — demo data seeded

### Backend Models
- [x] `WpSite` — encrypted `aa_token` + `auth_token`, `isAaConfigured()`, relationships to lighthouse/seo/google services
- [x] `WpCheckHistory` — fixed `$table = 'wp_check_history'` (Laravel would pluralise wrong)
- [x] `PortalSetting` — `get()`, `getDecrypted()`, `set()` static helpers with encryption
- [x] `WpLighthouseScore`, `WpSeoScore`, `WpGoogleServices` — new models created

### Backend GraphQL Types (registered in config/graphql.php)
- [x] `WpSiteType` — AA fields: `aa_plugin_active`, `aa_plugin_version`, `aa_token_hint`, `aa_token_verified_at`, `is_aa_configured`
- [x] `LighthouseScoreType`, `SeoScoreType`, `GoogleServicesType`, `AaPluginStatusType`
- [x] `PortalSettingType` — security: returns `hint` + `is_configured` only, never raw key

### Backend GraphQL Queries
- [x] `getSiteLighthouse(site_id)` → `LighthouseScoreType`
- [x] `getSiteSeo(site_id)` → `SeoScoreType`
- [x] `getSiteGoogleServices(site_id)` → `GoogleServicesType`
- [x] `portalSettings` → `[PortalSettingType]` (SuperAdmin only)

### Backend GraphQL Mutations
- [x] `updateWpSiteAaToken(id, aa_token)` — verifies against plugin `/status` before saving, encrypted store
- [x] `verifyWpSiteAaToken(id)` — re-pings plugin, updates `aa_token_verified_at`
- [x] `updatePortalSetting(key, value)` — SuperAdmin only, encrypts sensitive values
- [x] `testWordfenceKey(api_key?)` — calls Wordfence Intelligence API v2, returns `success`, `message`, `response_time_ms`
- [x] `testLighthouseKey(api_key?)` — calls Google PageSpeed API, returns `success`, `message`, `sample_score`
- [x] `refreshSiteData(site_id, data_type)` — dispatches `RunWpHealthCheck` job asynchronously

### Backend Queue Job
- [x] `RunWpHealthCheck` — full 10-step flow:
  1. `GET /status` → detect AA plugin active/version
  2. `GET /full-report?token=` → authenticated full data (health + security + updates)
  3. Wordfence CVE scan → portal calls `wordfence.com/api/intelligence/v2/plugins` directly
  4. Lighthouse → portal calls `googleapis.com/pagespeedonline/v5/runPagespeed` directly (plugin fallback)
  5. SEO analysis → plugin `/seo-analysis?token=`
  6. Google services → plugin `/guest/google-services`
  7. Compute overall health from findings
  8. Create `WpCheckHistory` record
  9. Notify SuperAdmin/Dev of P0 findings via `notifications` table
  10. Update `last_checked_at`

### Backend Seeders
- [x] `DatabaseSeeder` — 7 users (3 roles), 7 clients, projects, tasks, WP clients, WP sites, findings, work items, security issues, check history
- [x] `PortalSettingsSeeder` — seeds `wordfence_api_key`, `lighthouse_api_key`, `default_check_schedule`, `alert_p0_email`, `alert_p1_email`

### Frontend — Settings Page
- [x] `ApiIntegrationsSettings.jsx` — Wordfence key section + Lighthouse key section + Active Auditor info section with install instructions
- [x] `validationSchemas.js` — Zod schemas: `loginSchema`, `projectSchema`, `wpSiteSchema`, `wpSiteAuthSchema`, `wordfenceKeySchema`, `lighthouseKeySchema`, `aaSiteTokenSchema`
- [x] `Settings.jsx` — API Integrations section wired between Notifications and Credentials

### Frontend — Sites List Page (Sites.jsx)
- [x] Site grid cards show `AA` chip (green if `aa_plugin_active`, grey if not)
- [x] Site grid cards show Lighthouse score badge `⚡78` (colour-coded green/amber/red)

### Frontend — Site Detail Page (SiteDetail.jsx)
- [x] **7 tabs total** (added 2): Overview · Plugins · Security · **Lighthouse** · **SEO** · Check History · Work Items
- [x] **Lighthouse tab** — wired to real `GET_SITE_LIGHTHOUSE` Apollo query · Skeleton loaders · Alert if no data
- [x] **SEO tab** — wired to real `GET_SITE_SEO` Apollo query · Skeleton loaders · Alert if no data
- [x] **AaPluginCard (PluginTokenSection)** — interactive: token input + show/hide + Verify & Save → `UPDATE_WP_SITE_AA_TOKEN` · masked hint + Re-verify → `VERIFY_WP_SITE_AA_TOKEN` · toast notifications
- [x] **Apollo Client** — confirmed correct: `http://localhost:8000/graphql` + `Authorization: Bearer {token}` from localStorage

### Frontend — GraphQL Layer (queries.js)
- [x] `GET_WP_SITES` / `GET_WP_SITE` updated with AA plugin fields
- [x] `GET_SITE_LIGHTHOUSE`, `GET_SITE_SEO`, `GET_SITE_GOOGLE_SERVICES`, `GET_PORTAL_SETTINGS`
- [x] Mutations: `UPDATE_WP_SITE_AA_TOKEN`, `VERIFY_WP_SITE_AA_TOKEN`, `UPDATE_PORTAL_SETTING`, `TEST_WORDFENCE_KEY`, `TEST_LIGHTHOUSE_KEY`, `REFRESH_SITE_DATA`, `UPDATE_WP_SITE`

---

## 🔲 NOT YET DONE (Remaining Per Docs)

### Backend — Must Verify / Fix
- [ ] **`RunWpHealthCheck` job** — verify `data_type` is in `WpCheckHistory.$fillable`; verify `runSeoAnalysis()`, `detectGoogleServices()`, `callStandardWpApi()` methods all exist
- [ ] **Auth mutations** — verify `login`, `logout`, `register`, `forgotPassword`, `resetPassword` exist and are registered in `config/graphql.php`
- [ ] **SaaS mutations** — verify `createProject`, `updateProject`, `deleteProject`, `createTask`, `updateTask`, `deleteTask`, `addTaskComment`, `createClient`, `updateClient`, `createUser`, `updateUser`, `updateProfile`, `updatePassword`, `updatePreferences`
- [ ] **WP Ops mutations** — verify `createWpSite`, `updateWpSiteAuth`, `verifyWpSiteToken`, `removeWpSiteAuth`, `updateWpWorkItem`, `runSiteHealthCheck`
- [ ] **Backend validation** — app_password 24-char rule in `UpdateWpSiteAuth`; regex rule for Wordfence key; unique-except-self for `updateProfile` email
- [ ] **Notification mutations** — `markNotificationRead`, `markAllNotificationsRead`

### Frontend — Data Wiring Needed
- [x] **Edit Site screen** — `EditSite.jsx` at `/sites/:id/edit` · pre-fills from `GET_WP_SITE` · form: name, URL, notes, hosting, priority, schedule, auth, WP client · saves via `UPDATE_WP_SITE` mutation · "Edit Site" button wired in `SiteDetail.jsx`
- [ ] **Security tab** — wire to real Wordfence CVE data from site's `securityIssues` relation
- [ ] **Check History tab** — wire to real `wpCheckHistory` query instead of mock
- [ ] **Dashboard** — connect to real `dashboardStats` query
- [ ] **WP Dashboard** — connect to real `wpDashboard` query
- [ ] **Projects/Tasks/Clients pages** — wire create/edit/delete forms to real GraphQL mutations
- [x] **Authentication flow** — `AuthContext.jsx` calls real `LOGIN` mutation · stores Sanctum Bearer token in `auth_token` localStorage · restores session via `GET_ME` on page reload · logout calls `LOGOUT` mutation · register calls `REGISTER` mutation · removed global `auth:sanctum` middleware from graphql.php routes to allow unauthenticated login

### Frontend — Pages Not Yet Built (Section 9 Spec)
| Route | Description |
|---|---|
| `/workqueue` | Tabs: Active / All / Done. P0–P3 severity filter. Status change dropdown per row |
| `/findings` | All findings across all sites. Filter: site, severity, check_type |
| `/history` | Check history audit log. Filter: site, status, date range |
| `/security` | Security issues across all sites including Wordfence CVEs |
| `/wp/clients` | WP client company management (separate from SaaS clients) |
| `/wp/settings` | Default check schedule, alert email thresholds |

### Active Auditor Plugin REST API Endpoints (Section 3 — needs backend calls confirmed)
| Endpoint | Auth | Used in |
|---|---|---|
| `GET /status` | Public | Step 1 of health check job ✅ |
| `GET /guest/lighthouse` | Public | Lighthouse fallback ✅ |
| `GET /guest/seo` | Public | SEO fallback |
| `GET /guest/google-services` | Public | Google services detection ✅ |
| `GET /health?token=` | Token | Full health data step 2 ✅ |
| `GET /security-audit?token=` | Token | Security data |
| `GET /updates?token=` | Token | Plugin/theme/core updates |
| `GET /wordfence?token=` | Token | Local WF fallback |
| `GET /lighthouse?token=` | Token | Full LH audit (prefer direct API) |
| `GET /seo-analysis?token=` | Token | Full SEO analysis ✅ |
| `GET /full-report?token=` | Token | Combined health+security+updates ✅ |

---

## 🚀 NEXT PROMPT TO TRIGGER

```
Wire the Security tab in SiteDetail.jsx to real data:
1. Remove the mockSecurityIssues import and replace SecurityTab to use the site's real securityIssues from the GET_WP_SITE query (the data is already loaded in the parent — pass site.security_issues as a prop).
2. Wire the Check History tab the same way — replace mockCheckHistory with site.check_history from the parent.
3. Wire the Work Items tab — replace mockWorkItems with site.work_items from the parent.
4. The main SiteDetail component should use GET_WP_SITE Apollo query instead of mockSites.find() to load the site data. Show a Skeleton loading state and handle the case where the site isn't found with an EmptyState. The query is already defined in queries.js as GET_WP_SITE($id: String!).
```
