# Detailed Test Plan: Projects & WP Sites (CRUD & Relationships)

This document contains a comprehensive, step-by-step test plan for all **Create, Read, Update, and Delete (CRUD)** operations, as well as the relational links between **Projects**, **WP Sites**, **Clients**, and **Developers**.

## Prerequisites
- **Login Status:** You must be logged in as an Admin or SuperAdmin.
- **Dependencies:** Ensure you have at least 1 Client and 1 Developer already created in the system to test relational drop-downs.

---

## Part 1: WP Sites CRUD & Relationships

### 1.1 Create WP Site
* **Objective:** Verify a WP Site can be added to the database and linked to an existing Client.
* **Test Steps:**
  1. Navigate to **Sites** (`/sites`).
  2. Click **Add Site**.
  3. **Fail Case:** Leave all fields blank and click "Add Site".
     - **Expected:** Form validation triggers. Fields like "Site Name", "Client", "Site URL", "WP Admin URL", and "Hosting Provider" should show red error text.
  4. **Success Case:** Fill in:
     - Basic Info: `Acme E-Commerce Store`, select `Client A` from the dropdown, `https://acme.com`, `https://acme.com/wp-admin`.
     - Hosting: `Cloudways`, `account-1234`.
     - Settings: `Daily`, `High Priority`.
  5. Click **Add Site**.
  6. **Expected:** 
     - A success notification (`"Site added successfully"`) appears.
     - The modal closes.
     - The grid/list instantly displays the new site.
     - The GraphQL `CREATE_WP_SITE` mutation reflects a 200 response in network logs.

### 1.2 Read & Filter WP Sites
* **Objective:** Verify sites are fetched from the database, rendered accurately, and can be filtered.
* **Test Steps:**
  1. Observe the **Grid View**.
  2. **Expected:** Ensure the card displays `Acme E-Commerce Store`, the subtitle displays `Client A` (via relational `wp_client.name`), and the URLs match.
  3. Toggle the view pattern to **List View**.
  4. **Expected:** List view gracefully handles the same GraphQL payload.
  5. Use the **Search bar**: Type `Acme`.
  6. **Expected:** Only matching sites should appear. Clear the search bar to restore the list.
  7. Use the **Health filter**: Change to "Critical".
  8. **Expected:** Only sites with a "critical" health status show.

### 1.3 Update WP Site
* **Objective:** Currently, full WP Site editing is handled slightly differently in this iteration (Placeholder message indicates "managed in Site Settings" or via detail view). For the scope of list actions:
* **Test Steps:**
  1. Click the **Edit (pencil)** icon on the site card.
  2. **Expected:** If fully hooked up, the dialog opens with prepopulated data. If not, the notification `"Edit functionality is currently managed in Site Settings"` appears.

### 1.4 Delete WP Site
* **Objective:** Verify total destruction of the site and cleanup via the database.
* **Test Steps:**
  1. Click the **Delete (trash can)** icon on the `Acme E-Commerce Store` card.
  2. **Expected:** A confirmation modal appears, warning the action is irreversible.
  3. Click **Delete** in the modal.
  4. **Expected:** 
     - The `DELETE_WP_SITE` mutation fires.
     - Notification `"Site deleted"` appears.
     - The site is removed from the UI instantly.
     - The site is physically deleted from the database.

---

## Part 2: Projects CRUD & Relationships

### 2.1 Create Project
* **Objective:** Verify projects save cleanly, accept status parameters, and enforce data typing.
* **Test Steps:**
  1. Navigate to **Projects** (`/projects`).
  2. Click **Create Project**.
  3. **Fail Case:** Click "Save Project" immediately.
     - **Expected:** Required field errors appear (Name, Client, Target Date).
  4. **Success Case:**
     - **Name:** `Website Redesign 2026`
     - **Client:** `Client A`
     - **Status:** `Active` (Verify status dropdown operates).
     - **Assigned Team:** Select 2 developers.
     - **Budget:** `15000`
  5. Click **Save Project**.
  6. **Expected:**
     - The `CREATE_PROJECT` mutation executes successfully (ensures `Unknown argument status` error is completely gone).
     - The system navigates back to `/projects` automatically.
     - The new project appears in the table with the correct status, budget format, and team member avatars.

### 2.2 Relational Test: Link WP Site on Creation
* **Objective:** Verify a project can be linked to a WordPress Site during its creation phase.
* **Requirements:** Ensure 1 WP Site currently exists in the system.
* **Test Steps:**
  1. Navigate to `/projects/create` (Create Project page).
  2. Toggle **"Is this a WordPress Project?"** to **ON**.
  3. **Expected:** The "Select WP Site" dropdown appears.
  4. Select a site from the dropdown. Fill in the rest of the standard project details and click **Save Project**.
  5. **Expected:**
     - The `wp_site_url` and `wp_site_id` data is transmitted in the mutation.
     - On the list view (`/projects`), click on the created project to view details. The WP Site linkage badge/URL should be visibly bound to this project.

### 2.3 Read & Filter Projects
* **Objective:** Verify the table maps relational data (Clients, Developers) correctly.
* **Test Steps:**
  1. Navigate to **Projects** (`/projects`).
  2. Check the **Client** column for the newly added project.
     - **Expected:** Displays `Client A` (via relational join) rather than throwing an undefined/null error.
  3. Check the **Team** column.
     - **Expected:** Displays avatar circles for every developer attached in step 2.1.
  4. Change the segmented control to view **"Completed"** projects.
     - **Expected:** The active table list modifies correctly based on state.

### 2.4 Update Project
* **Objective:** Modify existing data and test reassigning relationships.
* **Test Steps:**
  1. On the **Projects** list, find `Website Redesign 2026`.
  2. Click **Edit**.
  3. Change the **Project Name** to `Website Redesign Final`.
  4. Change **Status** from `Active` to `Completed`.
  5. Check the **WordPress Project** toggle. Ensure the previously selected site is still selected.
  6. Add 1 additional developer to the "Assigned Team".
  7. Click **Save Changes**.
  8. **Expected:**
     - The `UPDATE_PROJECT` mutation triggers successfully.
     - The list view instantly reflects `Website Redesign Final`.
     - The Project now only shows under the "Completed" tab of the list view.
     - The number of assigned developer avatars increases.

### 2.5 Delete Project
* **Objective:** Ensure removing a project succeeds without breaking relational trees (like developers or clients).
* **Test Steps:**
  1. On the **Projects** list, locate the `Website Redesign Final` project.
  2. Open the contextual menu options (three dots / right-side actions) if applicable, or click the **Delete** button.
  3. Confirm the deletion.
  4. **Expected:**
     - `DELETE_PROJECT` mutation fires.
     - The project immediately leaves the table view.
  5. **Relational Verification:**
     - Navigate to **Clients**. Verify `Client A` still exists (Delete Cascade should *not* destroy the client).
     - Navigate to **Developers**. Verify all assigned developers still exist in the database.
     - Navigate to **Sites**. Verify the linked WP Site still exists (Project deletion should only nullify the link, not kill the WP site).

---

## Part 3: Extreme Edge Cases & Relational Deletion Flow

These tests confirm how the database cascades or restricts relational destruction.

### 3.1 Orphaned Data Check (Delete Linked Client)
* **Objective:** Ensure the system doesn't crash if a parent Client is removed.
* **Test Steps:**
  1. Ensure a pre-existing WP Site and Project are bound to `Client Z`.
  2. Go to `/clients` and **Delete `Client Z`**.
  3. Go to `/sites` and look at the WP Site.
  4. **Expected:** The site should NOT crash React. The client field should fail gracefully, either displaying "Unknown", becoming `null`, or the site itself might be cascaded away (depending on your specific database schema design for `ON DELETE CASCADE`).

### 3.2 Network Disconnect
* **Objective:** Validate frontend error states.
* **Test Steps:**
  1. Disconnect network or stop Docker backend.
  2. Try to perform any CRUD operation (Add Site, Add Project).
  3. **Expected:** The app should display an error toast via Apollo Server hooks (`"Network error: Failed to fetch"`) and should safely keep the user on the form without losing input data.
