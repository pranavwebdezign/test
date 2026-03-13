# Active Auditor - Testing Credentials

## Current API Token
**Token**: `gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb`

## Site Information
- **URL**: http://localhost:63
- **Admin**: http://localhost:63/wp-admin/
- **API Base**: http://localhost:63/index.php?rest_route=

## Quick Test Commands

### PowerShell Tests

#### 1. Check Plugin Status
```powershell
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/status" -UseBasicParsing | Select-Object -ExpandProperty Content
```

**Expected Response:**
```json
{"success":true,"status":"active","version":"1.0.0","has_token":true,"site_url":"http://localhost:63","domain":"localhost"}
```

#### 2. Get Health Data
```powershell
$token = "gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb"
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/health?token=$token" -UseBasicParsing | Select-Object -ExpandProperty Content | ConvertFrom-Json | ConvertTo-Json -Depth 3
```

**Response includes:**
- PHP version and extensions
- WordPress version and updates
- Database information
- Performance metrics
- Security status

#### 3. Get Security Audit
```powershell
$token = "gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb"
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/security-audit?token=$token" -UseBasicParsing | Select-Object -ExpandProperty Content | ConvertFrom-Json | ConvertTo-Json -Depth 3
```

**Response includes:**
- Vulnerabilities list
- Hardening status
- User security info
- File permissions

#### 4. Get Update Information
```powershell
$token = "gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb"
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/updates?token=$token" -UseBasicParsing | Select-Object -ExpandProperty Content | ConvertFrom-Json | ConvertTo-Json -Depth 3
```

**Response includes:**
- WordPress update availability
- Plugin update list
- Theme update list
- Version comparison

#### 5. Get Performance Metrics
```powershell
$token = "gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb"
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/performance?token=$token" -UseBasicParsing | Select-Object -ExpandProperty Content | ConvertFrom-Json | ConvertTo-Json -Depth 3
```

#### 6. Test Invalid Token (Should Fail)
```powershell
$token = "invalid_token_12345"
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/health?token=$token" -UseBasicParsing
```

**Expected Response**: 403 Forbidden with "Invalid API token" message

#### 7. Get Full Report
```powershell
$token = "gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb"
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/full-report?token=$token" -UseBasicParsing | Select-Object -ExpandProperty Content | ConvertFrom-Json | ConvertTo-Json -Depth 2
```

**Response includes:**
- Complete health data
- Full security audit
- All update information
- Timestamp

## WordPress Admin Access

### Login
- **URL**: http://localhost:63/wp-admin/
- **Admin User**: (use existing WordPress admin account)

### Active Auditor Admin Pages

#### Dashboard
- **Path**: Active Auditor > Dashboard
- **Shows**: System health overview, security status, pending updates

#### Settings
- **Path**: Active Auditor > Settings
- **Features**:
  - View/copy API token
  - Generate new token
  - Enable/disable security audit
  - Enable/disable update tracking
  - Configure cache duration

#### Health Report
- **Path**: Active Auditor > Health Report
- **Shows**: Complete health data in JSON format

## Database Queries

### Get API Token from Database
```bash
docker exec test-website-db-1 mysql -u wordpressuser -pPranav@@@23 Ecomm \
  -e "SELECT option_value FROM wp_options WHERE option_name = 'aa_api_token';"
```

### Get Active Plugins
```bash
docker exec test-website-db-1 mysql -u wordpressuser -pPranav@@@23 Ecomm \
  -e "SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';"
```

### Check Plugin Settings
```bash
docker exec test-website-db-1 mysql -u wordpressuser -pPranav@@@23 Ecomm \
  -e "SELECT * FROM wp_options WHERE option_name LIKE 'aa_%';"
```

### Regenerate Token (Admin Only)
```sql
UPDATE wp_options 
SET option_value = 'NEW_32_CHAR_TOKEN_HERE' 
WHERE option_name = 'aa_api_token';
```

## File Locations

### WordPress Plugin
```
d:\test-website\wp-content\plugins\active-auditor\
├── active-auditor.php
├── includes/
│   ├── class-authentication.php
│   ├── class-health-data.php
│   ├── class-security-audit.php
│   ├── class-update-tracker.php
│   ├── class-rest-endpoints.php
│   └── class-admin-settings.php
├── lib/
│   └── utility-functions.php
├── assets/
│   └── admin-style.css
└── README.md
```

### Chrome Extension
```
d:\test-website\site-health-guard\
├── src/
│   ├── services/api.ts       (Updated API service)
│   ├── pages/ExtensionPopup.tsx
│   └── ... (other files)
├── dist/                     (Built and ready)
│   ├── index.html
│   ├── assets/
│   │   └── index-*.js
│   └── ... (build output)
└── README.md
```

## Test Scenarios

### Scenario 1: First Time Connection
1. Get token from WordPress admin
2. Copy it to clipboard
3. Load Chrome extension
4. Enter `localhost:63` and paste token
5. Click "Test Connection"
6. Should show "Connected" and display health data

### Scenario 2: Security Check
1. Run security audit endpoint
2. Review vulnerabilities list
3. Check for:
   - Outdated WordPress version
   - Missing SSL
   - Debug mode enabled
   - Default database prefix
   - Unprotected wp-admin

### Scenario 3: Update Management
1. Run updates endpoint
2. Check plugin update count
3. Note which plugins need updates
4. Check theme update availability
5. Review core WordPress version status

### Scenario 4: Token Regeneration
1. Open WordPress admin
2. Go to Active Auditor > Settings
3. Click "Generate New Token"
4. Confirm the action
5. Old token becomes invalid
6. Extension needs new token to connect

### Scenario 5: Health Dashboard
1. Go to WordPress admin
2. Active Auditor > Dashboard
3. Review cards showing:
   - PHP version
   - WordPress version
   - Security status
   - Updates available

## Expected API Responses

### Health Endpoint Response
```json
{
  "success": true,
  "data": {
    "timestamp": "2026-02-20 13:57:23",
    "domain": "localhost",
    "connected": true,
    "php": {
      "version": "8.2.29",
      "status": "green",
      "extensions": {"curl": "yes", "json": "yes", ...}
    },
    "wordpress": {
      "version": {"current": "6.8.2", "status": "red"},
      ...
    },
    "database": {...},
    "performance": {...},
    "security": {...}
  },
  "timestamp": "2026-02-20 13:57:23"
}
```

### Security Audit Response
```json
{
  "success": true,
  "data": {
    "timestamp": "2026-02-20 13:57:31",
    "vulnerabilities": [
      {"label": "...", "status": "red", "severity": "critical"}
    ],
    "hardening": {...},
    "users_security": {...},
    "file_permissions": {...}
  },
  "timestamp": "2026-02-20 13:57:31"
}
```

## Troubleshooting

### Token Not Working
1. Verify token hasn't been regenerated
2. Copy exact token from WordPress admin (no spaces)
3. Use correct API URL format
4. Check WordPress is running: `docker ps`

### Extension Not Loading
1. Check Chrome: `chrome://extensions/`
2. Verify Developer Mode is ON
3. Load from correct path: `dist/` folder
4. Check for JavaScript errors in DevTools (F12)

### API Returning 404
1. Verify plugin is activated
2. Check plugin files exist: `wp-content/plugins/active-auditor/`
3. Check WordPress REST API enabled
4. Try `/status` endpoint (no token required)

### Connection Timeout
1. Verify WordPress container is running
2. Check port 63 is accessible: `http://localhost:63/`
3. Try from a different terminal
4. Check Docker logs: `docker logs test-website-wordpress-1`

## Support Information

- **Plugin Version**: 1.0.0
- **Extension Version**: 1.0.0
- **API Version**: v1
- **Environment**: Docker (MySQL 5.7, WordPress Latest, PHP 8.2.29)
- **Date Created**: February 20, 2026

## Quick Copy-Paste

### Copy Token
```
gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb
```

### Copy Health Check URL
```
http://localhost:63/index.php?rest_route=/active-auditor/v1/health?token=gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb
```

### Copy WordPress Admin URL
```
http://localhost:63/wp-admin/
```

### Copy Plugin Settings URL
```
http://localhost:63/wp-admin/admin.php?page=active-auditor-settings
```

---

**All tests confirmed working as of February 20, 2026**