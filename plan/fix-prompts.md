# Fix Prompts — Site Detail Issues
**Source:** `d:\test-website\plan\site-detail-test-plan.md` test run on 2026-03-13
**Target:** `d:\test-website\ai-agent\ai-agent-wp\` (frontend) and `d:\test-website\ai-agent\ai-agent-backend\` (backend)

---

## Issue I-1: Plugins Tab — Missing Search/Filter Bar

**Severity:** Medium  
**Affected file:** `d:\test-website\ai-agent\ai-agent-wp\src\pages\sites\SiteDetail.jsx`  
**Component:** `PluginsList` function (lines ~1099–1095)

### What's wrong
The `PluginsList` component renders all plugins in a table with no way to filter or search. With 15+ plugins, finding a specific one is difficult.

### Fix Prompt
```
In SiteDetail.jsx, inside the `PluginsList` function, add a live search/filter feature.

1. Add a `searchQuery` state:
   const [searchQuery, setSearchQuery] = useState('');

2. Filter the plugin list before rendering:
   const filteredPlugins = plugins.filter(p =>
     p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
     (p.slug ?? '').toLowerCase().includes(searchQuery.toLowerCase())
   );

3. In the header Box (between Typography "Plugins (X)" and the Refresh/Update All buttons),
   add a TextField search input:
   <TextField
     size="small"
     placeholder="Search plugins…"
     value={searchQuery}
     onChange={(e) => setSearchQuery(e.target.value)}
     InputProps={{
       startAdornment: (
         <InputAdornment position="start">
           <SearchIcon sx={{ fontSize: 16, color: 'text.secondary' }} />
         </InputAdornment>
       ),
     }}
     sx={{ width: 220, '& .MuiInputBase-input': { fontSize: '0.82rem' } }}
   />

4. Replace `{plugins.map(...)}}` in the TableBody with `{filteredPlugins.map(...)}`.

5. Show "No plugins match your search" empty state when filteredPlugins.length === 0:
   {filteredPlugins.length === 0 && (
     <TableRow>
       <TableCell colSpan={5} sx={{ textAlign: 'center', py: 4, color: 'text.secondary' }}>
         No plugins match "{searchQuery}"
       </TableCell>
     </TableRow>
   )}

Note: SearchIcon is already imported from @mui/icons-material.
```

---

## Issue I-2: Plugins Tab — Update Buttons Not Showing (Stale Data)

**Severity:** Medium  
**Affected files:**  
- `d:\test-website\ai-agent\ai-agent-wp\src\pages\sites\SiteDetail.jsx`  
- `d:\test-website\wp-content\plugins\active-auditor\includes\class-update-tracker.php`

### What's wrong
The Plugins tab shows all 15 plugins as "Up to Date" even though the portal header says 11 updates available. The `all_plugins` JSON in `wp_check_history` was written **before** the `update_available` flag fix in `class-update-tracker.php`. A fresh health check from the live site (after uploading the fixed plugin zip) will refresh the data.

Additionally, the plugin slug column shows a slug derived from the name (`p.name.toLowerCase().replace(/\s+/g, '-')`) instead of the actual WP file path (e.g. `woocommerce/woocommerce.php`). The backend sends `path` but the frontend doesn't use it.

### Fix Prompt — Part A: Fix file path display in `SiteDetail.jsx`
```
In PluginsList > TableBody > the plugin row primary TableCell (around line 1197–1198),
change:

CURRENT:
  <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace', fontSize: '0.7rem' }}>
    {p.name.toLowerCase().replace(/\s+/g, '-')}
  </Typography>

FIX TO:
  <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace', fontSize: '0.7rem' }}>
    {p.slug ?? p.id}
  </Typography>

This uses the actual WP plugin slug (e.g. `woocommerce`, `contact-form-7`) from the
stored data rather than a client-side derivation from the name.
```

### Fix Prompt — Part B: Re-upload AA plugin and run health check
```
Steps to refresh plugin update data:
1. Build a new zip of `d:\test-website\wp-content\plugins\active-auditor\` as active-auditor.zip
2. Upload to https://test.webdezign3.co.uk/wp-admin → Plugins → Delete old Active Auditor → Add New → Upload
3. In portal at http://localhost:3000/sites/a1484929-ccec-419b-b6ec-c0274b832fa2, click "Run Health Check"
4. Wait ~60s, then switch to Plugins tab — Update buttons should now appear for 11 plugins

Root cause: class-update-tracker.php fix (adding `slug`, `new_version`, `update_available` fields)
only takes effect after the fixed plugin version is running on the live site.
```

### Fix Prompt — Part C: Plugin update uses `p.id` as slug (potential bug)
```
In PluginsList, the doUpdate function calls:
  triggerUpdate({ variables: { id: siteId, type: 'plugin', item_slug: slug } })
where `slug` comes from `updatablePlugins.map(p => p.id)`.

The `id` field is set as: `id: p.path ?? p.slug ?? 'p${i}'`
And the AA plugin expects `item_slug` = the WP plugin file PATH (e.g. `woocommerce/woocommerce.php`)

Backend TriggerSiteUpdateMutation.php sends `plugin = item_slug` to the AA plugin's
POST /wp-json/active-auditor/v1/update-plugin endpoint.

Verify the AA plugin's update-plugin endpoint expects either the file path or the slug.
If it expects the file path, ensure `p.id` resolves to `p.path` (which is the WP file path).
The current mapping `id: p.path ?? p.slug` should be correct as long as `p.path` is set.

Action: in the plugin data mapping (SiteDetail.jsx ~line 1380), verify `p.path` is not null
by logging/inspecting `latestCheck.all_plugins` JSON for a plugin that needs update.
```

---

## Issue I-3: SEO Tab — All Checks Failing (Score = 0)

**Severity:** Medium  
**Affected files:**  
- `d:\test-website\ai-agent\ai-agent-backend\app\Jobs\RunWpHealthCheck.php` (`runSeoAnalysis`)
- `d:\test-website\wp-content\plugins\active-auditor\` (SEO endpoint)

### What's wrong
The SEO tab shows overall score = 0 and all 6 checks failing. The backend calls:
```
GET /wp-json/active-auditor/v1/seo-analysis?token=...&url=...
```
The AA plugin may be using a different endpoint name (`/guest/seo` vs `/seo-analysis`), or the response JSON structure doesn't match what `RunWpHealthCheck.php` expects.

The backend maps: `has_title => !empty($seo['page_title']['present'])` but if the plugin returns a flat structure like `has_title => true` directly, all mappings will silently fail and store zeroes.

### Fix Prompt
```
STEP 1 — Debug the SEO endpoint response.
Run this command to see what the AA plugin actually returns:

curl "https://test.webdezign3.co.uk/wp-json/active-auditor/v1/seo-analysis?token=YOUR_TOKEN&url=https://test.webdezign3.co.uk/" | jq .

Also try the guest endpoint:
curl "https://test.webdezign3.co.uk/wp-json/active-auditor/v1/guest/seo" | jq .

STEP 2 — In RunWpHealthCheck.php, function runSeoAnalysis() (line ~486):
The current endpoint is: /wp-json/active-auditor/v1/seo-analysis
If the AA plugin registers it differently (e.g. /guest/seo or /seo), update the URL.

STEP 3 — Fix field mapping in runSeoAnalysis() to handle multiple response shapes:
Current (only handles nested structure):
  'has_title' => !empty($seo['page_title']['present']),

Replace with defensive mapping:
  'has_title'        => !empty($seo['has_title']) || !empty($seo['page_title']['present']),
  'title_length'     => $seo['title_length'] ?? $seo['page_title']['length'] ?? 0,
  'has_meta_desc'    => !empty($seo['has_meta_desc']) || !empty($seo['meta_description']['present']),
  'meta_desc_length' => $seo['meta_desc_length'] ?? $seo['meta_description']['length'] ?? 0,
  'h1_count'         => $seo['h1_count'] ?? $seo['h1_tags']['count'] ?? 0,
  'has_canonical'    => !empty($seo['has_canonical']) || !empty($seo['canonical_tag']['present']),
  'has_og_tags'      => !empty($seo['has_og_tags']) || !empty($seo['og_tags']['complete']),
  'has_structured_data' => !empty($seo['has_structured_data']) || !empty($seo['structured_data']['has_structured_data']),

STEP 4 — Also check in class-seo-analyzer.php inside the AA plugin to confirm
the exact JSON keys returned by the seo-analysis endpoint. Make sure the plugin
registers the route as /active-auditor/v1/seo-analysis (not just /active-auditor/v1/seo).

STEP 5 — After fixing, run docker exec to deploy updated RunWpHealthCheck.php:
docker exec ai_agent_backend cp /var/www/html/app/Jobs/RunWpHealthCheck.php ... 
(or rebuild with docker-compose up -d --build)
Then run a health check and verify SEO tab shows non-zero scores.
```

---

## Issue I-4: Lighthouse — Accessibility, Best Practices, SEO Scores = 0

**Severity:** Low  
**Affected file:** `d:\test-website\ai-agent\ai-agent-backend\app\Jobs\RunWpHealthCheck.php` (`runLighthouseScan`)

### What's wrong
Performance = 70 but Accessibility = 0, Best Practices = 0, SEO score = 0. This means the Google PageSpeed API only returned the performance category, or the `category` parameter wasn't sent as a repeated query parameter correctly.

The backend sends:
```php
'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
```
Laravel's `Http::get()` serialises arrays as `category[0]=performance&category[1]=accessibility...`
but Google PageSpeed API expects **repeated** params: `category=performance&category=accessibility&category=best-practices&category=seo`.

### Fix Prompt
```
In RunWpHealthCheck.php, function runLighthouseScan() (line ~400), fix the category parameter:

CURRENT (broken — Laravel serialises as category[0]=...):
  $psiResp = Http::timeout(60)->get(
      'https://www.googleapis.com/pagespeedonline/v5/runPagespeed',
      [
          'url'      => $this->site->url,
          'key'      => $psiKey,
          'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
      ]
  );

FIX — Build URL manually with repeated category params:
  $queryString = http_build_query([
      'url' => $this->site->url,
      'key' => $psiKey,
  ]) . '&category=performance&category=accessibility&category=best-practices&category=seo';

  $psiResp = Http::timeout(60)->get(
      'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . $queryString
  );

After this fix, the $cats array will have all 4 categories and the scores will be correctly
stored. Deploy to backend and queue containers, then re-run a health check.
```

---

## Issue I-5: Google Services — All 7 Services Showing as Undetected

**Severity:** Low  
**Affected files:**  
- `d:\test-website\ai-agent\ai-agent-backend\app\Jobs\RunWpHealthCheck.php` (`runGoogleServicesDetection`)
- `d:\test-website\wp-content\plugins\active-auditor\` (guest/google-services endpoint)

### What's wrong
The sidebar shows "0 active" for all 7 Google Services (GA4, GTM, Ads, reCAPTCHA, Maps, Fonts, Search Console). The backend calls:
```
GET /wp-json/active-auditor/v1/guest/google-services?url=...
```
Either the AA plugin's endpoint returns a different JSON structure than what `RunWpHealthCheck.php` expects, or the live site actually doesn't have these services configured.

### Fix Prompt
```
STEP 1 — Verify what the endpoint actually returns:
curl "https://test.webdezign3.co.uk/wp-json/active-auditor/v1/guest/google-services?url=https://test.webdezign3.co.uk/" | jq .

STEP 2 — In RunWpHealthCheck.php, function runGoogleServicesDetection() (line ~540),
view the current field mapping around line 550–580 and compare against the actual response.

Common mismatch: backend expects `$gs['has_ga4']` but plugin returns `$gs['data']['ga4']` etc.

STEP 3 — Fix the mapping to handle wrapped 'data' key (same issue as full-report):
  $gsData = $gs['data'] ?? $gs;
  WpGoogleServices::create([
      ...
      'has_ga4'            => !empty($gsData['has_ga4']) || !empty($gsData['ga4']),
      'has_gtm'            => !empty($gsData['has_gtm']) || !empty($gsData['gtm']),
      'has_google_ads'     => !empty($gsData['has_google_ads']) || !empty($gsData['google_ads']),
      'has_recaptcha'      => !empty($gsData['has_recaptcha']) || !empty($gsData['recaptcha']),
      'has_maps'           => !empty($gsData['has_maps']) || !empty($gsData['google_maps']),
      'has_fonts'          => !empty($gsData['has_fonts']) || !empty($gsData['google_fonts']),
      'has_search_console' => !empty($gsData['has_search_console']) || !empty($gsData['search_console']),
  ]);

STEP 4 — If the endpoint simply doesn't exist in the AA plugin, add it:
In the AA plugin, register a route:
  register_rest_route('active-auditor/v1', '/guest/google-services', [
      'methods'  => 'GET',
      'callback' => [$this, 'get_google_services'],
      'permission_callback' => '__return_true',  // public
  ]);

The callback should scan the homepage HTML for GA4 (G-XXXXXXXX), GTM (GTM-XXXXX),
Google Ads (gtag/conversion), reCAPTCHA (recaptcha.net), Maps API, Fonts API, Search Console
(google-site-verification meta tag).

STEP 5 — Deploy updated files and re-run health check.
```

---

## Deployment Commands

After making any backend/queue changes, deploy with:

```powershell
# Rebuild and restart all containers
docker-compose up -d --build
```

Or copy individual files faster:
```powershell
# Backend only
docker cp d:\test-website\ai-agent\ai-agent-backend\app\Jobs\RunWpHealthCheck.php ai_agent_backend:/var/www/html/app/Jobs/RunWpHealthCheck.php
docker cp d:\test-website\ai-agent\ai-agent-backend\app\Jobs\RunWpHealthCheck.php ai_agent_queue:/var/www/html/app/Jobs/RunWpHealthCheck.php

# Frontend only (Vite hot reloads automatically — no copy needed)
```

After deploying, trigger a new health check from the portal to refresh all data.

---

## Priority Order

| Priority | Issue | Effort |
|---|---|---|
| 1 | I-4: Lighthouse multi-category fix | ~10 min — 1 line backend fix |
| 2 | I-1: Plugins search bar | ~20 min — frontend only |
| 3 | I-2: Plugin path display fix | ~5 min — 1 line frontend fix |
| 4 | I-3: SEO mapping fix | ~30 min — backend + AA plugin |
| 5 | I-5: Google Services mapping | ~30 min — backend + AA plugin |
