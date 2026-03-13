# Wordfence Intelligence API Setup Guide

## Overview
The Active Auditor plugin now integrates with **Wordfence Intelligence API** to provide real-time security vulnerability detection for WordPress plugins, themes, and core updates.

## What is Wordfence Intelligence API?

Wordfence Intelligence is a real-time threat intelligence feed that provides:
- Real-time vulnerability data for plugins and themes
- Exploitation attempts and malware signatures
- Malware signatures and threat information
- Free tier with rate-limited access
- Premium tier with unlimited access

## Getting a Free API Key

### Step 1: Visit Wordfence Intelligence
1. Go to [https://www.wordfence.com/intelligence/api/](https://www.wordfence.com/intelligence/api/)
2. This is the Wordfence Intelligence API documentation page

### Step 2: Sign Up or Login
- If you don't have a Wordfence account, create one at [https://www.wordfence.com](https://www.wordfence.com)
- You can use the free plugin first, then get an API key for programmatic access

### Step 3: Generate API Key
1. Go to your Wordfence account dashboard
2. Navigate to **API Settings** or **Integrations**
3. Click **Generate API Key**
4. Copy the generated API key (it will look like a long string of characters)

### Step 4: Configure in Active Auditor
1. Log into your WordPress admin panel
2. Navigate to **Active Auditor > Settings**
3. Find the **Wordfence Intelligence API Key** field
4. Paste your API key into the field
5. Click **Save Settings**

## API Response Structure

When configured, the Wordfence endpoint returns:

```json
{
  "success": true,
  "data": {
    "overall_status": "green|amber|red",
    "critical_vulnerabilities": 0,
    "medium_vulnerabilities": 0,
    "total_vulnerabilities": 0,
    "vulnerabilities": [
      {
        "type": "plugin|theme|wordpress",
        "name": "Plugin/Theme Name",
        "slug": "plugin-slug",
        "installed_version": "1.0.0",
        "fixed_version": "1.0.1",
        "severity": "high|medium|low",
        "description": "Vulnerability description",
        "status": "red|amber|green",
        "cve": "CVE-2025-XXXXX",
        "source": "wordfence_api|local_database"
      }
    ],
    "api_configured": true
  }
}
```

## Vulnerability Severity Mapping

| Severity | Status | Color |
|----------|--------|-------|
| Critical | 🔴 Red | #EF4444 |
| High | 🔴 Red | #EF4444 |
| Medium | 🟡 Amber | #FBBF24 |
| Low | 🟢 Green | #10B981 |

## Free Tier Limitations

- **Rate Limit**: 100 requests per day
- **Response Time**: ~2-5 seconds per request
- **Caching**: Results are cached for 24 hours to minimize API calls
- **Updates**: Vulnerability data updated daily

## Premium Features

For higher limits and more frequent updates, Wordfence offers:
- Unlimited API requests
- Real-time vulnerability updates
- Priority support
- Custom threat signatures

Visit [https://www.wordfence.com/intelligence/](https://www.wordfence.com/intelligence/) for pricing.

## Fallback Behavior

If NO API key is configured:
- The plugin uses a local vulnerability database
- Only manually maintained vulnerability data is available
- Recommend configuring API key for real-time data

If API key is **invalid or expired**:
- System falls back to local database
- Error is logged in WordPress debug log
- Extension still displays security status as "not configured"

## Testing the Integration

### Via WordPress Admin
1. Go to **Active Auditor** dashboard
2. Check the **Security (Wordfence)** section
3. Look for detected vulnerabilities with blue "wordfence_api" badge

### Via REST API
```bash
curl -X GET "http://localhost/index.php?rest_route=/active-auditor/v1/wordfence&token=YOUR_TOKEN"
```

### Via Chrome Extension
1. Install the Active Auditor Chrome extension
2. Click the extension icon
3. Switch to **Authenticated** tab
4. View the **Security (Wordfence)** section

## Troubleshooting

### API key not working
- Verify the key is copied completely (no extra spaces)
- Check that your Wordfence account is active
- Ensure the free tier rate limit hasn't been exceeded (100 requests/day)

### Seeing "No API Key Configured"
- Go to **Active Auditor > Settings**
- Verify the **Wordfence Intelligence API Key** field is filled
- Click **Save Settings**
- Clear the cache: Delete transients in database

### Getting old vulnerability data
- Results are cached for 24 hours
- To refresh immediately:
  - Clear the transient: `delete_transient('aa_wordfence_vulns')`
  - Or access the API endpoint with `clear_cache=1` parameter

## Security Notes

- ✅ API keys are stored in WordPress options table
- ✅ Only accessible from WordPress admin
- ✅ HTTPS required for API communication
- ✅ No vulnerability data is sent to external services except Wordfence
- ✅ All caching is done server-side, never exposed to the browser

## Resources

- Wordfence Intelligence API Docs: https://www.wordfence.com/api/
- Wordfence Information: https://www.wordfence.com/
- WordPress Plugin Security: https://wordpress.org/plugins/wordfence/

## Support

For issues with:
- **Active Auditor Plugin**: Check documentation in the plugin README
- **Wordfence API**: Contact Wordfence support at https://www.wordfence.com/support/

---

Last Updated: February 24, 2026
Plugin Version: 1.0.0
