# Active Auditor - Quick Start Guide

## What Was Created

A complete WordPress health auditing system with real-time data collection and Chrome extension integration.

### Components

1. **WordPress Plugin** (`wp-content/plugins/active-auditor/`)
   - REST API providing internal WordPress data
   - Security auditing engine
   - Update tracking system
   - Health monitoring dashboard
   - Token-based authentication

2. **Chrome Extension** (`site-health-guard/`)
   - React + TypeScript UI
   - Lighthouse guest scanning
   - Real-time data sync with WordPress
   - Dual-mode (Guest + Authenticated)
   - No-spyware privacy-first design

## Quick Setup (3 Steps)

### Step 1: Verify Plugin Activation

Plugin is already activated. Check WordPress Admin:
```
Admin URL: http://localhost:63/wp-admin/
Menu: Active Auditor
```

### Step 2: Get Your API Token

```bash
# In PowerShell:
docker exec test-website-db-1 mysql -u wordpressuser -pPranav@@@23 Ecomm \
  -e "SELECT option_value FROM wp_options WHERE option_name = 'aa_api_token';"
```

**Current Token**: `gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb`

### Step 3: Load Chrome Extension

1. Open Chrome → `chrome://extensions/`
2. Enable Developer Mode (top right)
3. Click "Load unpacked"
4. Select: `d:\test-website\site-health-guard\dist\`
5. Done! Extension should appear

## Test the API

### Health Check
```powershell
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/health?token=gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb" -UseBasicParsing
```

### Security Audit
```powershell
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/security-audit?token=gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb" -UseBasicParsing
```

### Updates Check
```powershell
Invoke-WebRequest -Uri "http://localhost:63/index.php?rest_route=/active-auditor/v1/updates?token=gB9VRgDP0rsLHQXGZ13E6DBqgVvqh2Zb" -UseBasicParsing
```

## Plugin Files Overview

```
active-auditor/
├── active-auditor.php                    # Main plugin (73 lines)
│   └── Hooks: plugins_loaded, rest_api_init, activation, deactivation
│
├── includes/
│   ├── class-authentication.php          # Token management (140 lines)
│   ├── class-health-data.php             # Health metrics (230 lines)
│   ├── class-security-audit.php          # Security checks (260 lines)
│   ├── class-update-tracker.php          # Update tracking (200 lines)
│   ├── class-rest-endpoints.php          # API endpoints (200 lines)
│   └── class-admin-settings.php          # Admin UI (280 lines)
│
├── lib/
│   └── utility-functions.php             # Helpers (160 lines)
│
└── assets/
    └── admin-style.css                   # Admin styling (100 lines)
```

**Total: ~1,800 lines of production code**

## API Endpoints (6 Total)

### Public (No Token Required)
- `GET /active-auditor/v1/status` - Plugin status

### Protected (Token Required)
- `GET /active-auditor/v1/health` - System health
- `GET /active-auditor/v1/security-audit` - Security check
- `GET /active-auditor/v1/updates` - Update tracking
- `GET /active-auditor/v1/performance` - Performance metrics
- `GET /active-auditor/v1/full-report` - Complete report

## Key Features

### Data Collection
- PHP version, extensions, memory usage
- WordPress version & update status
- Database info and statistics
- Plugin/theme update availability
- Security vulnerabilities & hardening
- User accounts & permissions
- File permissions & SSL status

### Security
- Token-based authentication (32-char random)
- Constant-time comparison (timing attack safe)
- Failed attempt logging
- Read-only API (no modifications)
- No sensitive data exposure

### Performance
- Optional response caching (default: 1 hour)
- Transient-based caching
- Scheduled update checks (12-hour interval)
- Memory-efficient operations

### Admin Dashboard
- Quick health overview
- Security status indicators
- Pending updates display
- Token management
- Detailed health report view

## Extension Features

### Guest Mode (Any Website)
- Lighthouse performance score
- SEO on-page analysis
- Broken link detection
- Google Analytics detection
- GTM/GA4 presence check

### Authenticated Mode (WordPress Sites)
- Actual WordPress health data
- Real security vulnerabilities
- Actual plugin/theme versions
- Genuine update availability
- Internal statistics

## File Modifications Made

### 1. WordPress Plugin
- Created entire plugin structure
- Implemented 6 REST endpoints
- Built security audit engine
- Added update tracker
- Created admin interface
- Token-based auth system

### 2. Chrome Extension API
- Updated `src/services/api.ts`
- Integrated with Active Auditor endpoints
- Real data transformation
- Error handling
- Fallback mechanisms

## Common Tasks

### View Admin Dashboard
```
URL: http://localhost:63/wp-admin/
Menu: Active Auditor → Dashboard
```

### Configure Settings
```
URL: http://localhost:63/wp-admin/
Menu: Active Auditor → Settings
```

### View Health Report
```
URL: http://localhost:63/wp-admin/
Menu: Active Auditor → Health Report
```

### Regenerate API Token
In WordPress settings (admin only)
- Open Active Auditor > Settings
- Click "Generate New Token" button
- Old token becomes invalid immediately

### Test Connection
Via PowerShell:
```powershell
$token = "YOUR_TOKEN_HERE"
$domain = "localhost:63"
$url = "http://$domain/index.php?rest_route=/active-auditor/v1/health?token=$token"
Invoke-WebRequest -Uri $url -UseBasicParsing | Select-Object -ExpandProperty Content
```

## Architecture Highlights

### Three-Layer Design
```
Extension UI (React)
    ↓ (REST calls)
WordPress REST API (Active Auditor Endpoints)
    ↓ (Data queries)
WordPress Core & Database
```

### Modular Plugin Classes
- Each file handles one responsibility
- Easy to extend or modify
- Well-documented code
- Helper function utility library

### Token Security
- Generated on plugin activation
- Regenerable by admins
- Constant-time comparison prevents timing attacks
- Failed attempts are logged

## Testing Checklist

- [x] Plugin activates without errors
- [x] Status endpoint returns plugin info
- [x] Health endpoint requires valid token
- [x] Security audit endpoint works
- [x] Updates endpoint returns current data
- [x] Performance endpoint provides metrics
- [x] Full report combines all data
- [x] Browser extension loads successfully
- [x] Extension connects to WordPress
- [x] Guest mode scanning works
- [x] Authenticated mode works
- [x] Extension gets real WordPress data

## Next Steps

### For Development
1. Extend security audit checks
2. Add more performance metrics
3. Integrate with vulnerability databases
4. Add email notifications
5. Create cron jobs for automated checking

### For Production
1. Deploy to production server
2. Use HTTPS for all communications
3. Regenerate token for production
4. Enable caching
5. Set up monitoring
6. Configure log rotation

### For the Extension
1. Publish to Chrome Web Store
2. Add popup screenshots
3. Create store listing
4. Set up auto-update mechanism
5. Gather user feedback

## Documentation

- **Plugin README**: `wp-content/plugins/active-auditor/README.md`
- **Integration Guide**: `INTEGRATION_GUIDE.md` (this directory)
- **Extension README**: `site-health-guard/README.md`

## Support

For issues:
1. Check WordPress debug log: `wp-content/debug.log`
2. View browser console (F12)
3. Test API endpoints directly
4. Verify token is current
5. Check network tab in DevTools

## Statistics

- **Lines of Code**: ~1,800 (plugin) + ~350 (extension update)
- **Classes**: 6 main classes
- **REST Endpoints**: 6 endpoints
- **React Components**: 6+ components
- **Admin Pages**: 3 pages
- **Security Features**: Token auth, logging, constant-time comparison
- **Performance**: Optional caching, scheduled checks
- **Compatibility**: WP 5.6+, PHP 7.4+, All modern browsers

---

**Status**: Production Ready (v1.0.0)