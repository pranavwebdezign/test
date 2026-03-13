# Active Auditor - Health Monitor

A comprehensive WordPress health auditor plugin with Chrome extension integration. Provides detailed insights into your WordPress site's health, security, and performance.

## Features

### Data Engine
- **Zero Front-End Impact**: Runs purely as a backend data engine
- **Comprehensive Health Monitoring**: PHP, WordPress, Database, Performance, and Security checks
- **Security Auditing**: Vulnerability detection, hardening checks, user security, and file permissions
- **Update Tracking**: Monitors WordPress core, plugins, and theme updates
- **Performance Metrics**: Memory usage, database stats, content inventory
- **Caching**: Optional health data caching for improved performance

### Chrome Extension Integration
- **Token-Based Authentication**: Secure API access
- **Multiple Endpoints**:
  - `/health` - System health data
  - `/security-audit` - Security vulnerability checks
  - `/updates` - Update tracking information
  - `/performance` - Performance metrics
  - `/full-report` - Complete health report
  - `/status` - Plugin status (no token required)

### Admin Features
- **Dashboard**: Quick overview of system health
- **Settings Page**: Configure plugin options and manage API token
- **Health Report**: Detailed JSON report view
- **Security Configuration**: Enable/disable security checks

## Installation

1. Upload the `active-auditor` folder to `/wp-content/plugins/`
2. Activate the plugin from the Plugins menu in WordPress
3. Navigate to **Active Auditor > Settings** in the WordPress admin
4. Copy your API token
5. Use the token in the Chrome extension for authenticated access

## Usage

### For WordPress Administrators

1. **Access the Dashboard**:
   - Go to Active Auditor from the WordPress admin menu
   - View quick health status, security alerts, and pending updates

2. **Configure Settings**:
   - Navigate to Active Auditor > Settings
   - Manage API token
   - Enable/disable specific audit features
   - Set cache duration for performance

3. **View Health Reports**:
   - Click on Health Report tab
   - View detailed JSON data for debugging

### For Chrome Extension Users

1. **Install the Extension**:
   - Load the built extension in Chrome (see extension build instructions)

2. **Add Site (Authenticated Mode)**:
   - Click the extension icon
   - Enter your WordPress site URL
   - Paste the API token (from WordPress Settings)
   - Extension will fetch internal health data

3. **View Reports**:
   - **Health Tab**: PHP, WordPress versions, updates, security status
   - **Security Tab**: Vulnerabilities, hardening status
   - **Performance Tab**: Database, memory, content metrics

## API Endpoints

All endpoints require the `token` parameter for authentication.

### GET /wp-json/active-auditor/v1/health?token=YOUR_TOKEN
Returns comprehensive system health data.

**Response**:
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "php": {
      "version": "8.2.29",
      "extensions": {...}
    },
    "wordpress": {
      "version": {...}
    },
    "database": {...},
    "performance": {...},
    "security": {...}
  }
}
```

### GET /wp-json/active-auditor/v1/security-audit?token=YOUR_TOKEN
Returns security audit results.

**Response**:
```json
{
  "success": true,
  "data": {
    "vulnerabilities": [...],
    "hardening": {...},
    "users_security": {...}
  }
}
```

### GET /wp-json/active-auditor/v1/updates?token=YOUR_TOKEN
Returns update tracking information.

**Response**:
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

### GET /wp-json/active-auditor/v1/performance?token=YOUR_TOKEN
Returns performance metrics.

### GET /wp-json/active-auditor/v1/full-report?token=YOUR_TOKEN
Returns complete health, security, and update report.

### GET /wp-json/active-auditor/v1/status
Returns plugin status (no token required).

## Architecture

### Plugin Structure

```
active-auditor/
├── active-auditor.php              # Main plugin file
├── includes/
│   ├── class-authentication.php    # Token validation & sessions
│   ├── class-health-data.php       # Health data engine
│   ├── class-security-audit.php    # Security auditing
│   ├── class-update-tracker.php    # Update tracking
│   ├── class-rest-endpoints.php    # REST API endpoints
│   └── class-admin-settings.php    # Admin interface
├── lib/
│   └── utility-functions.php       # Helper functions
└── assets/
    └── admin-style.css             # Admin styles
```

### Class Responsibilities

- **Authentication**: Token generation, validation, and session management
- **Health Data**: Collects and computes health metrics
- **Security Audit**: Runs security checks and hardening analysis
- **Update Tracker**: Monitors and tracks available updates
- **REST Endpoints**: Provides API endpoints for Chrome extension
- **Admin Settings**: WordPress admin interface and configuration

## Security

- **Token Validation**: All API requests require a valid token
- **Secure Token Generation**: 32-character random tokens
- **Constant-Time Comparison**: Prevents timing attacks on token validation
- **Request Logging**: Failed authentication attempts are logged
- **No Sensitive Data**: Plugin doesn't expose passwords or private keys
- **Read-Only Access**: Extension can only read health data, not modify

## Performance

- **Optional Caching**: Health data can be cached via transients
- **Configurable Cache Duration**: Default 1 hour, adjustable
- **Scheduled Updates**: Automatic update checks every 12 hours
- **Minimal Overhead**: Zero front-end impact, backend-only operation

## Filters & Hooks

### Filters

- `aa_health_data` - Modify health data before returning
- `aa_security_audit` - Modify security audit results
- `aa_update_tracking` - Modify update tracking data

### Actions

- `active_auditor_cache_health_data` - Triggered on hourly schedule

## Debug Mode

Enable debug logging in WordPress:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

All Active Auditor debug messages will appear in `/wp-content/debug.log`.

## Limitations

- Requires REST API enabled (default in modern WordPress)
- Database analysis requires sufficient permissions
- Some security checks may vary based on server configuration
- Update checks depend on WordPress.org API

## Support

For issues and feature requests, please visit the plugin homepage.

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Complete health monitoring
- Security auditing engine
- Update tracking system
- REST API with token authentication
- WordPress admin interface
- Chrome extension integration