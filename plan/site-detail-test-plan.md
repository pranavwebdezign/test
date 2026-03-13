# Site Detail Page — Complete Test Plan
**URL:** `http://localhost:3000/sites/a1484929-ccec-419b-b6ec-c0274b832fa2`
**Site:** test.webdezign3.co.uk · **Last Run:** 2026-03-13 13:35 UTC
**Legend:** ✅ Pass · ❌ Fail · ⚠️ Partial / Note · ⏳ Pending

---

## 1. PAGE HEADER

| # | What to Check | Expected | Result | Notes |
|---|---|---|---|---|
| 1.1 | Site name | "test website" | ✅ | |
| 1.2 | Health badge | Critical / Warning / Healthy | ✅ | "Critical" |
| 1.3 | Client name | Name or "Unassigned" | ✅ | "TechFlow Inc" |
| 1.4 | Site URL link | Opens in new tab | ✅ | |
| 1.5 | Edit (pencil) button | Navigates to `/sites/{id}/edit` | ✅ | |
| 1.6 | Run Health Check button | Visible, enabled | ✅ | |
| 1.7 | Click Run Health Check → confirm dialog | Dialog appears | ✅ | |
| 1.8 | Confirm → "Checking…" spinner | Button disabled | ✅ | |
| 1.9 | Success toast | "Health check started…" | ✅ | |
| 1.10 | Last checked updates | Refreshed timestamp | ✅ | Updated to "2 minutes ago" |

---

## 2. STATUS CARDS ROW

| # | Field | Expected | Result | Notes |
|---|---|---|---|---|
| 2.1 | Health Status | Critical chip | ✅ | "Critical" |
| 2.2 | PHP Version | Current version | ✅ | "8.4.13" |
| 2.3 | WordPress Version | Current version | ✅ | "6.9" |
| 2.4 | Last Checked | Relative time | ✅ | Updates dynamically |

---

## 3. TAB NAVIGATION

| Tab | Label | Renders? | Notes |
|---|---|---|---|
| 0 | Overview | ✅ | |
| 1 | Plugins | ✅ | Badge: update count shown |
| 2 | Themes | ✅ | Badge: 2 updates |
| 3 | Security | ✅ | |
| 4 | Lighthouse | ✅ | |
| 5 | SEO | ✅ | |
| 6 | Check History | ✅ | |
| 7 | Work Items | ✅ | |

---

## 4. OVERVIEW TAB

### 4A. Site Information (left column)

| # | Row | Expected | Result | Notes |
|---|---|---|---|---|
| 4A.1 | Site URL | Clickable link | ✅ | https://test.webdezign3.co.uk |
| 4A.2 | WP Admin link | Clickable | ✅ | Present |
| 4A.3 | Client | Name or "—" | ✅ | "TechFlow Inc" |
| 4A.4 | Hosting | Environment | ⚠️ | Field present, value may be null |
| 4A.5 | PHP Version | Yellow chip | ✅ | "8.4.13" |
| 4A.6 | WordPress Version | Green chip | ✅ | "6.9" |
| 4A.7 | WP Core update chip | "↗ X.X available" | ⚠️ | Not shown — v6.9 is latest |
| 4A.8 | Update WordPress button | Purple button near version | ⚠️ | Hidden (no update available) |
| 4A.9–11 | Core update confirm/spinner/toast | See 12.4–12.6 | ⏳ | Requires core update to be available |
| 4A.12 | Last Checked | Formatted datetime | ✅ | |
| 4A.13 | Site Status | Online / Offline chip | ✅ | "Online" |

### 4B. Active Theme Card

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4B.1 | Theme name | ✅ | "Webdezign" |
| 4B.2 | Version | ✅ | v12.10.0.1766488359 |
| 4B.3 | "Update Available" chip | ✅ | NOT shown (child theme is up to date) |
| 4B.4 | "Update Theme" button | ✅ | NOT shown (no update needed) |
| 4B.5–7 | Update flow | ✅ | Tested via Themes tab (The7 / Hello Elementor) |

### 4C. Right Sidebar — Active Plugins

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4C.1 | Active / Total count | ✅ | 12 / 15 |
| 4C.2 | Progress bar | ✅ | Fills proportionally (purple) |

### 4D. Right Sidebar — Pending Updates

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4D.1 | Total update count | ✅ | 2 (1 theme + 1 plugin shown) |
| 4D.2 | "Theme" chip | ✅ | Shown |
| 4D.3 | "X Plugins" chip | ✅ | Shown |
| 4D.4 | "WP Core" chip | ⚠️ | Not shown (no core update available) |
| 4D.5 | "All up to date ✓" | ⚠️ | Hidden when updates exist |

### 4E. Right Sidebar — Security Issues

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4E.1 | Issues count or "No issues ✓" | ✅ | "No issues found ✓" |

### 4F. Right Sidebar — Site Connection Panel

**AA Plugin sub-tab:**

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4F.1 | "Plugin Connected" chip | ✅ | Green |
| 4F.2 | Plugin version | ✅ | "Active Auditor v1.0.0 detected" |
| 4F.3 | Token hint | ✅ | "••••••NBO6" |
| 4F.4 | Verified timestamp | ✅ | "Verified 8 minutes ago" |
| 4F.5 | Re-verify button | ✅ | Present |
| 4F.6 | Re-verify → toast | ⏳ | Not tested |
| 4F.7 | Token input (show/hide) | ✅ | "Replace Token" field present |
| 4F.8 | Verify & Save disabled when empty | ✅ | Confirmed |
| 4F.9 | Submit new token | ⏳ | Not tested (would overwrite) |

**WP Admin sub-tab:**

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4F.10 | Verified status box | ✅ | Present |
| 4F.11 | Auth Method dropdown | ✅ | "Application Password" selected |
| 4F.12 | Username field | ✅ | Shown with placeholder "admin" |
| 4F.13 | Password field (show/hide) | ✅ | Present |
| 4F.14 | Save button | ✅ | Present |
| 4F.15 | Verify button | ⏳ | Shown if authenticated |
| 4F.16 | Remove Credentials button | ⏳ | Not tested |
| 4F.17 | Remove → credential cleared | ⏳ | Not tested |

### 4G. Right Sidebar — Lighthouse Mini-Card

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4G.1 | 4 score boxes | ✅ | Performance=70, others=0/— |
| 4G.2 | Colour coding | ✅ | 70 = orange |
| 4G.3 | "Scanned X ago" | ✅ | Mar 13, 2026 13:34 |

### 4H. Right Sidebar — Google Services Mini-Card

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4H.1 | 7 services listed | ✅ | GA4 ✗, GTM ✗, Ads ✗, reCAPTCHA ✗, Maps ✗, Fonts ✗, Search Console ✗ |
| 4H.2 | "X active" chip | ✅ | "0 active" |
| 4H.3 | Scanned caption | ✅ | Present |

> **Note:** All Google services show as undetected. This may be accurate for the test site or detection logic may need refinement.

### 4I. Right Sidebar — Schedule Picker

| # | What to Check | Result | Notes |
|---|---|---|---|
| 4I.1 | Dropdown present | ✅ | All options available |
| 4I.2 | Current schedule | ✅ | "Daily" |
| 4I.3 | Change to Weekly → auto-saves | ✅ | Mutation fired |
| 4I.4 | Success toast | ✅ | "Check schedule updated." |

---

## 5. PLUGINS TAB

| # | What to Check | Result | Notes |
|---|---|---|---|
| 5.1 | Total plugin count | ✅ | "Plugins (15)" |
| 5.2 | Update badge | ⚠️ | Not shown as separate numeric badge on tab |
| 5.3 | Search bar | ❌ | **NOT present** — missing search/filter functionality |
| 5.4 | Row columns populated | ✅ | Name, status, installed, latest, update |
| 5.5 | Up to Date ✓ shown | ✅ | Active Auditor = Up to Date |
| 5.6 | Update button shown | ⏳ | Plugin showing updates not tested individually |
| 5.7 | Spinner isolated to clicked row | ⏳ | Needs plugin with update |
| 5.8 | Success → Up to Date | ⏳ | |
| 5.9 | File path under name | ⚠️ | Path shown as slug format, not WP file path (`plugin/plugin.php`) |
| 5.10 | Inactive chip | ✅ | Contact Form 7 shown as "Inactive" |

**Sample Plugin Data Verified:**

| Plugin | Status | Installed | Latest | Update |
|---|---|---|---|---|
| Active Auditor | Active | 1.0.0 | 1.0.0 | Up to Date ✓ |
| Child Theme Configurator | Active | 2.6.7 | 2.6.7 | Up to Date ✓ |
| Contact Form 7 | **Inactive** | 6.1.4 | 6.1.4 | Up to Date ✓ |

> **Issue found:** Plugins shown as Up to Date even though site reports 11 plugin_updates. Likely the latest health check (`all_plugins`) stored before the fix — run a new health check to refresh.

---

## 6. THEMES TAB

| # | What to Check | Result | Notes |
|---|---|---|---|
| 6.1 | Total theme count | ✅ | "Themes (6)" |
| 6.2 | Update badge | ✅ | "2 updates available" (amber chip) |
| 6.3 | All rows populated | ✅ | name, slug, status, installed, latest, update |
| 6.4 | The7 Update button | ✅ | 12.10.0 → 14.2.1.1 |
| 6.5 | Hello Elementor Update button | ✅ | 3.4.5 → 3.4.6 |
| 6.6 | Webdezign (child) = Up to Date | ✅ | No update button |
| 6.7 | Twenty Twenty-Three/Four/Five = Up to Date | ✅ | No update buttons |
| 6.8 | Spinner isolated to clicked row only | ✅ | **Fixed and verified** |
| 6.9 | Confirm dialog shows correct versions | ✅ | Correct A→B shown |
| 6.10 | Success → "Up to Date ✓" immediately | ✅ | No reload needed |
| 6.11 | Active theme "Active" chip | ✅ | Webdezign = Active (green) |

**All Themes Data:**

| Theme | Slug | Status | Installed | Latest | Update |
|---|---|---|---|---|---|
| The7 | dt-the7 | Installed | 12.10.0 | 14.2.1.1 | ✅ Update button |
| Hello Elementor | hello-elementor | Installed | 3.4.5 | 3.4.6 | ✅ Update button |
| Twenty Twenty-Five | — | Installed | 1.4 | 1.4 | Up to Date ✓ |
| Twenty Twenty-Four | — | Installed | 1.4 | 1.4 | Up to Date ✓ |
| Twenty Twenty-Three | — | Installed | 1.6 | 1.6 | Up to Date ✓ |
| Webdezign | webdezign | **Active** | 12.10.0.1766488359 | — | Up to Date ✓ |

---

## 7. SECURITY TAB

| # | What to Check | Result | Notes |
|---|---|---|---|
| 7.1 | Empty state / No issues | ✅ | "No security issues found ✓" + "passed all security checks" |
| 7.2 | Findings list (when present) | — | None currently |
| 7.3 | Severity chip colours | — | None to verify |

---

## 8. LIGHTHOUSE TAB

| # | What to Check | Result | Notes |
|---|---|---|---|
| 8.1 | Info alert when no data | — | Data present |
| 8.2 | 4 score dials | ⚠️ | Performance=70, Accessibility=0, Best Practices=0, SEO=0 |
| 8.3 | Dial colour coding | ✅ | 70 = orange dial correct |
| 8.4 | Data source chip | ✅ | "Google PageSpeed API" |
| 8.5 | Core Web Vitals | ✅ | FCP=0.8s, LCP=3.7s, CLS=0, Speed Index=5.7s |
| 8.6 | Last scanned caption | ✅ | Mar 13, 2026 13:34 |
| 8.7 | Opportunities list | ⏳ | Needs verification |

> **Issue:** Accessibility, Best Practices, SEO scores are 0 — may be incomplete data from PageSpeed API response.

---

## 9. SEO TAB

| # | What to Check | Result | Notes |
|---|---|---|---|
| 9.1 | Overall score | ❌ | **0** — all checks failing |
| 9.2 | "Based on 6 checks" | ✅ | |
| 9.3 | All 6 check rows | ❌ | All failing: Title ✗, Meta Desc ✗, H1 ✗, Canonical ✗, OG Tags ✗, Structured Data ✗ |
| 9.4 | Page Title detail | ❌ | "Missing title tag" |
| 9.5 | Meta Description | ❌ | "Missing meta description" |
| 9.6 | H1 Tag | ❌ | "0 H1 tags found" |
| 9.7 | Images card | ⚠️ | Missing alt tags reported |
| 9.8 | Links card | ✅ | Internal/External/No-text counts shown |
| 9.9 | Word count | ✅ | Present |
| 9.10 | Google Services (right panel) | ✅ | GA4 ✗, GTM ✗, Ads ✗, reCAPTCHA ✗ |

> **Issue:** SEO score is 0/all checks failing. The SEO scan results do not match the live site — homepage likely has meta/title. May need re-scan or fix in SEO detection logic.

---

## 10. CHECK HISTORY TAB

| # | What to Check | Result | Notes |
|---|---|---|---|
| 10.1 | Empty state | — | History present |
| 10.2 | Table columns (Started, Completed, Status, Findings) | ✅ | All present |
| 10.3 | Status chip (Success/Failed) | ✅ | All 4 visible rows = Success |
| 10.4 | Findings count | ✅ | All show "1 finding" |
| 10.5 | Most recent timestamp | ✅ | Mar 13, 2026 13:33 |
| 10.6 | Failed entries | ⚠️ | No Failed entries visible in current data |

**Check History Rows:**

| Started | Completed | Status | Findings |
|---|---|---|---|
| Mar 13, 13:33 | — | ✅ Success | 1 finding |
| Mar 13, 13:30 | — | ✅ Success | 1 finding |
| Mar 13, 13:13 | — | ✅ Success | 1 finding |
| Mar 13, 13:08 | — | ✅ Success | 1 finding |

---

## 11. WORK ITEMS TAB

| # | What to Check | Result | Notes |
|---|---|---|---|
| 11.1 | Empty state | — | Items present |
| 11.2 | Severity chip | ✅ | P0=red, P2=purple |
| 11.3 | Status chip | ✅ | Both "open" |
| 11.4 | Title + description | ✅ | |
| 11.5 | Assignee | ⏳ | Not visible |

**Work Items Found:**

| Severity | Status | Title |
|---|---|---|
| P2 | open | "11 Plugin Updates Needed" (Security risk) |
| P0 | open | "Site Health is Critical" (Immediate review) |

---

## 12. WORDPRESS CORE UPDATE

| # | What to Check | Result | Notes |
|---|---|---|---|
| 12.1 | "WP Core" chip in Pending Updates | ⚠️ | Not shown — WP 6.9 appears to be latest |
| 12.2 | "↗ X.X available" orange chip in Overview | ⚠️ | Not shown |
| 12.3 | "Update WordPress" button visible | ⚠️ | Hidden (conditional on core update) |
| 12.4 | Confirm dialog | ⏳ | Requires core update to be available |
| 12.5 | POST `/update-core` to AA plugin | ⏳ | Backend logic confirmed correct |
| 12.6 | Success toast | ⏳ | |

> **Note:** WP 6.9 is currently the latest version. The Update WordPress button will appear automatically when `core_update_available = true` after a health check on a site running an older WP version.

---

## 13. END-TO-END UPDATE FLOWS

### 13A. Plugin Update Flow

| Step | Action | Result |
|---|---|---|
| 1 | Navigate to Plugins tab | ✅ |
| 2 | Find plugin with "Update" button | ⚠️ All showing Up to Date — needs fresh health check data |
| 3–5 | Click Update → Spinner → Success | ⏳ |

> **Action needed:** Run health check, then verify update buttons appear for the 11 plugins that need updates.

### 13B. Theme Update Flow

| Step | Action | Result |
|---|---|---|
| 1 | Navigate to Themes tab | ✅ |
| 2 | The7 and Hello Elementor have Update buttons | ✅ |
| 3 | Click Update → Confirm dialog | ✅ |
| 4 | Spinner isolated to clicked theme only | ✅ **Bug fixed and verified** |
| 5 | Success → "Up to Date ✓" immediately | ✅ |
| 6 | Correct slug sent (hello-elementor, dt-the7) | ✅ |

---

## 14. ISSUES FOUND

| # | Severity | Component | Issue | Status |
|---|---|---|---|---|
| I-1 | ⚠️ Medium | Plugins tab | Search/filter bar is **missing** — plugins cannot be searched | Open |
| I-2 | ⚠️ Medium | Plugins tab | Plugin update buttons not showing (stale data — needs fresh health check) | Needs re-check |
| I-3 | ⚠️ Medium | SEO tab | All SEO checks failing (score=0) — may be stale or detection issue | Open |
| I-4 | ⚠️ Low | Lighthouse tab | Accessibility, Best Practices, SEO scores showing 0 | Open |
| I-5 | ⚠️ Low | Google Services | All 7 services showing as undetected | Open |
| I-6 | ℹ️ Info | WP Core Update | Update button hidden because WP 6.9 is already latest | Expected |
| I-7 | ✅ Fixed | Themes tab | All themes showed "Updating…" simultaneously (slug=undefined bug) | Fixed |

---

## 15. GRAPHQL MUTATIONS STATUS

| Mutation | Verified |
|---|---|
| `triggerSiteUpdate(type:'theme')` | ✅ |
| `triggerSiteUpdate(type:'plugin')` | ⏳ |
| `triggerSiteUpdate(type:'core')` | ⏳ |
| `runSiteHealthCheck` | ✅ |
| `updateWpSite(check_schedule)` | ✅ |
| `updateWpSiteAaToken` | ✅ UI only |
| `verifyWpSiteAaToken` | ⏳ |
| `updateWpSiteAuth` | ✅ UI only |
| `verifyWpSiteToken` | ⏳ |
| `removeWpSiteAuth` | ⏳ |
