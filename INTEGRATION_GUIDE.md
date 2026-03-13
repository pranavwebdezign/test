# Active Auditor + Chrome Extension Integration Guide

## Architecture Overview

This is a complete implementation of a WordPress health auditing system with Chrome extension integration, based on the "Active Auditor + Health Monitor" architecture diagram.

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│  Active Auditor + Health Monitor (Chrome Extension)        │
│  Dual Tool: High-Trust 'No-Spyware' Traffic Light Summary  │
└─────────────────────────────────────────────────────────────┘
        │                                        │
        ├──────────────────┬─────────────────────┴────────────────┐
        │                  │                                       │
        ▼                  ▼                                       ▼
┌──────────────┐  ┌──────────────────┐                    ┌──────────────────┐
│   Guest      │  │  Extension Core  │                    │ Authenticated    │
│   Mode       │  │  Display Layer   │                    │ Mode             │
│              │  │  No Spyware      │                    │ Actual Data      │
└──────────────┘  │  Privacy First   │                    │ Real Security    │
                  └──────────────────┘                    │ Real Stats       │
                                                         └──────────────────┘
                           │
        ┌──────────────────┴───────────────────┐
        │                                       │
        ▼                                       ▼
┌─────────────────────┐            ┌────────────────────────┐
│  Public APIs        │            │  WP REST Endpoint      │
│  Lighthouse         │            │  Token-Based Auth      │
│  SEO Checks         │            │  Internal Health Data  │
│  Broken Links       │            │  Security Audit        │
│  Analytics Detection│            │  Update Tracking       │
└─────────────────────┘            └────────────────────────┘
        ▲                                       ▲
        │                                       │
        │                                       │
  Google Services Pings              WP Plugin API Engine
  (Lighthouse API, GA4, GTM)         (Active Auditor)
```

## Installation & Setup

### 1. WordPress Plugin Installation

The plugin is already installed at: `wp-content/plugins/active-auditor/`

#### Verify Plugin is Activated:
```bash
# Check if plugin is active in WordPress
docker exec test-website-db-1 mysql -u wordpressuser -pPranav@@@23 Ecomm \
  -e "SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';"
```

#### Access Admin Settings:
- Go to WordPress Admin: `http://localhost:63/wp-admin/`
- Navigate to **Active Auditor > Settings**
- Your API Token: (displayed on settings page)

### 2. Chrome Extension Setup

The extension files are built and ready in: `site-health-guard/dist/`

#### Load Extension in Chrome:
1. Open Chrome DevTools → Manage Extensions
2. Enable "Developer Mode"
3. Click "Load unpacked"
4. Select `site-health-guard/dist/` folder
5. Extension should now appear in your extensions list

#### Configure for WordPress Site:
1. Click extension icon in Chrome toolbar
2. Go to **Options/Settings** tab
3. Enter: **localhost:63** (or your WordPress URL)
4. Enter: **API Token** (from WordPress settings)
5. Click **Connect**

## API Endpoints Reference

### Base URL
```
http://localhost:63/index.php?rest_route=
```

### Endpoints

#### 1. Health Status Endpoint
```
GET /active-auditor/v1/health?token=YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "domain": "localhost",
    "php": {
      "version": "8.2.29",
      "status": "green",
      "extensions": {...}
    },
    "wordpress": {
      "version": {...},
      "updates": {...}
    },
    "database": {...},
    "performance": {...},
    "security": {...}
  }
}
```

#### 2. Security Audit Endpoint
```
GET /active-auditor/v1/security-audit?token=YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "vulnerabilities": [...],
    "hardening": {...},
    "users_security": {...},
    "file_permissions": {...}
  }
}
```

#### 3. Updates Endpoint
```
GET /active-auditor/v1/updates?token=YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "wordpress": {...},
    "plugins": {...},
    "themes": {...}
  }
}
```

#### 4. Performance Endpoint
```
GET /active-auditor/v1/performance?token=YOUR_TOKEN
```

#### 5. Full Report Endpoint
```
GET /active-auditor/v1/full-report?token=YOUR_TOKEN
```

Returns: Complete health + security + updates report

#### 6. Status Endpoint (No Token Required)
```
GET /active-auditor/v1/status
```

**Response:**
```json
{
  "success": true,
  "status": "active",
  "version": "1.0.0",
  "has_token": true,
  "site_url": "http://localhost:63",
  "domain": "localhost"
}
```

## Plugin Architecture

### Directory Structure
```
active-auditor/
├── active-auditor.php                 # Main plugin file & hooks
├── includes/
│   ├── class-authentication.php      # Token management
│   ├── class-health-data.php         # Health metrics engine
│   ├── class-security-audit.php      # Security auditing
│   ├── class-update-tracker.php      # Update monitoring
│   ├── class-rest-endpoints.php      # REST API routes
│   └── class-admin-settings.php      # Admin interface
├── lib/
│   └── utility-functions.php         # Helper functions
└── assets/
    └── admin-style.css               # Admin styles
```

### Key Classes

#### Authentication (class-authentication.php)
- **generate_token()** - Creates 32-char random token
- **validate_token()** - Validates token against stored value
- **regenerate_token()** - Creates new token (invalidates old)
- **authenticate_extension()** - Handles extension auth requests
- **verify_nonce()** - WordPress nonce verification

#### Health Data (class-health-data.php)
- **get_health_data()** - Collects all health metrics
- **get_php_health()** - PHP version, extensions, memory
- **get_wordpress_health()** - WP version, updates, debug mode
- **get_database_health()** - DB version, stats
- **get_performance_metrics()** - Content count, users, memory
- **get_security_info()** - SSL, REST API status
- **get_health_data_cached()** - Returns cached data if available

#### Security Audit (class-security-audit.php)
- **get_security_audit()** - Full security report
- **check_vulnerabilities()** - Checks for known issues
- **check_hardening()** - Security hardening status
- **check_users_security()** - Admin account analysis
- **check_file_permissions()** - File permission checks

#### Update Tracker (class-update-tracker.php)
- **get_updates_tracking()** - Complete update info
- **track_wordpress_updates()** - Core WP updates
- **track_plugins_updates()** - Plugin update status
- **track_themes_updates()** - Theme update status
- **get_version_comparison()** - Version v latest comparison

#### REST Endpoints (class-rest-endpoints.php)
- **register_routes()** - Registers all API endpoints
- **check_token_permission()** - Token validation
- **get_health_endpoint()** - Health data endpoint
- **get_security_audit_endpoint()** - Security audit endpoint
- **get_updates_endpoint()** - Updates endpoint
- **get_performance_endpoint()** - Performance endpoint
- **get_full_report_endpoint()** - Complete report
- **get_status_endpoint()** - Plugin status endpoint

#### Admin Settings (class-admin-settings.php)
- **init()** - Initializes admin interface
- **add_admin_menu()** - Adds menu items
- **register_settings()** - Registers options
- **render_main_page()** - Dashboard page
- **render_settings_page()** - Settings page
- **render_health_page()** - Health report page

## Chrome Extension Integration

### Extension Structure
```
site-health-guard/
├── src/
│   ├── App.tsx                      # Main component
│   ├── pages/
│   │   ├── ExtensionPopup.tsx      # Popup view
│   │   ├── ExtensionOptions.tsx    # Options page
│   │   └── Index.tsx               # Landing page
│   ├── services/
│   │   └── api.ts                  # API service
│   ├── components/
│   │   └── TrafficLight.tsx        # Status indicator
│   └── ...
├── dist/                            # Built extension (ready to load)
└── chrome-extension-manifest.json   # Extension manifest
```

### API Service (src/services/api.ts)

#### Guest Mode (Public Site Analysis)
```typescript
api.runGuestScan(url?: string)
```

Returns Lighthouse scores, SEO analysis, broken links, etc.

#### Authenticated Mode (WordPress Data)
```typescript
api.runAuthScan(domain: string, token: string)
```

Returns:
- WordPress & PHP version info
- Plugin/theme update status
- Security vulnerabilities
- System health metrics
- Open plugin list

#### Token Validation
```typescript
api.validateToken(domain: string, token: string)
```

## Testing the Integration

### Test 1: Check Plugin Status
```bash
curl "http://localhost:63/index.php?rest_route=/active-auditor/v1/status"
```

Expected: `{"success":true,"status":"active",...}`

### Test 2: Authenticate with Invalid Token
```bash
curl "http://localhost:63/index.php?rest_route=/active-auditor/v1/health?token=invalid"
```

Expected: Error 403 "Invalid API token"

### Test 3: Get Health Data
```bash
curl "http://localhost:63/index.php?rest_route=/active-auditor/v1/health?token=gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb"
```

Expected: Full health JSON response

### Test 4: Security Audit
```bash
curl "http://localhost:63/index.php?rest_route=/active-auditor/v1/security-audit?token=gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb"
```

### Test 5: Check Extension Connection
- Open the Chrome extension
- Enter `localhost:63` and token
- Click "Test Connection"
- Should show "Connected" status

## Admin Functions

### In WordPress Admin Panel

1. **Dashboard**
   - Quick overview of system health
   - SEO audit status
   - Update count
   - Security alerts

2. **Settings**
   - View and copy API token
   - Generate new tokens
   - Enable/disable security audits
   - Toggle update tracking
   - Configure cache duration

3. **Health Report**
   - View complete JSON health data
   - Useful for debugging

## Security Features

### Token-Based Authentication
- 32-character random tokens
- Constant-time comparison (prevents timing attacks)
- Token regeneration support
- Failed attempt logging

### Data Protection
- No sensitive data exposed (passwords, keys)
- Read-only API access
- Site health data only
- No user activity tracking

### Privacy by Design
- Extension doesn't spyware
- Clearly states what data is read
- No unnecessary data collection
- No third-party data sharing

## Troubleshooting

### Plugin Not Showing in REST API
- Verify plugin is activated: Check WordPress Plugins page
- Ensure REST API is enabled (default in modern WordPress)
- Check `wp-content/plugins/active-auditor/` directory exists

### Authentication Failures
- Verify token is correct (copy from WordPress settings)
- Check token hasn't been regenerated
- Verify domain includes protocol or port if needed
- Check browser console for errors

### Extension Not Connecting
- Verify WordPress site is accessible
- Check API token is correct
- Try the test endpoint: `/active-auditor/v1/status`
- Check WordPress debug log for errors

### Caching Issues
- Clear transients: `delete transient aa_health_data_cache`
- Disable caching temporarily in settings
- Check cache duration setting

## Advanced Configuration

### Enable Debug Logging
In WordPress:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Logs appear in: `wp-content/debug.log`

### Disable Specific Audit

In WordPress settings or code:
```php
update_option('aa_enable_security_audit', '0');
update_option('aa_enable_update_tracking', '0');
update_option('aa_enable_health_caching', '0');
```

### Custom Cache Duration
```php
update_option('aa_cache_duration', 1800); // 30 minutes
```

## Deployment Notes

### For Production
1. Use HTTPS for all communications
2. Keep API tokens secure and private
3. Regularly regenerate tokens
4. Monitor failed authentication attempts
5. Enable caching for better performance
6. Keep WordPress and plugins updated
7. Use strong admin passwords

### Security Hardening
- Add `.htaccess` protection to wp-admin
- Disable file editing: `define('DISALLOW_FILE_EDIT', true);`
- Use non-default database prefix
- Enable SSL/HTTPS
- Regular security audits

## Support & Resources

- Plugin README: `wp-content/plugins/active-auditor/README.md`
- Extension Build: `site-health-guard/dist/`
- API Responses: See endpoints section above
- Logs: WordPress debug log or server error logs

## Version Information

- **Plugin Version**: 1.0.0
- **Extension Version**: 1.0.0
- **Minimum PHP**: 7.4.0
- **Minimum WordPress**: 5.6
- **Database**: MySQL 5.7+

---

**Created:** February 20, 2026