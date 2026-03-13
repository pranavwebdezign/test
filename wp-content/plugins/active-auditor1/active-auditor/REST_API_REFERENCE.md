# Active Auditor REST API Reference

## Base URL
```
http://your-site.com/index.php?rest_route=/active-auditor/v1/ENDPOINT
```

## Authentication
Most endpoints require a token parameter. Get your token from **Active Auditor > Settings**.

### Token Usage
```
?rest_route=/active-auditor/v1/ENDPOINT&token=YOUR_TOKEN
```

---

## PUBLIC ENDPOINTS (No Token Required)

### 1. Status Check
**Endpoint:** `GET /status`

**Description:** Check if the Active Auditor plugin is active

**Parameters:** None

**Example Request:**
```bash
curl "http://localhost/index.php?rest_route=/active-auditor/v1/status"
```

**Response:**
```json
{
  "success": true,
  "status": "active",
  "version": "1.0.0",
  "has_token": true,
  "site_url": "http://localhost",
  "domain": "localhost"
}
```

---

### 2. Guest Lighthouse Scores
**Endpoint:** `GET /guest/lighthouse`

**Description:** Get Lighthouse performance scores without authentication

**Parameters:**
- `url` (optional): Website URL to scan (defaults to home_url())

**Example Request:**
```bash
curl "http://localhost/index.php?rest_route=/active-auditor/v1/guest/lighthouse"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "overall_score": 82,
    "overall_status": "green",
    "average_score": 82,
    "individual_scores": {
      "performance": 84,
      "accessibility": 83,
      "best_practices": 64,
      "seo": 56
    },
    "metrics": {
      "first_contentful_paint": "1.2 s",
      "largest_contentful_paint": "2.4 s",
      "cumulative_layout_shift": "0.05"
    },
    "issues": {
      "accessibility": 2,
      "performance": 3,
      "seo": 4
    }
  },
  "timestamp": "2026-02-24 14:30:00",
  "public": true
}
```

---

### 3. Guest SEO Analysis
**Endpoint:** `GET /guest/seo`

**Description:** Get on-page SEO analysis without authentication

**Parameters:**
- `url` (optional): Website URL to analyze (defaults to home_url())

**Example Request:**
```bash
curl "http://localhost/index.php?rest_route=/active-auditor/v1/guest/seo"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "http://localhost",
    "overall_score": 75,
    "overall_status": "amber",
    "page_title": {
      "present": true,
      "value": "My Website",
      "length": 10,
      "optimal_length": false,
      "issues": ["Title too short (< 30 characters)"]
    },
    "meta_description": {
      "present": false,
      "value": "",
      "length": 0,
      "optimal_length": false,
      "issues": ["Missing meta description"]
    },
    "h1_tags": {
      "count": 1,
      "present": true,
      "tags": ["Welcome"],
      "issues": [],
      "status": "green"
    },
    "images": {
      "total": 3,
      "with_alt_text": 2,
      "without_alt_text": 1,
      "alt_text_percentage": 67,
      "issues": ["1 image missing alt text"],
      "status": "amber"
    }
  },
  "timestamp": "2026-02-24 14:30:00",
  "public": true
}
```

---

### 4. Guest Google Services
**Endpoint:** `GET /guest/google-services`

**Description:** Detect Google services (GA4, GTM, Ads, etc.)

**Parameters:**
- `url` (optional): Website URL to scan

**Example Request:**
```bash
curl "http://localhost/index.php?rest_route=/active-auditor/v1/guest/google-services"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "http://localhost",
    "scan_time": "2026-02-24 14:30:00",
    "services": {
      "ga4": {
        "detected": true,
        "ids": ["G-XXXXXXXXXX"],
        "status": "green"
      },
      "gtm": {
        "detected": true,
        "ids": ["GTM-XXXXXX"],
        "status": "green"
      },
      "google_ads": {
        "detected": false,
        "conversion_labels": [],
        "status": "red"
      },
      "recaptcha": {
        "detected": true,
        "versions": ["v3"],
        "status": "green"
      }
    },
    "detected_services": {
      "ga4": { ... },
      "gtm": { ... },
      "recaptcha": { ... }
    },
    "total_services": 3
  },
  "timestamp": "2026-02-24 14:30:00",
  "public": true
}
```

---

## AUTHENTICATED ENDPOINTS (Token Required)

### 5. WordPress Health Data
**Endpoint:** `GET /health?token=YOUR_TOKEN`

**Description:** Get comprehensive WordPress health status

**Parameters:**
- `token` (required): Your API token

**Example Request:**
```bash
curl "http://localhost/index.php?rest_route=/active-auditor/v1/health&token=YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "domain": "localhost",
    "php": {
      "version": "8.2.0",
      "status": "green"
    },
    "wordpress": {
      "version": { "current": "6.5.2", "status": "green" },
      "updates": {
        "core": { "available": false },
        "plugins": { "count": 2, "status": "amber" },
        "themes": { "available": false, "status": "green" }
      }
    },
    "database": {
      "type": "MySQL 8.0.36",
      "status": "green"
    },
    "performance": {
      "memory_usage": "45%",
      "status": "green"
    }
  },
  "timestamp": "2026-02-24 14:30:00"
}
```

---

### 6. Security Audit (Basic)
**Endpoint:** `GET /security-audit?token=YOUR_TOKEN`

**Description:** Get basic security audit findings

**Parameters:**
- `token` (required): Your API token

**Response:**
```json
{
  "success": true,
  "data": {
    "vulnerabilities": [
      {
        "label": "File editing disabled",
        "status": "green"
      },
      {
        "label": "Debug mode disabled",
        "status": "green"
      }
    ]
  },
  "timestamp": "2026-02-24 14:30:00"
}
```

---

### 7. Wordfence Vulnerabilities
**Endpoint:** `GET /wordfence?token=YOUR_TOKEN`

**Description:** Get vulnerability data from Wordfence Intelligence API

**Parameters:**
- `token` (required): Your API token
- `url` (optional): Site URL to check

**Example Request:**
```bash
curl "http://localhost/index.php?rest_route=/active-auditor/v1/wordfence&token=YOUR_TOKEN"
```

**Response (No API Key):**
```json
{
  "success": true,
  "data": {
    "overall_status": "green",
    "critical_vulnerabilities": 0,
    "medium_vulnerabilities": 0,
    "total_vulnerabilities": 0,
    "vulnerabilities": [],
    "api_configured": false
  },
  "timestamp": "2026-02-24 14:30:00",
  "authenticated": true
}
```

**Response (With API Key & Vulnerabilities Found):**
```json
{
  "success": true,
  "data": {
    "overall_status": "red",
    "critical_vulnerabilities": 1,
    "medium_vulnerabilities": 2,
    "total_vulnerabilities": 3,
    "vulnerabilities": [
      {
        "type": "plugin",
        "name": "Contact Form 7",
        "slug": "contact-form-7",
        "installed_version": "5.4.0",
        "fixed_version": "5.5.0",
        "severity": "high",
        "description": "SQL Injection vulnerability",
        "status": "red",
        "cve": "CVE-2025-12345",
        "source": "wordfence_api"
      }
    ],
    "api_configured": true
  },
  "timestamp": "2026-02-24 14:30:00",
  "authenticated": true
}
```

---

### 8. Lighthouse Full Audit
**Endpoint:** `GET /lighthouse?token=YOUR_TOKEN`

**Description:** Get full Lighthouse audit with all metrics and issues

**Parameters:**
- `token` (required): Your API token
- `url` (optional): Site URL to audit

**Response:**
```json
{
  "success": true,
  "data": {
    "success": true,
    "url": "http://localhost",
    "fetch_time": "2026-02-24 14:30:00",
    "scores": {
      "performance": 84,
      "accessibility": 83,
      "best_practices": 64,
      "seo": 56
    },
    "metrics": {
      "first_contentful_paint": "1.2 s",
      "largest_contentful_paint": "2.4 s",
      "cumulative_layout_shift": "0.05",
      "speed_index": "1.8 s"
    },
    "accessibility_issues": [
      {
        "id": "color-contrast",
        "title": "Background and foreground colors don't have enough contrast",
        "score": 0.5,
        "display_value": "39% of tap targets"
      }
    ],
    "performance_issues": [
      {
        "id": "unused-css",
        "title": "Remove unused CSS",
        "score": 0.8,
        "display_value": "3 potential savings of 15 ms"
      }
    ]
  },
  "timestamp": "2026-02-24 14:30:00",
  "authenticated": true
}
```

---

### 9. SEO Full Analysis
**Endpoint:** `GET /seo-analysis?token=YOUR_TOKEN`

**Description:** Get detailed on-page SEO analysis

**Parameters:**
- `token` (required): Your API token
- `url` (optional): Site URL to analyze

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "http://localhost",
    "scan_time": "2026-02-24 14:30:00",
    "overall_score": 75,
    "page_title": { ... },
    "meta_description": { ... },
    "h1_tags": { ... },
    "headings_structure": {
      "structure": [
        { "level": 1, "text": "Welcome to My Site" },
        { "level": 2, "text": "About Us" },
        { "level": 2, "text": "Services" }
      ],
      "hierarchy_valid": true,
      "issues": [],
      "status": "green"
    },
    "images": { ... },
    "links": {
      "total": 45,
      "internal": 40,
      "external": 5,
      "without_text": 0,
      "status": "green"
    },
    "structured_data": {
      "has_structured_data": true,
      "types_found": ["JSON-LD"],
      "status": "green"
    },
    "canonical_tag": {
      "present": true,
      "url": "http://localhost/",
      "status": "green"
    },
    "og_tags": {
      "found_tags": {
        "og:title": "My Site",
        "og:description": "Welcome",
        "og:image": "http://localhost/image.jpg",
        "og:type": "website",
        "og:url": "http://localhost/"
      },
      "complete": true,
      "status": "green"
    },
    "readability": {
      "word_count": 1250,
      "paragraph_count": 8,
      "estimated_reading_time_minutes": 5,
      "average_words_per_paragraph": 156
    }
  },
  "timestamp": "2026-02-24 14:30:00",
  "authenticated": true
}
```

---

### 10. Plugin Updates
**Endpoint:** `GET /updates?token=YOUR_TOKEN`

**Description:** Get information about available updates

**Response:**
```json
{
  "success": true,
  "data": {
    "wordpress": {
      "current": "6.5.2",
      "latest": "6.9.1",
      "needs_update": true
    },
    "plugins": {
      "updates_available": 2,
      "plugins": [
        {
          "name": "Contact Form 7",
          "slug": "contact-form-7",
          "version": "5.4.0",
          "new_version": "5.5.0",
          "update_available": true
        }
      ]
    },
    "themes": {
      "updates_available": 0
    }
  },
  "timestamp": "2026-02-24 14:30:00"
}
```

---

### 11. Full Health Report
**Endpoint:** `GET /full-report?token=YOUR_TOKEN`

**Description:** Get complete health, security, and update report

**Response:** Combines all health, security, and updates data in one response

---

## Error Responses

### Missing Token
```json
{
  "code": "missing_token",
  "message": "API token is required",
  "data": {
    "status": 401
  }
}
```

### Invalid Token
```json
{
  "code": "invalid_token",
  "message": "Invalid API token",
  "data": {
    "status": 403
  }
}
```

---

## Caching

| Endpoint | Cache Duration |
|----------|-----------------|
| /guest/lighthouse | 24 hours |
| /guest/seo | 24 hours |
| /guest/google-services | 24 hours |
| /wordfence | 24 hours |
| /lighthouse | 24 hours |
| /seo-analysis | 24 hours |
| /health | 1 hour |
| /security-audit | 1 hour |
| /updates | 12 hours |

To clear cache:
```php
delete_transient('aa_wordfence_vulns');
delete_transient('aa_seo_analysis_' . md5($url));
delete_transient('aa_lighthouse_scores_' . md5($url));
delete_transient('aa_google_services_' . md5($url));
```

---

## Rate Limiting

- Public endpoints: 1000 requests per hour
- Authenticated endpoints: 100 requests per hour
- Wordfence API: 100 requests per day (free tier limit)

---

## Best Practices

1. **Cache Results**: Don't call endpoints excessively. Use the cache headers.
2. **Error Handling**: Always check `success` field before accessing `data`.
3. **Token Security**: Store tokens securely, never expose in client-side code.
4. **API Keys**: Store Wordfence and Lighthouse keys as Options in WordPress.
5. **Batch Operations**: Use `/full-report` when you need all data at once.

---

## SDK/Integration Examples

### JavaScript (Fetch API)
```javascript
const token = 'YOUR_TOKEN';
const response = await fetch(
  `http://localhost/index.php?rest_route=/active-auditor/v1/health&token=${token}`
);
const data = await response.json();
console.log(data.data);
```

### PHP (wp_remote_get)
```php
$token = get_option('aa_api_token');
$url = add_query_arg(array('token' => $token), home_url('index.php?rest_route=/active-auditor/v1/health'));
$response = wp_remote_get($url);
$data = json_decode(wp_remote_retrieve_body($response), true);
```

---

Last Updated: February 24, 2026
Plugin Version: 1.0.0
