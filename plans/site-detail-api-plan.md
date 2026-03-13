# Fix Plan: Replacing Mock Data in SiteDetail.jsx & EditSite.jsx

## 1. Issue Analysis
**The Problem:** Accessing `http://localhost:3000/sites/[uuid]` for a newly created database site results in a crash or empty state. 
**The Cause:** In `src/pages/sites/SiteDetail.jsx` (around line 949), the component is trying to find the site via synchronous array searching through the hardcoded dummy data: 
```javascript
const [site, setSite] = useState(() => mockSites.find((s) => s.id === id) ?? null);
```
Since the newly created backend site UUID does not exist in the frontend `mockSites` array, `site` becomes `null`, and any subsequent component renders that look for `site.name` or `site.domain` will throw a fatal `TypeError` or render a blank error page. `EditSite.jsx` likely suffers from the exact same hardcoded lookup logic.

## 2. Proposed Solution
We need to refactor completely away from `mockSites` on the Site Detail and Edit Site pages, hooking them directly into the Apollo Client's `useQuery` using our backend GraphQL.

### 2.1 Refactor SiteDetail.jsx
*   **Action:** Replace `useState` mock lookup with `useQuery(GET_WP_SITE, { variables: { id } })`.
*   **Action:** Add `if (loading)` and `if (error)` early return states to safely render loading spinners and error screens.
*   **Action:** Modify the active plugins lists and health histories. Since some of these (like `PLUGINS_BY_SITE`) are still hardcoded arrays mapping specifically to dummy strings like `'s1'`, we need to either adapt them to the API data (`site.active_plugins`, `site.check_history`), or gracefully fallback to empty arrays/mock defaults without crashing the UUID search strings.
*   **Action:** Update the prop drilling to rely on `data.wpSite` rather than a local `site` state variable.

### 2.2 Re-verify Database Structure
*   **Action:** Ensure the `App\GraphQL\Queries\WpSiteQuery` on the backend correctly resolves the required fields (e.g., `wp_client`, `check_history`, `security_issues`) requested by the GraphQL schema in `queries.js`.

---

## 3. Prompts / Execution Plan

If you want me to automatically execute this fix phase, here are the step-by-step prompts/tasks I will run:

### Prompt 1: Implement GraphQL Fetching in SiteDetail.jsx
> "Refactor `src/pages/sites/SiteDetail.jsx`. Import `GET_WP_SITE` from `graphql/queries.js`. Replace the initialization of the `site` constant with a `useQuery` hook passing the `:id` parameter. Extract the `data.wpSite` object. Add a `<Box sx={{ display: 'flex', justifyContent: 'center', p: 5 }}><CircularProgress /></Box>` for the loading state. Remove imports and initializations relying on `mockSites`."

### Prompt 2: Fix Data Mapping within the Component
> "In `SiteDetail.jsx`, update all UI sections to map to the new GraphQL field schema. Update the `SiteRightPanel`, `SiteInfoGrid`, and `HealthBadge` props to ensure they don't break on `null` data fields. Ensure that if `PLUGINS_BY_SITE` is used, it falls back to the `default` array gracefully since the new site IDs are UUIDs."

### Prompt 3: Handle the Edit Flow Fixes
> "Verify if `EditSite.jsx` also uses `mockSites.find`. If so, remove `mockSites` and change it to either receive the `site` object via React Router `<Link state={{ site }}>` OR by firing another `useQuery(GET_WP_SITE)` before rendering the edit form fields. Ensure submitting the edit runs `UPDATE_WP_SITE`."

### Prompt 4: Build and Verify
> "Run `npm run build` on the frontend. Push the build to the local Docker container. Test navigating to the newly created UUID URL (`/sites/a1482cbb-...`) to confirm the React component mounts and displays the backend data flawlessly."
