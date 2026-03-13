# WordPress-Linked Projects Feature Plan

## Overview

When creating or editing a **Project**, the user can toggle **"Is this a WordPress website?"**.  
If enabled — and a site URL is provided — the project is automatically registered as a **WP Operations Site** (`wp_sites` table), making it appear in the Sites section alongside any manually added WP sites.  
If the toggle is later turned off, the linked WP site is detached (but not deleted).

---

## Data Flow

```
Project (is_wordpress=true, wp_site_url="https://example.com")
    │
    ├── createProject / updateProject mutation fires
    │
    ▼
Backend resolver checks is_wordpress + wp_site_url
    │
    ├── wp_sites.url doesn't exist → CREATE wp_sites row (linked via project_id FK)
    └── wp_sites.url already exists → link project_id to existing row (no duplicate)
    
Project detail page shows "🌐 WordPress Site" badge
WP Operations → Sites shows the site auto-linked
```

---

## Phase 1 — Database Migration (Backend)

### New migration: `add_wp_fields_to_projects`

Add two columns to the `projects` table:

```php
$table->boolean('is_wordpress')->default(false);
$table->string('wp_site_url', 500)->nullable();
$table->foreignUuid('wp_site_id')->nullable()->constrained('wp_sites')->nullOnDelete();
```

**`wp_site_id`** = FK to the auto-created (or matched) WpSite record.  
**`is_wordpress`** = toggle flag read by the frontend.  
**`wp_site_url`** = the URL entered by the user (used to create/find the WpSite).

---

## Phase 2 — Backend GraphQL Changes

### 2A — Update Project GraphQL Type

File: `app/GraphQL/Types/ProjectType.php`

Add fields:
```php
'is_wordpress' => ['type' => Type::boolean(), 'description' => 'Is this project a WordPress site?'],
'wp_site_url'  => ['type' => Type::string(),  'description' => 'WordPress site URL'],
'wp_site_id'   => ['type' => Type::string(),  'description' => 'Linked WP Site ID'],
'wp_site'      => ['type' => GraphQL::type('WpSite'), 'description' => 'Linked WP site record'],
```

Add resolver for `wp_site`:
```php
'wp_site' => fn($project) => $project->wpSite,
```

### 2B — Update Project Model

File: `app/Models/Project.php`

```php
protected $casts = [
    'is_wordpress' => 'boolean',
];

public function wpSite()
{
    return $this->belongsTo(WpSite::class, 'wp_site_id');
}
```

### 2C — Update CreateProjectMutation

File: `app/GraphQL/Mutations/CreateProjectMutation.php`

Add args:
```php
'is_wordpress'  => ['type' => Type::boolean()],
'wp_site_url'   => ['type' => Type::string()],
```

Add logic in `resolve()`:
```php
$project = Project::create([...existing fields...]);

if ($args['is_wordpress'] ?? false) {
    $wpSite = $this->findOrCreateWpSite($args['wp_site_url'], $project, $user);
    $project->update(['wp_site_id' => $wpSite->id]);
}
```

Add helper `findOrCreateWpSite()`:
```php
private function findOrCreateWpSite(string $url, Project $project, User $user): WpSite
{
    // Normalize URL
    $url = rtrim($url, '/');
    $domain = parse_url($url, PHP_URL_HOST) ?? $url;

    // Find or create a default WpClient for the project's client
    $wpClient = WpClient::firstOrCreate(
        ['email' => $project->client->email],
        ['name' => $project->client->name, 'slug' => Str::slug($project->client->name)]
    );

    return WpSite::firstOrCreate(
        ['url' => $url],
        [
            'wp_client_id' => $wpClient->id,
            'name'         => $project->name,
            'domain'       => $domain,
            'overall_health' => 'unknown',
        ]
    );
}
```

### 2D — Update UpdateProjectMutation

File: `app/GraphQL/Mutations/UpdateProjectMutation.php`

Add same args as 2C. Logic:

```php
if (isset($args['is_wordpress'])) {
    $project->update(['is_wordpress' => $args['is_wordpress'], 'wp_site_url' => $args['wp_site_url'] ?? null]);

    if ($args['is_wordpress'] && !empty($args['wp_site_url'])) {
        $wpSite = $this->findOrCreateWpSite($args['wp_site_url'], $project, $user);
        $project->update(['wp_site_id' => $wpSite->id]);
    } elseif (!$args['is_wordpress']) {
        // Detach — set wp_site_id to null (don't delete the WpSite)
        $project->update(['wp_site_id' => null]);
    }
}
```

### 2E — config/graphql.php

No changes needed — mutations already registered.

---

## Phase 3 — Frontend Changes

### 3A — Update GraphQL Mutations

File: `src/graphql/mutations.js`

Add `is_wordpress` and `wp_site_url` to `CREATE_PROJECT` and `UPDATE_PROJECT`:

```graphql
mutation CreateProject(
  $name: String!, $description: String, $client_id: String!, 
  $developer_ids: [String], $status: String, $budget: Float, 
  $due_date: String,
  $is_wordpress: Boolean,   # NEW
  $wp_site_url: String      # NEW
) {
  createProject(...args..., is_wordpress: $is_wordpress, wp_site_url: $wp_site_url) {
    id name status ... is_wordpress wp_site_url wp_site_id
  }
}
```

### 3B — Update GraphQL Queries

File: `src/graphql/queries.js`

Add `is_wordpress`, `wp_site_url`, `wp_site_id` fields to `GET_PROJECT` and `GET_PROJECTS`.

### 3C — ProjectCreate.jsx

File: `src/pages/projects/ProjectCreate.jsx`

Add to `EMPTY` state:
```js
const EMPTY = { ...existing..., is_wordpress: false, wp_site_url: '' };
```

Add WordPress section UI (after Project Details card):
```jsx
<FormCard title="WordPress Site" subtitle="Automatically add to WP Operations Sites">
  <FormControlLabel
    control={<Switch checked={form.is_wordpress} onChange={(e) => set('is_wordpress', e.target.checked)} />}
    label="This project is a WordPress website"
  />
  {form.is_wordpress && (
    <TextField
      label="WordPress Site URL *"
      fullWidth
      value={form.wp_site_url}
      onChange={(e) => set('wp_site_url', e.target.value)}
      placeholder="https://example.com"
      error={!!errors.wp_site_url}
      helperText={errors.wp_site_url || "Site will appear in WP Operations → Sites"}
    />
  )}
</FormCard>
```

Add validation:
```js
if (form.is_wordpress && !form.wp_site_url.trim()) {
  e.wp_site_url = 'WordPress site URL is required';
}
```

Add to mutation variables:
```js
is_wordpress: form.is_wordpress,
wp_site_url: form.is_wordpress ? form.wp_site_url : null,
```

### 3D — ProjectEdit.jsx

File: `src/pages/projects/ProjectEdit.jsx`

Pre-fill new fields from loaded project:
```js
setForm({
  ...existing,
  is_wordpress: p.is_wordpress ?? false,
  wp_site_url: p.wp_site_url ?? '',
});
```

Add same WordPress section UI as 3C.  
Pass same variables in `updateProject()` call.

### 3E — ProjectDetail.jsx (Badge)

File: `src/pages/projects/ProjectDetail.jsx`

Add a badge/chip in the project header:
```jsx
{project.is_wordpress && (
  <Chip
    icon={<Language />}
    label="WordPress Site"
    size="small"
    sx={{ bgcolor: '#EDE8FC', color: '#8E43F0', fontWeight: 700 }}
    clickable
    component="a"
    href={`/sites`}
  />
)}
```

Optionally add a "View in WP Operations" link if `project.wp_site_id` is set.

---

## Phase 4 — WP Sites List Auto-Link (No Extra Work)

The `wp_sites` table already powers the `/sites` page via `GET_WP_SITES` query.  
Once the backend creates the `wp_sites` row, it **automatically appears** in WP Operations → Sites.  
No frontend changes needed for the Sites list.

---

## Implementation Order

```
1. [ ] Backend migration     → add is_wordpress, wp_site_url, wp_site_id to projects
2. [ ] Project model         → add wpSite() relation + boolean cast
3. [ ] ProjectType           → add is_wordpress, wp_site_url, wp_site, wp_site_id fields
4. [ ] CreateProjectMutation → add args + findOrCreateWpSite() logic
5. [ ] UpdateProjectMutation → same as above
6. [ ] mutations.js          → add new args to CREATE_PROJECT + UPDATE_PROJECT
7. [ ] queries.js            → add new fields to GET_PROJECT + GET_PROJECTS
8. [ ] ProjectCreate.jsx     → WordPress toggle + URL field + validation
9. [ ] ProjectEdit.jsx       → same as above + pre-fill from loaded data
10.[ ] ProjectDetail.jsx     → WordPress badge + optional "View Site" link
11.[ ] Build + deploy
```

---

## Verification Plan

### Backend
```bash
# Run migration inside Docker
docker exec ai_agent_backend php artisan migrate

# Test via GraphQL (Postman / curl):
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"query":"mutation { createProject(name:\"WP Test\",client_id:\"...\",is_wordpress:true,wp_site_url:\"https://test.com\") { id is_wordpress wp_site_id wp_site { id name url } } }"}'

# Verify wp_sites record was created:
docker exec -it ai_agent_backend php artisan tinker
>>> \App\Models\WpSite::where('url','https://test.com')->first()
```

### Frontend (Browser)
1. Go to `http://localhost:3000/projects/create`
2. Fill in project name + client
3. Toggle "This project is a WordPress website" → URL field appears
4. Enter `https://my-client.com` → click Save
5. Navigate to `/sites` — confirm the new site appears in the WP Operations section
6. Navigate to the created project detail — confirm WordPress badge is shown
7. Go to `/projects/:id/edit` — confirm the toggle and URL are pre-filled
8. Turn the toggle OFF and save — confirm `wp_site_id` is nulled (site still appears in `/sites`)

### Edge Cases to Verify
- URL with trailing slash is normalized (`https://test.com/` → `https://test.com`)
- Duplicate URL: if same URL entered twice, should reuse existing `wp_sites` row (not create duplicate)
- Turning toggle OFF: project loses `wp_site_id` link, but site stays in `/sites`

---

## Files Affected Summary

| File | Change |
|---|---|
| `migrations/xxxx_add_wp_fields_to_projects.php` | **NEW** — 3 columns |
| `app/Models/Project.php` | `wpSite()` relation + cast |
| `app/GraphQL/Types/ProjectType.php` | 4 new fields |
| `app/GraphQL/Mutations/CreateProjectMutation.php` | 2 new args + WpSite logic |
| `app/GraphQL/Mutations/UpdateProjectMutation.php` | 2 new args + WpSite logic |
| `src/graphql/mutations.js` | 2 new args in CREATE + UPDATE |
| `src/graphql/queries.js` | 3 new fields in GET_PROJECT + GET_PROJECTS |
| `src/pages/projects/ProjectCreate.jsx` | WordPress card + toggle |
| `src/pages/projects/ProjectEdit.jsx` | WordPress card + toggle + pre-fill |
| `src/pages/projects/ProjectDetail.jsx` | WordPress badge |

> **No changes to `wp_sites` table, WP Sites list query, or Sites page UI.**
