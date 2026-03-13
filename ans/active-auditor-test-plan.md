# Active Auditor Integration — End-to-End Test Plan

> **Date:** 2026-03-12
> **Target Environment:** Local Development environment via Docker
> **Test Site URL:** `https://test.webdezign3.co.uk/`
> **Wordfence Key:** `ZpztJQhEcOArbXTrsLwXsdXJfQc3qJFuGGtRrBVH`
> **AA Plugin Key:** `mro3Adk2ObB2R3BpNLkzCH1jNHaClgVr`

This document details the step-by-step test plan to verify that the Active Auditor integration is functioning correctly across the backend, WordPress plugin, and frontend dashboard.

---

## 🟢 Phase 1: Portal Global Settings

**Objective:** Verify that global API keys can be saved and tested.

1. Navigate to **Settings > API Integrations** (`/settings`) in the SaaS portal.
2. Under **Wordfence API**, enter the key: `ZpztJQhEcOArbXTrsLwXsdXJfQc3qJFuGGtRrBVH`
3. Click "Save & Test".
    - **Expected Result:** A success toast appears confirming the key is valid. The settings update without error.
4. If a Lighthouse API key is available, enter it and click "Save & Test".

---

## 🟢 Phase 2: Site Addition & Authentication

**Objective:** Add the test site to the portal and configure its Active Auditor plugin token.

1. Navigate to **All Sites** (`/sites`).
2. Click **Add New Site** and enter:
    - **Name:** Webdezign3 Test Site
    - **URL:** `https://test.webdezign3.co.uk/`
    - (Assign to any dummy Client for testing)
3. Click **Add Site**. You will be redirected to the Edit Site form (`/sites/:id/edit`).
4. In the Edit Site form, locate the **Active Auditor Plugin Connection** accordion.
5. Enter the exact plugin token: `mro3Adk2ObB2R3BpNLkzCH1jNHaClgVr`
6. Click **Verify & Save Token**.
    - **Expected Result:** A success toast appears. The connection badge for the site updates from "Not Configured" (gray) to "Connected" (green).
    - **Data Verification:** The `is_aa_configured` and `aa_plugin_active` flags should be true.

---

## 🟢 Phase 3: Triggering a Health Check

**Objective:** Execute a manual health check and ensure the background job successfully retrieves and parses the plugin's payload.

1. Navigate to the **Site Detail** page (`/sites/:id`).
2. Look at the top-right header and click the **"Run Health Check"** button.
3. Confirm the warning dialog.
    - **Expected Result:** A toast "Health check started" appears. 
4. Wait ~5-10 seconds for the backend background job (`RunWpHealthCheck`) to complete.
5. Watch the page for automatic data refresh, or manually format refresh.

---

## 🟢 Phase 4: Data Verification (Site Detail)

**Objective:** Ensure all parsed data is correctly displayed in the UI tabs.

1. **Overview Tab:**
    - Site Information grid should populate (PHP version, WP version, Hosting environment).
    - Google Services card should display detected services (GA4, GTM, Recaptcha, Fonts, Maps, Search Console).
    - Health badge should reflect a real status (Healthy/Warning/Critical), not Unknown.
2. **Plugins Tab:**
    - The plugins table should populate with the real plugins installed on `test.webdezign3.co.uk`.
    - Plugins needing updates should be clearly marked with an "Update Available" badge.
    - Click "Update All" (Optional/Destructive testing — only do if it's safe to update plugins on that live site).
3. **Security Tab:**
    - Any Wordfence or basic security findings should be listed.
4. **Lighthouse & SEO Tabs:**
    - If Google API keys are active, scores should be visible. (Otherwise, it may show 0s or empty state).
5. **Check History Tab:**
    - A new entry for the check you just ran should appear at the top, showing "Completed" and the duration.

---

## 🟢 Phase 5: Work Queue Automation

**Objective:** Verify that actionable issues from the health check automatically generated `wp_work_items`.

1. Navigate to the **Work Queue** (`/work-queue`).
2. Look for newly created items assigned to `test.webdezign3.co.uk`.
3. **Expected Automations** (depending on the site's state):
    - If the site has >3 plugin updates, a `P2` item "X Plugin Updates Needed" should exist.
    - If the site has a core update pending, a `P1` item "WordPress Core Update Available" should exist.
    - If overall health is critical, a `P0` item "Site Health is Critical" should exist.
    - If Wordfence reported vulnerabilities, a `P0/P1` item should exist.
4. **Action Test:** Change the status of one item from "Open" to "Done".
    - **Expected Result:** The item moves to the "Completed" tab (if filtering). In the database, the `resolved_at` timestamp is explicitly logged (confirming the G5 fix).

---

## 🟢 Phase 6: Dashboard Statistics

**Objective:** Verify the global rollup counts.

1. Navigate to the **Dashboard** (`/`).
2. Look at the **Site Health Distribution** pie chart.
    - **Expected Result:** Your test site should be reflected in the proper wedge (Healthy, Warning, or Critical).
3. Look at the top stat cards.
    - **Expected Result:** "Pending Updates" should sum the plugin/theme updates found on the test site. "Open Action Items" should reflect the auto-created work queue items.

---

**(End of Test Plan)**
