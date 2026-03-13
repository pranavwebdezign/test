# ChangeFluent Quote Manager

**WordPress Plugin — v1.0.0**  
Powers **Fixdly.com** | Re-brandable for **LeafAndLush.com** via brand settings

---

## Overview

A full-featured quote management system for UK trades businesses. Built as a self-contained WordPress plugin — no external CRMs, no Google Sheets, no Zapier.

### Core User Journeys

| User | Journey |
|------|---------|
| **Homeowner** | Submits a job query (guest, no login) → receives quotes by email → views/accepts/declines via secure portal link → requests amendments |
| **Tradesperson** | Receives matched query notification → builds and sends quote via Toolbox dashboard → manages amendments → receives deposit via Stripe |
| **Admin** | Manages users, tiers, settings, geo-config, brand, email from WP Admin |

---

## Features

### Phase 1 — Custom Post Types & Data Model
- `cfqm_quote` · `cfqm_quote_area` · `cfqm_trade_profile` · `cfqm_hw_query` · `cfqm_customer`
- Flat `cfqm_category` taxonomy with 20 seeded trade categories (non-hierarchical)
- Two custom DB tables: `{prefix}cfqm_events` (audit log) · `{prefix}cfqm_tokens` (secure tokens)
- Full meta schema (23 quote fields, 9 trade profile fields, 12 query fields)

### Phase 2 — Subscription Tiers
- **Starter**: 10 quotes/month · 5 amendment iterations per quote
- **Pro**: 40 quotes/month · 10 amendment iterations per quote
- Integration with: Paid Memberships Pro · MemberPress · WooCommerce Memberships
- Monthly quota enforcement via `post_date` query (no extra table)

### Phase 3 — Geo-Matching Engine
- Postcodes.io API geocoding (UK, cached 1 week in transients)
- Haversine distance calculation (miles)
- Activity score: `response_rate × 0.6 + recency × 0.4` (updated hourly via cron)
- Configurable pool size (default: top 10 notified)
- Configurable response cap (default: first 5 accepted)
- Late responder "Quotes Full" notification
- Auto-reopen: when ALL quotes for a query are declined, remaining pool is re-notified

### Phase 4 — Quote Builder
- Per-area pricing: each area has name, scope, price, type (Included / Optional / Exclusion), validity date
- VAT toggle + configurable rate (default 20%)
- Deposit: fixed amount OR percentage
- Tradesperson-defined deposit (not platform-controlled)
- Iteration limit inherited from subscription plan
- All AJAX — full SPA-style Toolbox experience
- 9 quote statuses: `draft` → `sent` → `viewed` → `approved` / `partial` / `declined` / `deposit_paid` / `expired` / `pending_amendment`

### Phase 5 — Customer Portal
- Shortcode: `[cfqm_quote_portal]`
- Token-based access — no account or password needed
- Always-latest link (portal token never expires)
- Area selection: Included areas locked; Optional areas tick-boxable
- Live total recalculation client-side
- E-signature: typed name + date + confirmation checkbox
- Accept (full) / Partial accept (subset of optional areas) / Decline
- Amendment request: tick areas + free-text message
- PDF download button
- Quote event timeline
- Auto-marks quote as "Viewed" on first open

### Phase 6 — Amendment Engine
- Homeowner submits tick-box amendment request
- Iteration counter increments on each request
- Tradesperson responds: **Reissue** (creates new version) / **Reject** / **Approve-as-is**
- Iteration limit enforcement with clear error messages
- Admin can reset iteration counter per quote
- Full history stored as JSON in post meta

### Phase 7 — Versioning & Audit Trail
- Version numbers: `1.0`, `1.1`, `1.2` etc.
- Full snapshot saved on every "sent" transition
- 23 named event types logged to `cfqm_events` table
- Per-event: timestamp · quote_id · query_id · version · actor_type · actor_id · event_data
- Timeline rendered in Customer Portal and Admin meta box

### Phase 8 — PDF Generation
- Native PHP — no Google Docs, no Zapier
- Auto-detects library priority: **mPDF** → **FPDF** → HTML fallback
- Saved to `wp-content/uploads/cfqm-pdfs/{year}/{month}/quote-{id}-v{version}.pdf`
- Secure download URL (nonce-protected AJAX endpoint)
- Trade branding: logo, company name, address, contact details

### Phase 9 — Stripe Integration
- Payment Links API — no Composer SDK, uses WP HTTP API directly
- Auto-creates Payment Link on quote acceptance (if deposit configured)
- REST webhook: `POST /wp-json/cfqm/v1/stripe-webhook`
- HMAC signature verification
- Handles: `checkout.session.completed` · `payment_intent.succeeded`
- Idempotency: tracks `stripe_intent_id` to prevent duplicate processing
- Updates quote status to `deposit_paid`

### Phase 10 — Homeowner Dashboard
- Shortcodes: `[cfqm_homeowner_dashboard]` · `[cfqm_magic_link_form]`
- Magic-link auth: 15-min email link → 24-hr session cookie (no password, no WP account)
- Quotes grouped by `project_label`
- Status filter tabs: All / Accepted / Pending / Closed
- Search by trade name or job description
- Multi-quote comparison: 2-3 quotes side-by-side, highlights lowest price + fastest response
- Project label editing (inline AJAX)
- Direct portal links per quote

### Phase 11 — Trades Dashboard
- Shortcode: `[cfqm_trades_dashboard]`
- Tab 1: **Pipeline** — all quotes with status badges, iteration count, last activity
- Tab 2: **Amendments** — quotes awaiting response
- Tab 3: **Awaiting Deposit** — accepted quotes without deposit paid
- Stat cards: quotes this month · win rate · avg time to approval
- Plan tier summary card

### Transactional Emails (12 types)
| Template | Recipient |
|----------|-----------|
| `query_received` | Tradesperson |
| `query_full` | Tradesperson (late arrival) |
| `query_reopened` | Tradesperson |
| `quote_sent` | Homeowner |
| `amendment_requested` | Tradesperson |
| `amendment_rejected` | Homeowner |
| `quote_accepted` | Tradesperson |
| `quote_declined` | Tradesperson |
| `deposit_paid` | Tradesperson |
| `magic_link` | Homeowner |
| `reminder_unviewed` | Homeowner (48h cron) |
| `reminder_expiry` | Homeowner (24h before expiry) |

### Scheduled Cron (hourly)
1. 48-hour unviewed quote reminder
2. 24-hour expiry warning
3. Quote auto-expiration (90-day fallback)
4. Activity score recalculation for all trade profiles
5. Expired token purge

---

## Installation

1. Upload `changefluent-quote-manager/` to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Navigate to **Quote Manager → Settings** and configure:
   - Brand name + industry label
   - Stripe API keys (test + live)
   - Stripe webhook secret
   - SMTP from name + from address
   - Response cap and pool size
   - Page IDs for each shortcode page

### Page Setup

Create one WP page per shortcode:

| Page | Shortcode | URL suggestion |
|------|-----------|----------------|
| Quote Portal | `[cfqm_quote_portal]` | `/quote-portal/` |
| Homeowner Dashboard | `[cfqm_homeowner_dashboard]` | `/my-quotes/` |
| Trades Toolbox | `[cfqm_trades_dashboard]` | `/toolbox/` |
| Get Quotes | `[cfqm_query_form]` | `/get-quotes/` |
| Login (magic link) | `[cfqm_magic_link_form]` | `/login/` |

Then set the page IDs in **Quote Manager → Settings → Pages**.

### Stripe Webhook

In the Stripe Dashboard, add a webhook endpoint:

```
https://yoursite.com/wp-json/cfqm/v1/stripe-webhook
```

Events to listen for:
- `checkout.session.completed`
- `payment_intent.succeeded`

Copy the **Signing Secret** into **Quote Manager → Settings → Stripe → Webhook Secret**.

### Optional: PDF Library

For full PDF output, install via Composer:

```bash
# Option A — mPDF (recommended, full CSS support)
composer require mpdf/mpdf

# Option B — FPDF (lightweight, basic layout)
composer require setasign/fpdf
```

Without a PDF library, the plugin falls back to a print-CSS HTML file.

---

## Multi-Brand Configuration

The plugin supports running as **Fixdly** (general trades) or **LeafAndLush** (landscaping) via Settings:

| Setting | Fixdly | LeafAndLush |
|---------|--------|-------------|
| `brand_name` | Fixdly | LeafAndLush |
| `brand_industry` | trades | landscaping |
| `brand_tradesperson` | Tradesperson | Landscaper |
| `brand_homeowner` | Homeowner | Homeowner |

---

## Architecture

```
changefluent-quote-manager/
├── changefluent-quote-manager.php   Main plugin file + autoloader
├── uninstall.php                    Cleanup on plugin deletion
├── includes/
│   ├── trait-singleton.php          Reusable singleton trait
│   ├── class-plugin.php             Core boot orchestrator
│   ├── class-database.php           Custom tables + CRUD helpers
│   ├── class-settings.php           Options wrapper + brand config
│   ├── class-post-types.php         CPT + taxonomy registration
│   ├── class-subscription-tiers.php Plan detection + quota enforcement
│   ├── class-token-manager.php      Portal + magic-link tokens
│   ├── class-geo-matching.php       Geocoding + haversine + activity score
│   ├── class-quote-builder.php      Quote CRUD + areas + totals + status
│   ├── class-amendment-engine.php   Amendment workflow
│   ├── class-versioning.php         Version bumping + snapshots
│   ├── class-audit-trail.php        Event log wrapper (23 event types)
│   ├── class-pdf-generator.php      mPDF/FPDF/HTML PDF output
│   ├── class-stripe.php             Payment Links + webhook handler
│   ├── class-email-notifications.php 12 transactional emails
│   ├── class-homeowner-query.php    Guest query submission
│   ├── class-customer-portal.php    Token portal shortcode + AJAX
│   ├── class-homeowner-dashboard.php Magic-link dashboard + comparison
│   ├── class-trades-dashboard.php   Tradesperson Toolbox
│   ├── class-cron.php               Scheduled jobs
│   └── class-admin.php              WP Admin UI + meta boxes
├── templates/
│   ├── portal/
│   │   ├── quote-portal.php         Full customer portal UI
│   │   └── invalid-token.php        Expired/invalid link page
│   ├── dashboard/
│   │   ├── homeowner-dashboard.php  HO dashboard with compare
│   │   ├── magic-link-form.php      Email input for magic link
│   │   ├── query-form.php           Guest job query form
│   │   └── trades-dashboard.php     Tradesperson Toolbox UI
│   ├── pdf/
│   │   └── quote-pdf.php            PDF layout template
│   └── emails/
│       ├── email-wrapper.php        HTML email shell
│       ├── query_received.php       + 11 other email templates
│       └── ...
├── admin/views/
│   ├── admin-dashboard.php          Admin overview page
│   ├── settings.php                 Settings form
│   ├── meta-box-trade-profile.php   Trade profile meta box
│   ├── meta-box-hw-query.php        Query meta box
│   └── meta-box-quote.php           Quote detail + timeline meta box
└── assets/
    ├── css/
    │   ├── frontend.css             Portal + dashboard styles
    │   └── admin.css                WP Admin styles
    └── js/
        └── frontend.js              Portal + dashboard JS
```

---

## Post Meta Reference

### cfqm_quote
| Key | Type | Description |
|-----|------|-------------|
| `_cfqm_customer_name` | string | Customer full name |
| `_cfqm_customer_email` | string | Customer email |
| `_cfqm_customer_phone` | string | Customer phone |
| `_cfqm_query_id` | int | Linked HO query post ID |
| `_cfqm_trade_id` | int | Trade profile post ID |
| `_cfqm_status` | string | Quote status enum |
| `_cfqm_version` | string | e.g. "1.0", "1.1" |
| `_cfqm_iteration_limit` | int | Max amendments allowed |
| `_cfqm_iteration_used` | int | Amendments used so far |
| `_cfqm_vat_enabled` | bool | VAT applies? |
| `_cfqm_vat_rate` | float | VAT % (default 20) |
| `_cfqm_deposit_type` | string | "none" / "percent" / "fixed" |
| `_cfqm_deposit_percent` | float | Deposit % |
| `_cfqm_deposit_amount` | float | Calculated deposit £ |
| `_cfqm_grand_total` | float | Grand total inc. VAT |
| `_cfqm_vat_amount` | float | VAT amount £ |
| `_cfqm_validity_date` | string | Quote expiry date (Y-m-d) |
| `_cfqm_portal_token` | string | Raw portal token |
| `_cfqm_pdf_path` | string | Local file path to PDF |
| `_cfqm_pdf_url` | string | Public URL to PDF |
| `_cfqm_stripe_link` | string | Stripe Payment Link URL |
| `_cfqm_project_label` | string | HO project grouping label |
| `_cfqm_sent_at` | datetime | When first sent |
| `_cfqm_signature` | string | E-signature (typed name) |
| `_cfqm_signed_at` | datetime | E-sign timestamp |
| `_cfqm_selected_areas` | JSON | Area IDs chosen on accept |
| `_cfqm_amendment_history` | JSON | Amendment request log |
| `_cfqm_snapshots` | JSON | Per-version data snapshots |

### cfqm_quote_area
| Key | Type | Description |
|-----|------|-------------|
| `_cfqm_quote_id` | int | Parent quote ID |
| `_cfqm_area_type` | string | "included" / "optional" / "exclusion" |
| `_cfqm_scope` | string | Scope of work description |
| `_cfqm_price` | float | Area price £ |
| `_cfqm_validity_date` | string | Area-level expiry (Y-m-d) |
| `_cfqm_sort_order` | int | Display order |

---

## Requirements

- WordPress 6.3+
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- UK postcodes only (uses postcodes.io)
- Stripe account (for deposit payments)
- One of: Paid Memberships Pro, MemberPress, or WooCommerce Memberships (for tier enforcement)

---

## License

Proprietary — © ChangeFluent Ltd. Not for redistribution.
