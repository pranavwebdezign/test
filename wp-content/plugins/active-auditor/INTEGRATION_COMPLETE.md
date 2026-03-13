# Active Auditor - Complete Integration Guide

## Overview
Active Auditor is a comprehensive WordPress health monitoring plugin with a Chrome extension. It provides:
- 📊 Real-time performance scoring (Google Lighthouse)
- 🔒 Security vulnerability detection (Wordfence Intelligence)
- 🎯 On-page SEO analysis
- 🔍 Google services detection (GA4, GTM, Ads)
- 📱 Chrome extension for quick checks
- 🔐 Token-based authentication

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│         WordPress Site (Active Auditor Plugin)          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ Health Data  │  │ Updates      │  │ Security     │ │
│  │ Generator    │  │ Tracker      │  │ Audit        │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                         │
│  ┌───────────────────────────────────────────────────┐ │
│  │         External API Integrations                 │ │
│  ├───────────────────────────────────────────────────┤ │
│  │ • Wordfence Intelligence API (Vulnerabilities)   │ │
│  │ • Google PageSpeed Insights (Lighthouse Scores)  │ │
│  │ • HTML DOM Parser (SEO Analysis)                 │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
│  ┌───────────────────────────────────────────────────┐ │
│  │   REST API Endpoints (Token Authenticated)       │ │
│  ├───────────────────────────────────────────────────┤ │
│  │ GET /health, /wordfence, /lighthouse, /seo, ... │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
└─────────────────────────────────────────────────────────┘
                          ▲
                          │ HTTP API Calls
                          │
┌─────────────────────────────────────────────────────────┐
│    Chrome Extension (site-health-guard)                 │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────────┐      ┌─────────────────────┐   │
│  │  Guest Mode      │      │ Authenticated Mode  │   │
│  ├──────────────────┤      ├─────────────────────┤   │
│  │ • Lighthouse     │      │ • WordPress Health  │   │
│  │ • SEO Analysis   │      │ • Security Issues   │   │
│  │ • Google Svc     │      │ • Plugin Updates    │   │
│  │ • Links          │      │ • Vulnerabilities   │   │
│  └──────────────────┘      └─────────────────────┘   │
│                                                         │
│  Settings: Store Domain + Token in Chrome Storage      │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

## Setup Instructions

### Step 1: WordPress Plugin Installation

1. **Activate Plugin**
   - Place `active-auditor` folder in `/wp-content/plugins/`
   - Go to WordPress Admin → Plugins
   - Activate "Active Auditor - Health Monitor"

2. **Generate API Token**
   - Go to **Active Auditor > Settings**
   - Click "Generate New Token"
   - Copy and save the token securely

### Step 2: Configure External API Keys

#### Wordfence Intelligence API (Security)
1. Visit https://www.wordfence.com/intelligence/api/
2. Sign up for free account
3. Generate API key
4. In **Active Auditor > Settings**, paste key in "Wordfence Intelligence API Key"
5. Click "Save Settings"

**Benefits:**
- Real-time vulnerability detection
- Automatic check for outdated plugins/themes
- Security severity ratings
- CVE references

#### Google PageSpeed Insights API (Performance)
1. Visit https://console.cloud.google.com/
2. Create new project
3. Enable "PageSpeed Insights API"
4. Create API key (unrestricted)
5. In **Active Auditor > Settings**, paste key in "Google Lighthouse API Key"
6. Click "Save Settings"

**Benefits:**
- Lighthouse performance scores (0-100)
- Accessibility audit results
- Best practices checks
- SEO analysis scores

### Step 3: Chrome Extension Installation

1. **Build Extension** (if not pre-built)
   ```bash
   cd site-health-guard
   npm install
   npm run build
   ```

2. **Load in Chrome**
   - Open Chrome: `chrome://extensions/`
   - Enable "Developer mode" (top right)
   - Click "Load unpacked"
   - Select `site-health-guard/dist/` folder

3. **Configure Sites**
   - Click extension icon
   - Go to ⚙️ Settings tab
   - Click "Add Site"
   - Enter:
     - **Domain**: your-wordpress-site.com
     - **Token**: Paste the token from Step 1
   - Click "Save"

### Step 4: Verify Integration

#### Option A: WordPress Admin
1. Go to **Active Auditor** dashboard
2. Check "System Health" section
3. Should see PHP, WordPress, Database info

#### Option B: Chrome Extension
1. Click extension icon
2. **Guest Tab** - Shows automatic Lighthouse + SEO checks
3. **Auth Tab** - After 5 seconds, shows WordPress Health + Security

#### Option C: API Direct Call
```bash
curl "http://your-site.com/index.php?rest_route=/active-auditor/v1/status"
```

---

## Dashboard Overview

### WordPress Admin Dashboard

**Main Page (Active Auditor)**
- 📊 Overall system health status
- 🟢 PHP version and status
- 🟢 WordPress version and update status
- 🟠 Available plugin/theme updates
- 🔴 Security vulnerabilities

**Health Report Page**
- Detailed JSON report
- System metrics
- Database performance
- File permissions

**Settings Page**
- API token management
- Wordfence API key configuration
- Lighthouse API key configuration
- Feature toggles
- Cache settings

### Chrome Extension Popup

**Guest Mode** (Public • No Auth)
```
┌────────────────────────────────┐
│ Lighthouse Scores              │
├────────────────────────────────┤
│ Performance:    84  🟢          │
│ Accessibility:  83  🟢          │
│ Best Practices: 64  🟡          │
│ SEO:            56  🟡          │
├────────────────────────────────┤
│ On-Page SEO                    │
├────────────────────────────────┤
│ ✅ H1 tag present              │
│ ⚠️  1 image missing alt text   │
│ ❌ No meta description         │
├────────────────────────────────┤
│ Google Services                │
├────────────────────────────────┤
│ ✅ GA4 connected (G-XXXXX)    │
│ ✅ GTM detected (GTM-XXX)     │
│ ❌ Google Ads not found        │
└────────────────────────────────┘
```

**Authenticated Mode** (Token Required)
```
┌────────────────────────────────┐
│ ⚪ Connected · localhost       │
├────────────────────────────────┤
│ WordPress Health               │
├────────────────────────────────┤
│ ✅ PHP 8.2 (latest)           │
│ ✅ WP 6.5.2 (up to date)      │
│ ⚠️  2 plugin updates available │
│ ✅ Theme up to date            │
├────────────────────────────────┤
│ Security (Wordfence)           │
├────────────────────────────────┤
│ ✅ No critical vulnerabilities │
│ ⚠️  1 medium severity issue    │
│ ✅ Firewall active             │
├────────────────────────────────┤
│ Plugin Status                  │
├────────────────────────────────┤
│ • Yoast SEO v19.5              │
│ ⚠️  Contact Form 7 v5.4.0       │
│ • WooCommerce v7.8             │
│ • Elementor v3.15              │
└────────────────────────────────┘
```

---

## REST API Endpoints Quick Reference

| Endpoint | Auth | Purpose |
|----------|------|---------|
| `/status` | ❌ | Check plugin active |
| `/guest/lighthouse` | ❌ | Performance scores |
| `/guest/seo` | ❌ | On-page SEO |
| `/guest/google-services` | ❌ | GA4/GTM/Ads detection |
| `/health` | ✅ | WordPress health |
| `/security-audit` | ✅ | Basic security checks |
| `/wordfence` | ✅ | Vulnerability data |
| `/lighthouse` | ✅ | Full Lighthouse audit |
| `/seo-analysis` | ✅ | Detailed SEO report |
| `/updates` | ✅ | Available updates |
| `/full-report` | ✅ | Combined report |

Full Reference: See [REST_API_REFERENCE.md](REST_API_REFERENCE.md)

---

## Troubleshooting

### Extension Not Showing Data

**Issue:** Extension shows "No data leaves your browser" but no actual data

**Solution:**
1. Check if site domain is correct (no protocol)
2. Verify token is correctly copied (no spaces)
3. Ensure WordPress site is accessible
4. Check browser console for errors (F12)
5. Clear extension cache: right-click extension → Remove → Re-add

### Wordfence API Not Working

**Issue:** Extension shows "API not configured" after adding key

**Solution:**
1. Go to WordPress **Active Auditor > Settings**
2. Paste Wordfence API key (get from https://www.wordfence.com/intelligence/api/)
3. Click "Save Settings"
4. Wait 24 hours for first results (daily API limit)
5. Or manually trigger check by visiting extension guest tab

### Lighthouse Scores Showing 0

**Issue:** All Lighthouse scores are 0

**Solution:**
1. Verify Google Lighthouse API key is configured
2. API key must have "PageSpeed Insights API" enabled
3. Check Google Cloud Console quota
4. Lighthouse audits can take 30+ seconds - be patient

### Token Generation Failed

**Issue:** Can't generate new token in Settings

**Solution:**
1. Check WordPress WP-Cron is enabled
2. Verify database is writable
3. Try WP-CLI: `wp option update aa_api_token "$(openssl rand -hex 16)"`

---

## Performance Considerations

### Recommended Setup

- **PHP**: ≥ 7.4 (tested on 8.2)
- **WordPress**: ≥ 5.6
- **Database**: MySQL 5.7+ or PostgreSQL 12+
- **Memory**: Minimum 128MB
- **Caching**: Enable transient caching for APIs

### Timings

| Operation | Duration |
|-----------|----------|
| Health data collection | ~500ms |
| SEO scan | ~2s |
| Lighthouse audit | ~15-30s |
| Wordfence check | ~3-5s |
| Full health report | ~3-5s |

### Caching Strategy

- guest/* endpoints: 24-hour cache
- health: 1-hour cache
- wordfence: 24-hour cache
- Clear cache in Settings or via code

---

## Security Best Practices

✅ **DO:**
- Regenerate token if compromised
- Use HTTPS for all API calls
- Store keys in WordPress options (not code)
- Enable WordPress security headers
- Keep Wordfence plugin active for real-time protection

❌ **DON'T:**
- Share API token in public
- Store credentials in version control
- Disable HTTPS
- Expose API keys in client-side code
- Use same token across multiple sites

---

## Roadmap & Future Features

- [ ] Real-time monitoring dashboard
- [ ] Scheduled email reports
- [ ] Slack/Teams notifications
- [ ] Multi-site WooCommerce support
- [ ] Custom security rules
- [ ] PDF export reports
- [ ] Advanced performance profiling

---

## Support & Resources

- **Documentation**: `/wp-content/plugins/active-auditor/README.md`
- **API Reference**: [REST_API_REFERENCE.md](REST_API_REFERENCE.md)
- **Setup Guide**: [WORDFENCE_API_SETUP.md](WORDFENCE_API_SETUP.md)
- **Issues**: Check WordPress debug log
- **GitHub**: (if applicable)

---

## Version History

### v1.0.0 (Feb 24, 2026)
- ✅ Initial release
- ✅ Wordfence Intelligence API integration
- ✅ Google Lighthouse API integration
- ✅ On-page SEO analysis
- ✅ Google Services detection
- ✅ Chrome extension with dual modes
- ✅ REST API with token authentication
- ✅ Admin settings and dashboard

---

**Last Updated:** February 24, 2026  
**Maintained By:** Your Organization  
**License:** GPL v2 or later
